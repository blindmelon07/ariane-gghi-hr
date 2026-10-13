{{-- Phone/browser push notifications. Used by the toggle in livewire/notification-bell. --}}
@if (\App\Notifications\Channels\WebPushChannel::isConfigured())
<script>
    window.gghiPush = (() => {
        const publicKey = @json(config('services.webpush.public_key'));
        const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

        const supported = () => 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
        const isIos = () => /iphone|ipad|ipod/i.test(navigator.userAgent);
        const isStandalone = () => window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;

        function keyBytes(base64) {
            const padded = (base64 + '='.repeat((4 - base64.length % 4) % 4)).replace(/-/g, '+').replace(/_/g, '/');
            return Uint8Array.from(atob(padded), c => c.charCodeAt(0));
        }

        async function send(url, body) {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify(body),
            });
            if (!res.ok) throw new Error('Server rejected the subscription');
        }

        async function save(sub) {
            const encoding = (PushManager.supportedContentEncodings || ['aesgcm'])[0];
            await send('/push/subscribe', { ...sub.toJSON(), contentEncoding: encoding });
        }

        return {
            // 'unsupported' | 'ios-install' | 'denied' | 'on' | 'off'
            async status() {
                if (isIos() && !isStandalone()) return 'ios-install';
                if (!supported()) return 'unsupported';
                if (Notification.permission === 'denied') return 'denied';
                const reg = await navigator.serviceWorker.ready;
                const sub = await reg.pushManager.getSubscription();
                return sub && Notification.permission === 'granted' ? 'on' : 'off';
            },

            async enable() {
                if ((await Notification.requestPermission()) !== 'granted') return this.status();
                const reg = await navigator.serviceWorker.ready;
                const sub = await reg.pushManager.getSubscription()
                    || await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: keyBytes(publicKey) });
                await save(sub);
                return 'on';
            },

            async disable() {
                const reg = await navigator.serviceWorker.ready;
                const sub = await reg.pushManager.getSubscription();
                if (sub) {
                    await send('/push/unsubscribe', { endpoint: sub.endpoint }).catch(() => {});
                    await sub.unsubscribe();
                }
                return 'off';
            },

            // Sends the logged-in user a test notification; resolves to the number of devices it went to.
            async test() {
                await this.sync();
                const res = await fetch('/push/test', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
                });
                if (!res.ok) throw new Error(res.status === 429 ? 'Please wait a minute before testing again.' : 'Test failed.');
                return (await res.json()).devices;
            },

            // Re-link an existing subscription to whoever is logged in now (shared devices, re-logins).
            async sync() {
                if (!supported() || Notification.permission !== 'granted') return;
                const reg = await navigator.serviceWorker.ready;
                const sub = await reg.pushManager.getSubscription();
                if (sub) save(sub).catch(() => {});
            },
        };
    })();

    // A push arrived while the portal is open (posted by sw.js) — refresh the bell now.
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', (event) => {
            if (event.data && event.data.type === 'gghi-notification' && window.Livewire) {
                window.Livewire.dispatch('notification-received');
            }
        });
    }

    window.addEventListener('load', () => {
        try {
            if (sessionStorage.getItem('gghiPushSynced') !== '{{ auth()->id() }}') {
                window.gghiPush.sync();
                sessionStorage.setItem('gghiPushSynced', '{{ auth()->id() }}');
            }
        } catch (e) {}
    });
</script>
@endif
