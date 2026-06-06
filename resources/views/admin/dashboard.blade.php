<x-app-layout>
    <x-slot name="header">Admin dashboard</x-slot>
    <x-slot name="title">Dashboard</x-slot>

    <div class="p-6">

        <div class="mb-6">
            <h2 class="text-lg font-medium text-gray-100">Welcome back, {{ auth()->user()->name }}</h2>
            <p class="text-sm text-gray-500 mt-1">Pipeline at a glance.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <x-stat-card label="Total active" :value="$stats['total_active']" color="indigo"
                icon='<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>' />
            <x-stat-card label="Due this week" :value="$stats['due_this_week']" :color="$stats['due_this_week'] > 0 ? 'amber' : 'gray'"
                icon='<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' />
            <x-stat-card label="Stuck > 3 days" :value="$stats['stuck']" :color="$stats['stuck'] > 0 ? 'rose' : 'gray'"
                icon='<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>' />
            <x-stat-card label="Published this month" :value="$stats['published_this_month']" color="emerald"
                icon='<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' />
        </div>

        <!-- Pipeline grid -->
        <div class="card p-5 mb-6">
            <h3 class="text-sm font-medium text-gray-100 mb-4">Pipeline</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach($pipeline as $row)
                    @php
                        $color = $row['stage']->color();
                        $accentClass = match ($color) {
                            'gray'    => 'text-gray-400',
                            'blue'    => 'text-blue-300',
                            'indigo'  => 'text-indigo-300',
                            'amber'   => 'text-amber-300',
                            'pink'    => 'text-pink-300',
                            'orange'  => 'text-orange-300',
                            'emerald' => 'text-emerald-300',
                            'green'   => 'text-green-300',
                            default   => 'text-gray-400',
                        };
                        $borderClass = match ($color) {
                            'gray'    => 'hover:border-gray-500/40',
                            'blue'    => 'hover:border-blue-500/40',
                            'indigo'  => 'hover:border-indigo-500/40',
                            'amber'   => 'hover:border-amber-500/40',
                            'pink'    => 'hover:border-pink-500/40',
                            'orange'  => 'hover:border-orange-500/40',
                            'emerald' => 'hover:border-emerald-500/40',
                            'green'   => 'hover:border-green-500/40',
                            default   => 'hover:border-gray-500/40',
                        };
                    @endphp
                    <a href="{{ route('admin.articles.index', ['stage' => $row['stage']->value]) }}"
                       class="block p-3 bg-ink-800/40 border border-ink-700 {{ $borderClass }} rounded-lg transition-colors group">
                        <p class="text-xs text-gray-500 group-hover:text-gray-300 transition-colors">{{ $row['stage']->label() }}</p>
                        <p class="text-2xl font-semibold mt-1 {{ $row['count'] > 0 ? $accentClass : 'text-gray-600' }}">{{ $row['count'] }}</p>
                    </a>
                @endforeach
            </div>
        </div>

        @if($stuckArticles->isNotEmpty())
            @php
                // Group stuck articles by stage so admin can see clusters at a glance
                $stuckByStage = $stuckArticles->groupBy(fn ($a) => $a->current_stage->value);

                // Stage owner hint — who needs to take action
                $stageOwner = [
                    'inbox'           => 'Tech team to pick up',
                    'assigned'        => 'Tech writer to start',
                    'in_progress'     => 'Tech writer working',
                    'internal_review' => 'Tech lead to review',
                    'client_approval' => 'Sales to confirm with client',
                    'revisions'       => 'Tech writer to revise',
                    'approved'        => 'Tech team to publish',
                    'published'       => '—',
                ];

                $severity = function ($days) {
                    if ($days >= 14) return ['label' => 'Critical', 'color' => 'bg-rose-500/20 text-rose-300 border-rose-500/40', 'days' => 'text-rose-300'];
                    if ($days >= 7)  return ['label' => 'High',     'color' => 'bg-amber-500/20 text-amber-300 border-amber-500/40', 'days' => 'text-amber-300'];
                    return ['label' => 'Moderate', 'color' => 'bg-yellow-500/15 text-yellow-200 border-yellow-500/30', 'days' => 'text-yellow-200'];
                };
            @endphp

            <div class="bg-ink-850 border border-ink-700 rounded-xl overflow-hidden mb-6">
                {{-- Header --}}
                <div class="flex items-center justify-between gap-3 px-5 py-4 bg-rose-500/5 border-b border-rose-500/20">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-rose-500/15 text-rose-300 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-100">{{ $stuckArticles->count() }} article{{ $stuckArticles->count() > 1 ? 's' : '' }} stuck &gt; 3 days</p>
                            <p class="text-xs text-gray-500 mt-0.5">Grouped by stage — nudge the right team to unblock.</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.articles.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300 whitespace-nowrap">View all articles →</a>
                </div>

                {{-- Stage groups --}}
                <div class="divide-y divide-ink-700">
                    @foreach($stuckByStage as $stageValue => $stageArticles)
                        @php $stage = \App\Enums\ArticleStage::from($stageValue); @endphp
                        <div class="px-5 py-3">
                            <div class="flex items-center justify-between gap-2 mb-2.5 flex-wrap">
                                <div class="flex items-center gap-2">
                                    <x-stage-badge :stage="$stage" />
                                    <span class="text-xs text-gray-500">{{ $stageArticles->count() }} {{ Str::plural('article', $stageArticles->count()) }}</span>
                                </div>
                                <span class="text-[11px] text-gray-500 italic">{{ $stageOwner[$stageValue] ?? '—' }}</span>
                            </div>

                            <ul class="space-y-1.5">
                                @foreach($stageArticles->sortByDesc(fn ($a) => $a->stage_entered_at->diffInDays(now())) as $a)
                                    @php
                                        $days = (int) $a->stage_entered_at->diffInDays(now());
                                        $sev  = $severity($days);
                                    @endphp
                                    <li>
                                        <a href="{{ route('admin.articles.index', ['q' => $a->article_code]) }}"
                                           class="group flex items-center justify-between gap-3 px-3 py-2 bg-ink-900/40 hover:bg-ink-900 border border-ink-700/50 rounded-lg transition-colors">
                                            <div class="min-w-0 flex-1 flex items-center gap-3">
                                                <span class="text-[11px] font-mono text-gray-500">{{ $a->article_code }}</span>
                                                <span class="text-sm text-gray-200 truncate group-hover:text-white">{{ $a->title }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 flex-shrink-0">
                                                <span class="text-xs font-semibold {{ $sev['days'] }} tabular-nums">{{ $days }}d</span>
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold uppercase tracking-wider border {{ $sev['color'] }}">{{ $sev['label'] }}</span>
                                            </div>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="card">
            <div class="px-5 py-3 border-b border-ink-700 flex items-center justify-between">
                <h3 class="text-sm font-medium text-gray-100">Recent activity</h3>
                <a href="{{ route('admin.activity.index') }}" class="text-xs text-indigo-400 hover:underline">See all</a>
            </div>
            @if($recentActivity->isEmpty())
                <div class="px-5 py-12 text-center">
                    <p class="text-sm text-gray-500">No activity yet.</p>
                </div>
            @else
                <ul class="divide-y divide-ink-700">
                    @foreach($recentActivity as $h)
                        <li class="px-5 py-3 flex items-center justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-mono text-gray-500">{{ $h->article?->article_code ?? '—' }}</span>
                                    @if($h->from_stage)
                                        <x-stage-badge :stage="$h->from_stage" />
                                        <svg class="w-3 h-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                        </svg>
                                    @endif
                                    <x-stage-badge :stage="$h->to_stage" />
                                </div>
                                <p class="text-xs text-gray-500 mt-1 truncate">
                                    {{ $h->article?->title ?? '(deleted)' }} — {{ $h->changedBy?->name ?? 'system' }}
                                </p>
                            </div>
                            <span class="text-xs text-gray-500 whitespace-nowrap">{{ $h->changed_at->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE, short: true) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-app-layout>
