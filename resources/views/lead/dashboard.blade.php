<x-app-layout>
    <x-slot name="header">Tech lead dashboard</x-slot>
    <x-slot name="title">Dashboard</x-slot>

    <div class="p-6" data-live="lead-dashboard">

        <div class="mb-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Welcome back, {{ auth()->user()->name }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Articles waiting for your review and how the team is doing.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <x-stat-card label="Pending reviews" :value="$stats['pending_reviews']" :color="$stats['pending_reviews'] > 0 ? 'amber' : 'gray'"
                icon='<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>' />
            <x-stat-card label="Approved this week" :value="$stats['approved_this_week']" color="emerald"
                icon='<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' />
            <x-stat-card label="Sent for revision (week)" :value="$stats['sent_for_revision_this_week']" color="orange"
                icon='<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>' />
            <x-stat-card label="Team active" :value="$stats['team_active']" color="violet"
                icon='<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>' />
        </div>

        @if($stuck->isNotEmpty())
            <div class="bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900 rounded-lg p-4 mb-6">
                <div class="flex items-start gap-3">
                    <svg class="w-4 h-4 text-rose-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-rose-900 dark:text-rose-200">{{ $stuck->count() }} article{{ $stuck->count() > 1 ? 's' : '' }} stuck &gt; 3 days</p>
                        <ul class="mt-2 space-y-1">
                            @foreach($stuck as $a)
                                <li class="text-sm">
                                    <a href="{{ route('lead.articles.show', $a) }}" class="text-rose-700 dark:text-rose-300 hover:underline">
                                        {{ $a->article_code }} · {{ $a->title }}
                                    </a>
                                    <span class="text-xs text-rose-600 dark:text-rose-400">— {{ $a->current_stage->label() }} for {{ (int) $a->stage_entered_at->diffInDays(now()) }} days</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Review queue -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Review queue</h3>
                    <a href="{{ route('lead.articles.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">See all</a>
                </div>
                @if($reviewQueue->isEmpty())
                    <div class="px-5 py-12 text-center">
                        <svg class="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Review queue is empty.</p>
                    </div>
                @else
                    <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($reviewQueue as $a)
                            <li>
                                <a href="{{ route('lead.articles.show', $a) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-950/50 transition-colors">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 mb-0.5">
                                            <span class="text-xs font-mono text-gray-400">{{ $a->article_code }}</span>
                                            @if($a->priority === 'high')
                                                <span class="text-xs text-rose-600 dark:text-rose-400">â— High</span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-gray-900 dark:text-gray-100 truncate">{{ $a->title }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            {{ $a->client?->displayName() ?? '—' }} · {{ $a->techWriter?->name ?? 'unassigned' }}
                                        </p>
                                    </div>
                                    @php $waitDays = (int) ($a->stage_entered_at?->diffInDays(now()) ?? 0); @endphp
                                    @if($waitDays >= 2)
                                        <span class="text-xs font-medium text-amber-600 dark:text-amber-400 whitespace-nowrap">{{ $waitDays }}d wait</span>
                                    @else
                                        <span class="text-xs text-gray-400 whitespace-nowrap">{{ $a->stage_entered_at?->diffForHumans() ?? '—' }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <!-- Team workload -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Team workload</h3>
                    <a href="{{ route('lead.team.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Details</a>
                </div>
                @if($teamWorkload->isEmpty())
                    <div class="px-5 py-12 text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">No tech writers yet.</p>
                    </div>
                @else
                    <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($teamWorkload as $u)
                            <li class="px-5 py-3">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <div class="w-6 h-6 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center flex-shrink-0">
                                            <span class="text-xs font-medium text-indigo-700 dark:text-indigo-300">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                                        </div>
                                        <span class="text-sm text-gray-900 dark:text-gray-100 truncate">{{ $u->name }}</span>
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $u->total_active }} active</span>
                                </div>
                                <div class="mt-1.5 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ $u->assigned_count }} assigned</span>
                                    <span>·</span>
                                    <span>{{ $u->in_progress_count }} in progress</span>
                                    @if($u->revisions_count > 0)
                                        <span>·</span>
                                        <span class="text-orange-600 dark:text-orange-400">{{ $u->revisions_count }} revising</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
