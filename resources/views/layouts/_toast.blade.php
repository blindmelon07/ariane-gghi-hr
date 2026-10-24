{{--
    Shows the message of every $this->dispatch('toast', message: '…') from a
    Livewire component (approvals, fleet, guard log, …). Messages stack bottom-right
    and fade after a few seconds. data-navigate-once keeps a single listener
    across wire:navigate page swaps.
--}}
<style>
    #gghi-toasts { position: fixed; right: 16px; bottom: calc(16px + var(--sab, 0px)); z-index: 9998; display: flex; flex-direction: column; gap: 8px; max-width: calc(100vw - 32px); pointer-events: none; }
    @media (max-width: 1023px) { #gghi-toasts { bottom: calc(4.75rem + var(--sab, 0px)); } }
    #gghi-toasts .toast { pointer-events: auto; display: flex; gap: 10px; align-items: flex-start; width: 340px; max-width: 100%; background: #fff; color: #111827; border: 1px solid #e5e7eb; border-left: 4px solid #16a34a; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,.15); padding: 12px 14px; font-size: 14px; line-height: 1.4; animation: gghi-toast-in .16s ease-out; }
    #gghi-toasts .toast.leaving { opacity: 0; transform: translateY(6px); transition: opacity .2s, transform .2s; }
    #gghi-toasts .toast .text { flex: 1; }
    #gghi-toasts .toast button { flex: none; background: none; border: 0; color: #9ca3af; font-size: 18px; line-height: 1; cursor: pointer; padding: 0 2px; }
    #gghi-toasts .toast button:hover { color: #4b5563; }
    .dark #gghi-toasts .toast { background: #1e293b; color: #f1f5f9; border-color: #334155; border-left-color: #22c55e; }
    @keyframes gghi-toast-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
</style>
<div id="gghi-toasts" role="status" aria-live="polite"></div>
<script data-navigate-once>
    (function () {
        if (window.__gghiToastBound) return;
        window.__gghiToastBound = true;

        window.addEventListener('toast', function (e) {
            var detail  = e.detail || {};
            var message = typeof detail === 'string' ? detail : (detail.message || (Array.isArray(detail) ? (detail[0] || {}).message : ''));
            var host    = document.getElementById('gghi-toasts');
            if (!message || !host) return;

            var el = document.createElement('div');
            el.className = 'toast';
            var text = document.createElement('div');
            text.className = 'text';
            text.textContent = message;
            var close = document.createElement('button');
            close.type = 'button';
            close.setAttribute('aria-label', 'Dismiss');
            close.innerHTML = '&times;';
            el.append(text, close);
            host.append(el);

            var dismiss = function () { el.classList.add('leaving'); setTimeout(function () { el.remove(); }, 200); };
            close.addEventListener('click', dismiss);
            setTimeout(dismiss, 4500);
        });
    })();
</script>
