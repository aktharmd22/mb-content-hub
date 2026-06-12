@php $unreadCount = auth()->user()->unreadNotifications()->count(); @endphp

<div x-data="notificationBell({{ $unreadCount }})" class="relative">
    <button
        @click="toggle()"
        @click.outside="open = false"
        class="relative p-2 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
        title="Notifications"
    >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <span
            x-show="unreadCount > 0"
            x-text="unreadCount > 9 ? '9+' : unreadCount"
            class="absolute top-1 right-1 min-w-[14px] h-3.5 px-1 flex items-center justify-center text-[10px] font-medium text-white bg-rose-500 rounded-full"
        ></span>
    </button>

    <div
        x-show="open" x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 top-full mt-1 w-80 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm z-50"
    >
        <div class="px-4 py-2.5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Notifications</h3>
            <template x-if="items.length > 0">
                <form method="POST" action="{{ route('notifications.read-all') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Mark all read</button>
                </form>
            </template>
        </div>

        <div class="max-h-96 overflow-y-auto">
            <template x-if="items.length === 0">
                <div class="px-5 py-12 text-center">
                    <svg class="w-7 h-7 text-gray-300 dark:text-gray-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400">You're all caught up.</p>
                </div>
            </template>

            <template x-for="n in items" :key="n.id">
                <a
                    :href="n.url && n.url !== '#' ? n.url : '{{ route('notifications.index') }}'"
                    @click="markReadAsync(n)"
                    class="block px-4 py-3 border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-950/50 transition-colors"
                >
                    <p class="text-sm text-gray-900 dark:text-gray-100" x-text="n.message"></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="n.created_at"></p>
                </a>
            </template>
        </div>

        <div class="px-4 py-2.5 border-t border-gray-100 dark:border-gray-800 text-center">
            <a href="{{ route('notifications.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">View all notifications</a>
        </div>
    </div>
</div>

<script>
    function notificationBell(initialCount) {
        return {
            open: false,
            unreadCount: initialCount,
            items: [],
            loaded: false,
            pollHandle: null,
            previousCount: initialCount,

            init() {
                console.log('[bell] init — polling every 10s. Initial unread:', this.unreadCount);
                this.startPolling();

                // Pause polling when the tab is in the background; resume on focus.
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        this.stopPolling();
                    } else {
                        this.poll();
                        this.startPolling();
                    }
                });
            },

            startPolling() {
                if (this.pollHandle) return;
                this.pollHandle = setInterval(() => this.poll(), 10000);
            },

            stopPolling() {
                if (this.pollHandle) {
                    clearInterval(this.pollHandle);
                    this.pollHandle = null;
                }
            },

            async poll() {
                try {
                    const r = await fetch('{{ route('notifications.dropdown') }}', {
                        headers: { 'Accept': 'application/json' },
                        cache: 'no-store',
                    });
                    if (! r.ok) {
                        console.warn('[bell] poll non-ok', r.status);
                        return;
                    }
                    const data = await r.json();
                    console.log('[bell] poll →', data.unread_count, 'unread');

                    // Toast when a new notification arrives while user is on the page.
                    if (data.unread_count > this.previousCount && data.items.length > 0) {
                        const newest = data.items[0];
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { type: 'info', message: newest.message }
                        }));
                    }
                    this.previousCount = data.unread_count;
                    this.unreadCount = data.unread_count;

                    // Always update the dropdown items so they're fresh when reopened.
                    this.items = data.items;

                    // Live-update the sidebar Support badge from the same poll.
                    if (typeof data.support_active !== 'undefined') {
                        window.dispatchEvent(new CustomEvent('support-count', { detail: data.support_active }));
                    }
                } catch (e) {
                    console.warn('[bell] poll failed', e);
                }
            },

            async toggle() {
                this.open = !this.open;
                if (this.open) {
                    await this.refresh();
                }
            },

            async refresh() {
                try {
                    const r = await fetch('{{ route('notifications.dropdown') }}', { headers: { 'Accept': 'application/json' } });
                    const data = await r.json();
                    this.unreadCount = data.unread_count;
                    this.previousCount = data.unread_count;
                    this.items = data.items;
                    this.loaded = true;
                } catch (e) { /* ignore */ }
            },

            // Mark as read in the background; don't block the link's normal navigation.
            // `keepalive` lets the request finish even after the browser starts loading the new page.
            markReadAsync(n) {
                try {
                    fetch(`/notifications/${n.id}/read`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        keepalive: true,
                    }).catch(() => {});
                } catch (e) { /* ignore */ }
            },
        }
    }
</script>
