const CACHE_NAME = 'gghi-hr-v7';
const OFFLINE_URL = '/offline.html';

// Pre-cache the offline page on install
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll([OFFLINE_URL]))
    );
    self.skipWaiting();
});

// Clean up old caches on activate
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

// Push notifications sent by App\Notifications\Channels\WebPushChannel
self.addEventListener('push', event => {
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = { body: event.data ? event.data.text() : '' };
    }

    event.waitUntil(Promise.all([
        self.registration.showNotification(data.title || 'GGHI HR', {
            body: data.body || '',
            icon: data.icon || '/images/icon-192.png',
            badge: data.badge || '/images/icon-192.png',
            tag: data.tag,
            data: { url: data.url || '/login' },
        }),
        // Number on the installed app's icon (Badging API; ignored where unsupported).
        ('setAppBadge' in self.navigator && typeof data.unread === 'number')
            ? (data.unread > 0 ? self.navigator.setAppBadge(data.unread) : self.navigator.clearAppBadge()).catch(() => {})
            : Promise.resolve(),
        // Tell any open portal tab, so its bell badge updates without a reload.
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(windows => windows.forEach(win => win.postMessage({ type: 'gghi-notification' }))),
    ]));
});

// Tapping a notification focuses an open portal tab, or opens one
self.addEventListener('notificationclick', event => {
    event.notification.close();
    const target = new URL(event.notification.data?.url || '/login', self.location.origin).href;

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windows => {
            for (const win of windows) {
                if (win.url.startsWith(self.location.origin) && 'focus' in win) {
                    win.navigate(target);
                    return win.focus();
                }
            }
            return clients.openWindow(target);
        })
    );
});

self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Only handle same-origin GET requests
    if (request.method !== 'GET' || url.origin !== location.origin) return;

    // Cache-first for Vite compiled assets (JS/CSS with content hashes)
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.match(request).then(cached => {
                if (cached) return cached;
                return fetch(request).then(response => {
                    if (response.ok) {
                        // Clone synchronously, before the body can be consumed elsewhere —
                        // caches.open() is async, so cloning inside its .then() risks the
                        // original response already being read by the time clone() runs.
                        const copy = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(request, copy));
                    }
                    return response;
                }).catch(() => caches.match(OFFLINE_URL));
            })
        );
        return;
    }

    // Static images: cache with network fallback
    if (url.pathname.startsWith('/images/')) {
        event.respondWith(
            caches.match(request).then(cached => {
                if (cached) return cached;
                return fetch(request).then(response => {
                    if (response.ok) {
                        const copy = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(request, copy));
                    }
                    return response;
                });
            })
        );
        return;
    }

    // All HTML navigation: network-first, show offline page on failure
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_URL))
        );
        return;
    }
});
