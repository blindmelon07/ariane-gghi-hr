{{--
    Styled replacement for the browser's confirm() box used by every wire:confirm
    in the app (sign out, delete, finalize, send SMS, …).

    How it works: a capture-phase click listener stops the click on any element
    with wire:confirm before Livewire sees it and shows this dialog instead. On
    "confirm" the click is replayed with window.confirm briefly answering "yes",
    so Livewire runs the action exactly as before. Pages need no changes.
--}}
<style>
    #gghi-confirm { position: fixed; inset: 0; z-index: 9999; display: none; align-items: center; justify-content: center; padding: 16px; background: rgba(15, 23, 42, .55); backdrop-filter: blur(2px); }
    #gghi-confirm.open { display: flex; animation: gghi-fade .12s ease-out; }
    #gghi-confirm .box { width: 100%; max-width: 400px; background: #fff; color: #111827; border: 1px solid #e5e7eb; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,.25); padding: 22px; animation: gghi-pop .14s ease-out; }
    #gghi-confirm .head { display: flex; gap: 12px; align-items: flex-start; }
    #gghi-confirm .icon { flex: none; width: 40px; height: 40px; border-radius: 999px; display: flex; align-items: center; justify-content: center; background: #eef2ff; color: #4f46e5; }
    #gghi-confirm.danger .icon { background: #fef2f2; color: #dc2626; }
    #gghi-confirm .title { font-size: 16px; font-weight: 600; margin: 2px 0 4px; }
    #gghi-confirm .msg { font-size: 14px; line-height: 1.45; color: #4b5563; white-space: pre-line; }
    #gghi-confirm .actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
    #gghi-confirm button { font-size: 14px; font-weight: 500; border-radius: 10px; padding: 9px 16px; cursor: pointer; border: 1px solid transparent; }
    #gghi-confirm .cancel { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
    #gghi-confirm .cancel:hover { background: #e5e7eb; }
    #gghi-confirm .ok { background: #4f46e5; color: #fff; }
    #gghi-confirm .ok:hover { background: #4338ca; }
    #gghi-confirm.danger .ok { background: #dc2626; }
    #gghi-confirm.danger .ok:hover { background: #b91c1c; }
    #gghi-confirm button:focus-visible { outline: 2px solid #6366f1; outline-offset: 2px; }
    .dark #gghi-confirm .box { background: #1e293b; color: #f1f5f9; border-color: #334155; }
    .dark #gghi-confirm .msg { color: #cbd5e1; }
    .dark #gghi-confirm .icon { background: rgba(99,102,241,.15); color: #a5b4fc; }
    .dark #gghi-confirm.danger .icon { background: rgba(220,38,38,.15); color: #fca5a5; }
    .dark #gghi-confirm .cancel { background: #334155; color: #e2e8f0; border-color: #475569; }
    .dark #gghi-confirm .cancel:hover { background: #475569; }
    @keyframes gghi-fade { from { opacity: 0 } to { opacity: 1 } }
    @keyframes gghi-pop { from { transform: scale(.96); opacity: 0 } to { transform: scale(1); opacity: 1 } }
</style>

<div id="gghi-confirm" role="dialog" aria-modal="true" aria-labelledby="gghi-confirm-title">
    <div class="box">
        <div class="head">
            <div class="icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            </div>
            <div>
                <p class="title" id="gghi-confirm-title">Please confirm</p>
                <p class="msg"></p>
            </div>
        </div>
        <div class="actions">
            <button type="button" class="cancel">Cancel</button>
            <button type="button" class="ok">Confirm</button>
        </div>
    </div>
</div>

<script>
    (() => {
        const root   = document.getElementById('gghi-confirm');
        const msgEl  = root.querySelector('.msg');
        const titleEl = root.querySelector('.title');
        const okBtn  = root.querySelector('.ok');
        const cancel = root.querySelector('.cancel');
        let resolver = null;

        // Destructive wording gets a red button and a matching label.
        const KINDS = [
            [/sign out|log ?out/i,           'Sign out', 'Sign out?',     true],
            [/delete|remove|clear/i,         'Delete',   'Are you sure?', true],
            [/reject|deny|deactivate/i,      'Yes, continue', 'Are you sure?', true],
            [/finalize|cannot be undone/i,   'Yes, continue', 'Are you sure?', true],
            [/send|text|notify/i,            'Send',     'Send now?',     false],
        ];

        function ask(message) {
            const kind = KINDS.find(([re]) => re.test(message));
            titleEl.textContent = kind ? kind[2] : 'Please confirm';
            okBtn.textContent   = kind ? kind[1] : 'Confirm';
            root.classList.toggle('danger', !!(kind && kind[3]));
            msgEl.textContent   = message;
            root.classList.add('open');
            setTimeout(() => okBtn.focus(), 0);
            return new Promise(resolve => { resolver = resolve; });
        }

        function close(result) {
            root.classList.remove('open');
            if (resolver) { resolver(result); resolver = null; }
        }

        okBtn.addEventListener('click', () => close(true));
        cancel.addEventListener('click', () => close(false));
        root.addEventListener('click', e => { if (e.target === root) close(false); });
        document.addEventListener('keydown', e => {
            if (!root.classList.contains('open')) return;
            if (e.key === 'Escape') { e.preventDefault(); close(false); }
        });

        document.addEventListener('click', (e) => {
            const el = e.target.closest && e.target.closest('[wire\\:confirm]');
            if (!el || el.__gghiConfirmed || el.disabled) return;

            e.preventDefault();
            e.stopImmediatePropagation();

            ask(el.getAttribute('wire:confirm') || 'Are you sure?').then(ok => {
                if (!ok) return;
                // Replay the click; Livewire's own confirm() call answers "yes" this once.
                const nativeConfirm = window.confirm;
                window.confirm = () => true;
                el.__gghiConfirmed = true;
                try { el.click(); } finally {
                    window.confirm = nativeConfirm;
                    el.__gghiConfirmed = false;
                }
            });
        }, true);
    })();
</script>
