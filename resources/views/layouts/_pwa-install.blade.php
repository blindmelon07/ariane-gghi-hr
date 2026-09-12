@auth
{{-- Install banner — bottom sheet on mobile, bottom-right card on desktop.
     Shown for Android (via beforeinstallprompt) and iOS (manual instructions),
     hidden once already installed / running standalone. --}}
<div
    x-data
    x-show="$store.pwaInstall.showBanner"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed z-40 left-3 right-3 bottom-20 sm:left-auto sm:right-5 sm:bottom-5 sm:w-80
           bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700
           rounded-2xl shadow-xl p-4"
>
    <div class="flex items-start gap-3">
        <img src="/images/icon-192.png" alt="" class="w-10 h-10 rounded-xl shrink-0">
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-gray-900 dark:text-white">Install GGHI HR</p>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                Add it to your home screen for quick, full-screen access — no browser bar, works like an app.
            </p>
            <div class="flex items-center gap-3 mt-3">
                <button
                    @click="$store.pwaInstall.install()"
                    class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium transition-colors"
                >
                    Install
                </button>
                <button
                    @click="$store.pwaInstall.dismiss()"
                    class="text-xs text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300"
                >
                    Not now
                </button>
            </div>
        </div>
        <button @click="$store.pwaInstall.dismiss()" class="shrink-0 text-gray-300 dark:text-slate-600 hover:text-gray-500 dark:hover:text-slate-400" aria-label="Dismiss">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
</div>

{{-- iOS instructions modal — Safari has no install API, so we walk the user through
     Share -> Add to Home Screen manually. --}}
<div
    x-data
    x-show="$store.pwaInstall.showIOSModal"
    x-cloak
    x-transition.opacity
    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 dark:bg-black/70 p-4"
    @click.self="$store.pwaInstall.showIOSModal = false"
>
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full sm:max-w-sm p-6">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Install on iOS</h3>
        <p class="text-xs text-gray-400 dark:text-slate-500 mb-5">Safari doesn't support one-tap install — just a few taps:</p>

        <ol class="space-y-4 text-sm text-gray-700 dark:text-slate-300">
            <li class="flex items-start gap-3">
                <span class="shrink-0 w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-xs font-bold flex items-center justify-center">1</span>
                <span class="flex items-center gap-1.5">
                    Tap the <strong>Share</strong> icon
                    <svg class="w-4 h-4 text-gray-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 16.5V9.75m0 0l-3 3m3-3l3 3M6.75 19.5a2.25 2.25 0 01-2.25-2.25V7.5A2.25 2.25 0 016.75 5.25H9m6 0h2.25A2.25 2.25 0 0119.5 7.5v9.75a2.25 2.25 0 01-2.25 2.25H15"/></svg>
                    in the Safari toolbar.
                </span>
            </li>
            <li class="flex items-start gap-3">
                <span class="shrink-0 w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-xs font-bold flex items-center justify-center">2</span>
                <span>Scroll down and tap <strong>Add to Home Screen</strong>.</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="shrink-0 w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-xs font-bold flex items-center justify-center">3</span>
                <span>Tap <strong>Add</strong> — the GGHI HR icon now appears on your home screen.</span>
            </li>
        </ol>

        <button
            @click="$store.pwaInstall.showIOSModal = false; $store.pwaInstall.dismiss()"
            class="w-full mt-6 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium transition-colors"
        >
            Got it
        </button>
    </div>
</div>
@endauth
