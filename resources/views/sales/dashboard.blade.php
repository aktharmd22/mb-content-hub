<x-app-layout>
    <x-slot name="header">Sales dashboard</x-slot>
    <x-slot name="title">Dashboard</x-slot>

    <div class="p-6" data-live="sales-dashboard">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Welcome back, {{ auth()->user()->name }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Your articles in flight and what's awaiting clients.</p>
            </div>
            <a href="{{ route('sales.articles.create') }}"
               class="inline-flex items-center gap-2 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Submit new article
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <x-stat-card label="My active articles" :value="$stats['active']" color="indigo"
                icon='<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>' />
            <x-stat-card label="Pending client approval" :value="$stats['pending_client']" color="pink"
                icon='<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' />
            <x-stat-card label="Approved this month" :value="$stats['approved_this_month']" color="emerald"
                icon='<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' />
            <x-stat-card label="Published this month" :value="$stats['published_this_month']" color="violet"
                icon='<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>' />
        </div>

        @include('partials.viral-overview', ['viral' => $viral, 'viralRole' => $viralRole])

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Recent submissions -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Recent submissions</h3>
                    <a href="{{ route('sales.articles.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">View all</a>
                </div>
                @if($recent->isEmpty())
                    <div class="px-5 py-12 text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">No submissions yet.</p>
                        <a href="{{ route('sales.articles.create') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline mt-2 inline-block">Submit your first</a>
                    </div>
                @else
                    <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($recent as $a)
                            <li>
                                <a href="{{ route('sales.articles.show', $a) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-950/50 transition-colors">
                                    <div class="min-w-0">
                                        <p class="text-sm text-gray-900 dark:text-gray-100 truncate">{{ $a->title }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $a->article_code }} · {{ $a->client?->name ?? '—' }} · {{ $a->submitted_at?->diffForHumans() }}</p>
                                    </div>
                                    <x-stage-badge :stage="$a->current_stage" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <!-- Client follow-up queue -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Awaiting client response</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Follow up with these clients.</p>
                </div>
                @if($needsFollowup->isEmpty())
                    <div class="px-5 py-12 text-center">
                        <svg class="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Nothing to follow up on.</p>
                    </div>
                @else
                    <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($needsFollowup as $a)
                            <li>
                                <a href="{{ route('sales.articles.show', $a) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-950/50 transition-colors">
                                    <div class="min-w-0">
                                        <p class="text-sm text-gray-900 dark:text-gray-100 truncate">{{ $a->title }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $a->client?->name ?? '—' }} · waiting {{ $a->stage_entered_at?->diffForHumans(null, true) ?? 'a while' }}</p>
                                    </div>
                                    @php $days = $a->days_in_stage; @endphp
                                    @if($days >= 3)
                                        <span class="text-xs font-medium text-rose-600 dark:text-rose-400">{{ $days }}d</span>
                                    @elseif($days >= 1)
                                        <span class="text-xs font-medium text-amber-600 dark:text-amber-400">{{ $days }}d</span>
                                    @else
                                        <span class="text-xs text-gray-400">today</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
