<x-app-layout>
    <x-slot name="header">With sales</x-slot>
    <x-slot name="title">With sales</x-slot>

    @php
        $stageLabels = [
            'client_approval' => ['title' => 'With sales',         'desc' => 'Articles you\'ve submitted that sales is currently reviewing.'],
            'all'             => ['title' => 'All articles',       'desc' => 'Every article in the pipeline.'],
        ];
        $current = $stageLabels[$stage] ?? [
            'title' => ucfirst(str_replace('_', ' ', $stage)),
            'desc'  => 'Filtered article list.',
        ];
    @endphp

    <div class="p-6">

        <div class="mb-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $current['title'] }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $current['desc'] }}</p>
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
                <option value="all" @selected($stage === 'all')>All stages</option>
                @foreach($stages as $s)
                    <option value="{{ $s->value }}" @selected($stage === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>

            <button type="submit" class="px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">Filter</button>
            @if(request('q') || $stage !== 'client_approval')
                <a href="{{ route('lead.articles.index') }}" class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">Reset</a>
            @endif
        </form>

        <div data-live="lead-articles-list" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
            @if($articles->count() === 0)
                <div class="p-12 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">No articles in this stage.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Code</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Title</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Writer</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Stage</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Wait time</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Deadline</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($articles as $a)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-950/50 transition-colors cursor-pointer"
                                onclick="window.location='{{ route('lead.articles.show', $a) }}'">
                                <td class="px-4 py-3 text-xs font-mono text-gray-500 dark:text-gray-400">{{ $a->article_code }}</td>
                                <td class="px-4 py-3">
                                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ $a->title }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $a->client?->name ?? '—' }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $a->techWriter?->name ?? '—' }}</td>
                                <td class="px-4 py-3"><x-stage-badge :stage="$a->current_stage" /></td>
                                <td class="px-4 py-3 text-xs">
                                    @php $waitDays = (int) ($a->stage_entered_at?->diffInDays(now()) ?? 0); @endphp
                                    @if($waitDays >= 3)
                                        <span class="text-rose-600 dark:text-rose-400">{{ $waitDays }}d</span>
                                    @elseif($waitDays >= 1)
                                        <span class="text-amber-600 dark:text-amber-400">{{ $waitDays }}d</span>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">{{ $a->stage_entered_at?->diffForHumans() ?? '—' }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($a->deadline)
                                        @php $days = $a->days_until_deadline; @endphp
                                        @if($days < 0)
                                            <span class="text-rose-600 dark:text-rose-400">{{ $a->deadline->format('M j') }} ({{ abs($days) }}d late)</span>
                                        @elseif($days === 0)
                                            <span class="text-rose-600 dark:text-rose-400">Today</span>
                                        @else
                                            <span class="text-gray-700 dark:text-gray-300">{{ $a->deadline->format('M j') }}</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
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
