<x-app-layout>
    <x-slot name="header">My articles</x-slot>
    <x-slot name="title">My articles</x-slot>

    <div class="p-6">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">My articles</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">All submissions you've made.</p>
            </div>
            <a href="{{ route('sales.articles.create') }}"
               class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New article
            </a>
        </div>

        <!-- Filters -->
        <form method="GET" class="flex flex-wrap items-center gap-2 mb-4">
            <div class="relative flex-1 min-w-[200px]">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Search by title or code"
                       class="w-full pl-9 pr-3 py-1.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"/>
            </div>

            <select name="stage" class="px-3 py-1.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All stages</option>
                @foreach($stages as $s)
                    <option value="{{ $s->value }}" @selected(request('stage') === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>

            <select name="client_id" class="px-3 py-1.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All clients</option>
                @foreach($clients as $c)
                    <option value="{{ $c->id }}" @selected(request('client_id') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>

            <button type="submit" class="px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                Filter
            </button>
            @if(request()->hasAny(['q', 'stage', 'client_id']))
                <a href="{{ route('sales.articles.index') }}" class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">Clear</a>
            @endif
        </form>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
            @if($articles->count() === 0)
                <div class="p-12 text-center">
                    <svg class="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">No articles match your filters.</p>
                    <a href="{{ route('sales.articles.create') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Submit your first article</a>
                </div>
            @else
                <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Code</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Title</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Client</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Stage</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Deadline</th>
                            <th class="text-right px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Days in stage</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($articles as $a)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-950/50 transition-colors cursor-pointer"
                                onclick="window.location='{{ route('sales.articles.show', $a) }}'">
                                <td class="px-4 py-3 text-xs font-mono text-gray-500 dark:text-gray-400">{{ $a->article_code }}</td>
                                <td class="px-4 py-3">
                                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ $a->title }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $a->client?->name ?? '—' }}</td>
                                <td class="px-4 py-3"><x-stage-badge :stage="$a->current_stage" /></td>
                                <td class="px-4 py-3 text-sm">
                                    @if($a->deadline)
                                        @php $days = $a->days_until_deadline; @endphp
                                        @if($days < 0)
                                            <span class="text-rose-600 dark:text-rose-400">{{ $a->deadline->format('M j') }} ({{ abs($days) }}d late)</span>
                                        @elseif($days === 0)
                                            <span class="text-amber-600 dark:text-amber-400">Today</span>
                                        @elseif($days <= 2)
                                            <span class="text-amber-600 dark:text-amber-400">{{ $a->deadline->format('M j') }} ({{ $days }}d)</span>
                                        @else
                                            <span class="text-gray-700 dark:text-gray-300">{{ $a->deadline->format('M j') }}</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-xs text-gray-500 dark:text-gray-400">{{ $a->days_in_stage }}d</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
                <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800">
                    {{ $articles->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
