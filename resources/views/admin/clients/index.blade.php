<x-app-layout>
    <x-slot name="header">Clients</x-slot>
    <x-slot name="title">Clients</x-slot>

    <div class="p-6">

        <div class="mb-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Clients</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Every client across the business.</p>
        </div>

        {{-- Totals --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-4">
                <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">Total clients</p>
                <p class="text-2xl font-semibold text-gray-100 mt-1">{{ $totals['clients'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-4">
                <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">Articles</p>
                <p class="text-2xl font-semibold text-gray-100 mt-1">{{ $totals['articles'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-4">
                <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">Viral packages</p>
                <p class="text-2xl font-semibold text-gray-100 mt-1">{{ $totals['packages'] }}</p>
            </div>
        </div>

        <form method="GET" class="flex items-center gap-2 mb-4">
            <div class="relative flex-1 max-w-md">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Search by name, company or email"
                       class="w-full pl-9 pr-3 py-1.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"/>
            </div>
            <button type="submit" class="px-3 py-1.5 text-sm bg-ink-800 hover:bg-ink-700 text-gray-300 rounded-lg transition-colors">Search</button>
            @if(request('q'))
                <a href="{{ route('admin.clients.index') }}" class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">Clear</a>
            @endif
        </form>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
            @if($clients->count() === 0)
                <div class="p-12 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">No clients found.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[760px]">
                    <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Client</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Contact</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Added by</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Articles</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Packages</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Added</th>
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
                                <td class="px-4 py-3 text-xs text-gray-400">{{ $c->creator?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-300">{{ $c->articles_count }}</td>
                                <td class="px-4 py-3 text-sm text-gray-300">{{ $c->viral_packages_count }}</td>
                                <td class="px-4 py-3 text-xs text-gray-400">{{ $c->created_at->format('M j, Y') }}</td>
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
