<x-app-layout>
    <x-slot name="header">Clients</x-slot>
    <x-slot name="title">Clients</x-slot>

    <div class="p-6">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Clients</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">All clients on the platform.</p>
            </div>
            <a href="{{ route('sales.clients.create') }}"
               class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add client
            </a>
        </div>

        <form method="GET" class="flex items-center gap-2 mb-4">
            <div class="relative flex-1 max-w-md">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Search clients"
                       class="w-full pl-9 pr-3 py-1.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"/>
            </div>
            @if(request('q'))
                <a href="{{ route('sales.clients.index') }}" class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">Clear</a>
            @endif
        </form>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
            @if($clients->count() === 0)
                <div class="p-12 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">No clients yet.</p>
                    <a href="{{ route('sales.clients.create') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Add your first client</a>
                </div>
            @else
                <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Client</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Contact</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Articles</th>
                            <th class="text-right px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($clients as $c)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-950/50 transition-colors">
                                <td class="px-4 py-3">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $c->displayName() }}</p>
                                    @if($c->secondaryName())
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $c->secondaryName() }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                    @if($c->contact_email)<p>{{ $c->contact_email }}</p>@endif
                                    @if($c->contact_phone)<p>{{ $c->contact_phone }}</p>@endif
                                    @if(! $c->contact_email && ! $c->contact_phone)—@endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $c->articles_count }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('sales.clients.edit', $c) }}" class="px-2 py-1 text-xs text-gray-600 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 transition-colors">Edit</a>
                                        @if($c->articles_count === 0)
                                            <form method="POST" action="{{ route('sales.clients.destroy', $c) }}"
                                                  onsubmit="return confirm('Delete {{ $c->name }}?');" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="px-2 py-1 text-xs text-gray-600 hover:text-rose-600 dark:text-gray-400 dark:hover:text-rose-400 transition-colors">Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
                <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800">
                    {{ $clients->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
