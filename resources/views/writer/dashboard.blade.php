<x-app-layout>
    <x-slot name="header">My assignments</x-slot>
    <x-slot name="title">Dashboard</x-slot>

    <div class="p-6">

        <div class="mb-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Welcome back, {{ auth()->user()->name }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Your queue and what's due soon.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <x-stat-card label="Active assignments" :value="$stats['active']" color="indigo"
                icon='<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>' />
            <x-stat-card label="Due this week" :value="$stats['due_this_week']" :color="$stats['due_this_week'] > 0 ? 'amber' : 'gray'"
                icon='<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' />
            <x-stat-card label="Completed this month" :value="$stats['completed_this_month']" color="emerald"
                icon='<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' />
            <x-stat-card label="Avg days per article" :value="$stats['avg_days'] !== null ? $stats['avg_days'] : '—'" color="violet"
                icon='<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>' />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- My queue -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">My queue</h3>
                    <a href="{{ route('writer.articles.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">View all</a>
                </div>
                @if($queue->isEmpty())
                    <div class="px-5 py-12 text-center">
                        <svg class="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">No active assignments. Time for a coffee.</p>
                    </div>
                @else
                    <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($queue as $a)
                            <li>
                                <a href="{{ route('writer.articles.show', $a) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-950/50 transition-colors">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 mb-0.5">
                                            <span class="text-xs font-mono text-gray-400">{{ $a->article_code }}</span>
                                            <x-stage-badge :stage="$a->current_stage" />
                                            @if($a->priority === 'high')
                                                <span class="text-xs text-rose-600 dark:text-rose-400">â— High</span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-gray-900 dark:text-gray-100 truncate">{{ $a->title }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $a->client?->name ?? '—' }}</p>
                                    </div>
                                    @if($a->deadline)
                                        @php $days = $a->days_until_deadline; @endphp
                                        @if($days < 0)
                                            <span class="text-xs font-medium text-rose-600 dark:text-rose-400 whitespace-nowrap">{{ abs($days) }}d late</span>
                                        @elseif($days === 0)
                                            <span class="text-xs font-medium text-rose-600 dark:text-rose-400 whitespace-nowrap">Due today</span>
                                        @elseif($days <= 2)
                                            <span class="text-xs font-medium text-amber-600 dark:text-amber-400 whitespace-nowrap">{{ $days }}d left</span>
                                        @elseif($days <= 7)
                                            <span class="text-xs font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ $days }}d left</span>
                                        @else
                                            <span class="text-xs text-gray-400 whitespace-nowrap">{{ $a->deadline->format('M j') }}</span>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-400 whitespace-nowrap">No deadline</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <!-- Recently completed -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Recently completed</h3>
                </div>
                @if($recentlyCompleted->isEmpty())
                    <div class="px-5 py-12 text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">No completed articles yet.</p>
                    </div>
                @else
                    <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($recentlyCompleted as $a)
                            <li>
                                <a href="{{ route('writer.articles.show', $a) }}" class="block px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-950/50 transition-colors">
                                    <div class="flex items-center justify-between gap-2 mb-0.5">
                                        <span class="text-xs font-mono text-gray-400">{{ $a->article_code }}</span>
                                        <x-stage-badge :stage="$a->current_stage" />
                                    </div>
                                    <p class="text-sm text-gray-900 dark:text-gray-100 truncate">{{ $a->title }}</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <!-- New uploads from sales (visible to all tech team) -->
        <div class="card mt-6">
            <div class="px-5 py-3 border-b border-ink-700 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-gray-100">New uploads from sales</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Recently submitted, awaiting assignment.</p>
                </div>
                <a href="{{ route('lead.articles.index', ['stage' => 'inbox']) }}" class="text-xs text-indigo-400 hover:underline">View all</a>
            </div>

            @if($newUploads->isEmpty())
                <div class="px-5 py-12 text-center">
                    <svg class="w-7 h-7 text-gray-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-sm text-gray-500">No new uploads.</p>
                </div>
            @else
                <ul class="divide-y divide-ink-700">
                    @foreach($newUploads as $a)
                        <li>
                            <a href="{{ route('lead.articles.show', $a) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-ink-800/50 transition-colors">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 mb-0.5 flex-wrap">
                                        <span class="text-xs font-mono text-gray-500">{{ $a->article_code }}</span>
                                        @if($a->articleType)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-violet-500/10 text-violet-300 border border-violet-500/20">{{ $a->articleType->name }}</span>
                                        @endif
                                        @if($a->priority === 'high')
                                            <span class="text-xs text-rose-400">● High</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-100 truncate">{{ $a->title }}</p>
                                    <p class="text-xs text-gray-500 truncate">
                                        from {{ $a->salesRep?->name ?? 'unknown' }}{{ $a->client ? ' · ' . $a->client->name : '' }}
                                    </p>
                                </div>
                                <span class="text-xs text-gray-500 whitespace-nowrap">{{ $a->submitted_at?->diffForHumans() ?? '' }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-app-layout>
