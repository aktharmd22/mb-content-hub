<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
          dark: localStorage.getItem('theme') !== 'light',
          sidebarOpen: localStorage.getItem('sidebar') !== 'collapsed',
          mobileMenuOpen: false,
          searchOpen: false,
          toasts: []
      }"
      :class="{ 'dark': dark }"
      x-init="
          $watch('dark', v => localStorage.setItem('theme', v ? 'dark' : 'light'));
          $watch('sidebarOpen', v => localStorage.setItem('sidebar', v ? 'open' : 'collapsed'));
          window.addEventListener('toast', e => {
              const t = { id: Date.now(), ...e.detail };
              toasts.push(t);
              setTimeout(() => toasts = toasts.filter(x => x.id !== t.id), 4000);
          });
          window.addEventListener('keydown', e => {
              if ((e.metaKey || e.ctrlKey) && e.key === 'k') { e.preventDefault(); searchOpen = true; }
              if (e.key === 'Escape') searchOpen = false;
          });
      "
      class="dark"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' — ' . \App\Support\Branding::name() : \App\Support\Branding::name() }}</title>
    @if($favicon = \App\Support\Branding::faviconUrl())
        <link rel="icon" href="{{ $favicon }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-ink-900 text-gray-100 h-screen flex overflow-hidden">

    <!-- Mobile backdrop -->
    <div x-show="mobileMenuOpen" x-cloak
         style="display: none;"
         @click="mobileMenuOpen = false"
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/60 z-30 md:hidden"></div>

    <!-- Sidebar -->
    <aside
        :class="[
            mobileMenuOpen ? 'translate-x-0' : '-translate-x-full',
            sidebarOpen ? 'md:w-56' : 'md:w-14'
        ]"
        class="fixed md:static inset-y-0 left-0 z-40 w-56 md:translate-x-0
               flex-shrink-0 bg-ink-850 border-r border-ink-700 flex flex-col
               transform transition-all duration-200 overflow-hidden"
    >
        <div class="h-14 flex items-center justify-center px-3 border-b border-ink-700 flex-shrink-0 relative" title="{{ \App\Support\Branding::name() }}">
            @if($brandLogo = \App\Support\Branding::logoUrl())
                <div :class="(sidebarOpen || mobileMenuOpen) ? 'h-10' : 'h-8'" class="flex items-center justify-center transition-all">
                    <img src="{{ $brandLogo }}" alt="{{ \App\Support\Branding::name() }}" class="max-h-full max-w-full object-contain"/>
                </div>
            @else
                <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-lg flex items-center justify-center flex-shrink-0 shadow-lg shadow-indigo-500/20">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            @endif
            <button @click="mobileMenuOpen = false"
                    class="md:hidden absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-gray-500 hover:text-gray-200 hover:bg-ink-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav @click="mobileMenuOpen = false" class="flex-1 py-3 px-2 space-y-0.5 overflow-y-auto">
            @include('layouts.partials.sidebar-nav')
        </nav>

        <div class="border-t border-ink-700 p-2 hidden md:block">
            <button
                @click="sidebarOpen = !sidebarOpen"
                class="w-full flex items-center justify-center p-2 rounded-lg text-gray-500 hover:text-gray-200 hover:bg-ink-700 transition-colors"
            >
                <svg :class="sidebarOpen ? '' : 'rotate-180'" class="w-4 h-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                </svg>
            </button>
        </div>
    </aside>

    <!-- Main area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        <header class="h-14 bg-ink-850 border-b border-ink-700 flex items-center px-3 sm:px-4 gap-2 sm:gap-4 flex-shrink-0">
            <button @click="mobileMenuOpen = true"
                    class="md:hidden p-2 rounded-lg text-gray-400 hover:text-gray-200 hover:bg-ink-700 transition-colors flex-shrink-0"
                    title="Open menu">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div class="flex-1 min-w-0">
                @isset($header)
                    <h1 class="text-sm font-medium text-gray-100 truncate">{{ $header }}</h1>
                @endisset
            </div>

            <button
                @click="searchOpen = true"
                class="flex items-center gap-2 p-2 sm:px-3 sm:py-1.5 sm:bg-ink-800 sm:hover:bg-ink-700 sm:border sm:border-ink-600 rounded-lg text-xs text-gray-500 hover:text-gray-200 transition-colors"
                title="Search (⌘K)"
            >
                <svg class="w-4 h-4 sm:w-3.5 sm:h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span class="hidden sm:inline">Search</span>
                <kbd class="hidden md:inline-flex items-center px-1.5 py-0.5 rounded bg-ink-700 border border-ink-600 text-[10px] font-mono">⌘K</kbd>
            </button>

            <div class="flex items-center gap-1">
                @include('layouts.partials.notification-bell')

                <button
                    @click="dark = !dark"
                    class="p-2 rounded-lg text-gray-500 hover:text-gray-200 hover:bg-ink-700 transition-colors"
                    title="Toggle theme"
                >
                    <template x-if="!dark">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </template>
                    <template x-if="dark">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </template>
                </button>

                <div x-data="{ open: false }" class="relative">
                    <button
                        @click="open = !open"
                        @click.outside="open = false"
                        class="flex items-center gap-2 px-2 sm:px-3 py-1.5 rounded-lg hover:bg-ink-700 transition-colors"
                    >
                        <div class="w-6 h-6 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-xs font-medium text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        </div>
                        <span class="hidden sm:inline text-sm text-gray-300 max-w-[6rem] truncate">{{ auth()->user()->name }}</span>
                        <svg class="hidden sm:block w-3 h-3 text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div
                        x-show="open" x-cloak
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 top-full mt-1 w-44 bg-ink-850 border border-ink-700 rounded-lg shadow-xl shadow-black/50 py-1 z-50"
                    >
                        <div class="px-3 py-2 border-b border-ink-700">
                            <p class="text-xs font-medium text-gray-100 truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500">{{ auth()->user()->role_label }}</p>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-300 hover:bg-ink-700 transition-colors">
                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Profile
                        </a>
                        <a href="{{ route('notifications.preferences') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-300 hover:bg-ink-700 transition-colors">
                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            Preferences
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-2 w-full px-3 py-2 text-sm text-rose-400 hover:bg-rose-950/40 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                Sign out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto">
            {{ $slot }}
        </main>
    </div>

    @include('layouts.partials.global-search')

    {{-- Global copy-to-clipboard helper (used by caption/hashtag copy buttons) --}}
    <script>
        window.copyToClipboard = function (text) {
            const done = () => window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Copied to clipboard' } }));
            const fail = () => window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Copy failed — select and copy manually.' } }));
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(done).catch(fail);
            } else {
                // Fallback for non-HTTPS / older browsers
                try {
                    const ta = document.createElement('textarea');
                    ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
                    document.body.appendChild(ta); ta.select();
                    document.execCommand('copy'); document.body.removeChild(ta);
                    done();
                } catch (e) { fail(); }
            }
        };
    </script>

    {{-- ==================== Platform-wide live refresh ====================
         Any element with data-live="<unique-id>" auto-updates without a manual
         page refresh. The poller re-fetches the current URL every few seconds and
         swaps only the marked regions whose HTML actually changed. It pauses while
         the tab is hidden and never disrupts a region you're actively typing in. --}}
    <script>
        (function () {
            const regions = () => Array.from(document.querySelectorAll('[data-live]'));
            if (!regions().length) return;

            const INTERVAL = 12000; // 12s — gentle on shared hosting
            let timer = null;
            let inFlight = false;

            function regionBusy(el) {
                // Don't swap a region the user is interacting with (typing, open dropdown, etc.)
                const active = document.activeElement;
                if (active && el.contains(active) &&
                    ['INPUT', 'TEXTAREA', 'SELECT', 'BUTTON'].includes(active.tagName)) {
                    return true;
                }
                // Respect an explicit lock (e.g. an open upload form sets data-live-lock="1")
                if (el.querySelector('[data-live-lock="1"]')) return true;
                return false;
            }

            async function poll() {
                if (document.hidden || inFlight) return;
                const current = regions();
                if (!current.length) return;
                inFlight = true;
                try {
                    const r = await fetch(window.location.href, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        cache: 'no-store',
                    });
                    if (!r.ok) return;
                    const doc = new DOMParser().parseFromString(await r.text(), 'text/html');
                    current.forEach(el => {
                        const key = el.getAttribute('data-live');
                        const fresh = doc.querySelector('[data-live="' + (window.CSS && CSS.escape ? CSS.escape(key) : key) + '"]');
                        if (!fresh) return;
                        if (regionBusy(el)) return;
                        if (fresh.innerHTML !== el.innerHTML) {
                            el.innerHTML = fresh.innerHTML;
                        }
                    });
                } catch (e) { /* silent — try again next tick */ }
                finally { inFlight = false; }
            }

            function start() { if (!timer) timer = setInterval(poll, INTERVAL); }
            function stop() { if (timer) { clearInterval(timer); timer = null; } }

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) { stop(); } else { poll(); start(); }
            });
            start();
        })();
    </script>

    <!-- Toast notifications -->
    <div class="fixed bottom-4 right-4 z-50 flex flex-col gap-2" style="pointer-events: none;">
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                :class="{
                    'border-emerald-500/30 bg-emerald-950/60': toast.type === 'success',
                    'border-rose-500/30 bg-rose-950/60': toast.type === 'error',
                    'border-amber-500/30 bg-amber-950/60': toast.type === 'warning',
                    'border-blue-500/30 bg-blue-950/60': !toast.type || toast.type === 'info',
                }"
                class="flex items-center gap-3 px-4 py-3 rounded-lg border backdrop-blur-md shadow-xl shadow-black/50 text-sm max-w-xs"
                style="pointer-events: auto;"
            >
                <span :class="{
                    'text-emerald-400': toast.type === 'success',
                    'text-rose-400': toast.type === 'error',
                    'text-amber-400': toast.type === 'warning',
                    'text-blue-400': !toast.type || toast.type === 'info',
                }">
                    <svg x-show="toast.type === 'success'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <svg x-show="toast.type === 'error'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <svg x-show="toast.type === 'warning'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <svg x-show="!toast.type || toast.type === 'info'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <span class="text-gray-200" x-text="toast.message"></span>
            </div>
        </template>
    </div>

    @if(session('success') || session('error'))
        <script>
            (function () {
                const fire = () => {
                    @if(session('success'))
                        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: @js(session('success')) } }));
                    @endif
                    @if(session('error'))
                        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: @js(session('error')) } }));
                    @endif
                };
                // Alpine loads via deferred Vite module scripts. Wait until it's ready
                // so the toast event isn't dispatched before any listener is registered.
                if (window.Alpine && window.Alpine.version) {
                    fire();
                } else {
                    document.addEventListener('alpine:initialized', fire, { once: true });
                }
            })();
        </script>
    @endif

    @livewireScripts
</body>
</html>
