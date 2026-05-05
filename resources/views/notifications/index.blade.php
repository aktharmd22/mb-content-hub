<x-app-layout>
    <x-slot name="header">Notifications</x-slot>
    <x-slot name="title">Notifications</x-slot>

    <div class="p-6 max-w-3xl"
         x-data="{ checking: false }"
         x-init="
            setInterval(async () => {
                try {
                    checking = true;
                    const r = await fetch('{{ route('notifications.dropdown') }}', { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
                    const d = await r.json();
                    // If the server has more notifications than what's rendered, do a soft reload of just the list.
                    const rendered = document.querySelectorAll('[data-notification-row]').length;
                    if (d.items.length > 0 && (d.unread_count !== {{ auth()->user()->unreadNotifications->count() }} || d.items[0].id !== document.querySelector('[data-notification-row]')?.dataset.id)) {
                        window.location.reload();
                    }
                } catch (e) {} finally { checking = false; }
            }, 15000);
         ">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-medium text-gray-100">Notifications</h2>
                <p class="text-sm text-gray-500 mt-0.5">Live — checks for new ones every 15 seconds.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('notifications.preferences') }}" class="text-xs text-gray-500 hover:text-gray-300">Preferences</a>
                @if(auth()->user()->unreadNotifications->count() > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 text-xs font-medium bg-ink-700 hover:bg-ink-600 text-gray-200 rounded-lg transition-colors">
                            Mark all as read
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
            @if($notifications->count() === 0)
                <div class="p-12 text-center">
                    <svg class="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400">No notifications yet.</p>
                </div>
            @else
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($notifications as $n)
                        @php $isUnread = $n->read_at === null; @endphp
                        <li data-notification-row data-id="{{ $n->id }}">
                            <form method="POST" action="{{ route('notifications.read', $n->id) }}" class="flex">
                                @csrf
                                <button type="submit" class="flex-1 flex items-start gap-3 px-5 py-3 text-left hover:bg-ink-800/50 transition-colors">
                                    <span class="mt-1.5 w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $isUnread ? 'bg-indigo-500' : 'bg-transparent' }}"></span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm {{ $isUnread ? 'text-gray-100 font-medium' : 'text-gray-400' }}">
                                            {{ $n->data['message'] ?? 'Notification' }}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $n->created_at->diffForHumans() }}</p>
                                    </div>
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
                <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
