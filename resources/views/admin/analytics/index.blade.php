<x-app-layout>
    <x-slot name="header">Analytics</x-slot>
    <x-slot name="title">Analytics</x-slot>

    <div class="p-6 space-y-6">

        <div>
            <h2 class="text-lg font-medium text-gray-100">Analytics</h2>
            <p class="text-sm text-gray-500 mt-0.5">Pipeline health, bottlenecks, and team performance.</p>
        </div>

        <!-- KPI strip -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="card p-4 relative overflow-hidden">
                <div class="absolute -top-8 -right-8 w-24 h-24 rounded-full bg-indigo-500/10 blur-2xl"></div>
                <p class="text-xs text-gray-500 relative">This month</p>
                <p class="text-3xl font-semibold text-gray-100 mt-1 relative">{{ $kpis['this_month'] }}</p>
                @if($kpis['change_pct'] !== null)
                    <p class="text-xs mt-1 relative {{ $kpis['change_pct'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                        {{ $kpis['change_pct'] >= 0 ? '↑' : '↓' }} {{ abs($kpis['change_pct']) }}% vs last month
                    </p>
                @else
                    <p class="text-xs text-gray-600 mt-1 relative">vs last month: —</p>
                @endif
            </div>

            <div class="card p-4 relative overflow-hidden">
                <div class="absolute -top-8 -right-8 w-24 h-24 rounded-full bg-violet-500/10 blur-2xl"></div>
                <p class="text-xs text-gray-500 relative">Avg cycle time</p>
                <p class="text-3xl font-semibold text-gray-100 mt-1 relative">
                    {{ $kpis['avg_cycle_days'] !== null ? $kpis['avg_cycle_days'] : '—' }}<span class="text-base font-normal text-gray-500 ml-1">days</span>
                </p>
                <p class="text-xs text-gray-600 mt-1 relative">submission → published, last 90d</p>
            </div>

            <div class="card p-4 relative overflow-hidden">
                <div class="absolute -top-8 -right-8 w-24 h-24 rounded-full bg-emerald-500/10 blur-2xl"></div>
                <p class="text-xs text-gray-500 relative">Total published</p>
                <p class="text-3xl font-semibold text-gray-100 mt-1 relative">{{ $kpis['total_published'] }}</p>
                <p class="text-xs text-gray-600 mt-1 relative">all time</p>
            </div>

            <div class="card p-4 relative overflow-hidden">
                @php $stuckBg = $kpis['stuck_total'] > 0 ? 'bg-rose-500/10' : 'bg-gray-500/10'; @endphp
                <div class="absolute -top-8 -right-8 w-24 h-24 rounded-full {{ $stuckBg }} blur-2xl"></div>
                <p class="text-xs text-gray-500 relative">Stuck articles</p>
                <p class="text-3xl font-semibold mt-1 relative {{ $kpis['stuck_total'] > 0 ? 'text-rose-400' : 'text-gray-100' }}">
                    {{ $kpis['stuck_total'] }}
                </p>
                <p class="text-xs text-gray-600 mt-1 relative">in stage &gt; 3 days</p>
            </div>
        </div>

        @if(! $bottlenecks->isEmpty())
            <div class="bg-rose-500/10 border border-rose-500/30 rounded-xl p-4 flex items-start gap-3">
                <svg class="w-4 h-4 text-rose-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-rose-200">Pipeline bottlenecks</p>
                    <div class="flex flex-wrap gap-2 mt-2">
                        @foreach($bottlenecks as $stageValue => $count)
                            @php $stage = \App\Enums\ArticleStage::tryFrom($stageValue); @endphp
                            <a href="{{ route('admin.articles.index', ['stage' => $stageValue]) }}"
                               class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-rose-500/15 border border-rose-500/30 rounded-md text-xs">
                                @if($stage)<span class="text-rose-200">{{ $stage->label() }}</span>@else<span class="text-rose-200">{{ $stageValue }}</span>@endif
                                <span class="text-rose-300 font-medium">{{ $count }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Monthly column chart -->
        <div class="card p-5">
            <div class="flex items-baseline justify-between mb-5">
                <h3 class="text-sm font-medium text-gray-100">Submissions per month</h3>
                <span class="text-xs text-gray-500">Last 6 months</span>
            </div>

            @php $maxCount = max(1, max(array_column($articlesPerMonth, 'count'))); @endphp
            <div class="grid grid-cols-6 gap-3 h-44 items-end">
                @foreach($articlesPerMonth as $i => $m)
                    @php
                        $heightPct = max(4, ($m['count'] / $maxCount) * 100);
                        $isLast    = $i === count($articlesPerMonth) - 1;
                    @endphp
                    <div class="flex flex-col items-center justify-end h-full gap-2">
                        <span class="text-xs font-medium {{ $m['count'] > 0 ? 'text-gray-200' : 'text-gray-600' }}">{{ $m['count'] }}</span>
                        <div class="w-full bg-ink-800 rounded-md relative overflow-hidden" style="height: 90%">
                            <div class="absolute bottom-0 left-0 right-0 rounded-md transition-all
                                        {{ $isLast ? 'bg-gradient-to-t from-indigo-600 to-violet-500' : 'bg-gradient-to-t from-indigo-700/60 to-indigo-500/60' }}"
                                 style="height: {{ $heightPct }}%"></div>
                        </div>
                        <span class="text-xs {{ $isLast ? 'text-indigo-400 font-medium' : 'text-gray-500' }}">{{ $m['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Stage timing tiles -->
            <div class="card p-5">
                <h3 class="text-sm font-medium text-gray-100 mb-1">Time spent per stage</h3>
                <p class="text-xs text-gray-500 mb-4">Average days, last 90 days</p>

                @php
                    $maxDays = max(1, collect($avgTimePerStage)->max('avg_days') ?? 1);
                @endphp
                <div class="space-y-3">
                    @foreach($avgTimePerStage as $row)
                        @php
                            $days = $row['avg_days'];
                            $pct  = $days !== null ? min(100, ($days / $maxDays) * 100) : 0;
                            $color = match ($row['stage']->color()) {
                                'gray'    => 'bg-gray-500/40',
                                'blue'    => 'bg-blue-500/50',
                                'indigo'  => 'bg-indigo-500/50',
                                'amber'   => 'bg-amber-500/60',
                                'pink'    => 'bg-pink-500/50',
                                'orange'  => 'bg-orange-500/50',
                                'emerald' => 'bg-emerald-500/50',
                                'green'   => 'bg-green-500/50',
                                default   => 'bg-gray-500/40',
                            };
                        @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <x-stage-badge :stage="$row['stage']" />
                                <span class="text-xs {{ $days !== null ? 'text-gray-200' : 'text-gray-600' }}">
                                    {{ $days !== null ? $days . ' days' : '—' }}
                                </span>
                            </div>
                            @if($days !== null)
                                <div class="h-1 bg-ink-800 rounded-full overflow-hidden">
                                    <div class="h-full {{ $color }} rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Writer leaderboard -->
            <div class="card p-5">
                <h3 class="text-sm font-medium text-gray-100 mb-1">Writer leaderboard</h3>
                <p class="text-xs text-gray-500 mb-4">Last 30 days</p>

                @if($writerPerformance->isEmpty())
                    <p class="text-sm text-gray-500 py-8 text-center">No writers yet.</p>
                @else
                    @php
                        $sorted = $writerPerformance->sortByDesc('completed')->values();
                        $top    = $sorted->max('completed') ?: 1;
                    @endphp
                    <div class="space-y-3">
                        @foreach($sorted as $i => $w)
                            <div class="flex items-center gap-3">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-semibold flex-shrink-0
                                            {{ $i === 0 ? 'bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-lg shadow-amber-500/30'
                                               : ($i === 1 ? 'bg-gray-400/30 text-gray-200'
                                                  : ($i === 2 ? 'bg-orange-500/20 text-orange-300' : 'bg-ink-700 text-gray-500')) }}">
                                    {{ $i + 1 }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <p class="text-sm text-gray-100 truncate">{{ $w->name }}</p>
                                        <div class="flex items-center gap-2 text-xs text-gray-500 flex-shrink-0">
                                            <span class="text-gray-300">{{ $w->completed }} done</span>
                                            @if($w->avg_days !== null)<span>·</span><span>{{ $w->avg_days }}d</span>@endif
                                            @if($w->revision_rate !== null)
                                                @php
                                                    $rateColor = $w->revision_rate >= 50 ? 'text-rose-400' : ($w->revision_rate >= 25 ? 'text-amber-400' : 'text-emerald-400');
                                                @endphp
                                                <span>·</span>
                                                <span class="{{ $rateColor }}">{{ $w->revision_rate }}% rev</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="h-1 bg-ink-800 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-indigo-500 to-violet-500 rounded-full" style="width: {{ ($w->completed / $top) * 100 }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Top clients -->
        <div class="card p-5">
            <div class="flex items-baseline justify-between mb-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-100">Top clients</h3>
                    <p class="text-xs text-gray-500 mt-0.5">By article volume, all-time</p>
                </div>
                <span class="text-xs text-gray-500">Showing {{ $clientVolume->count() }}</span>
            </div>

            @if($clientVolume->isEmpty())
                <p class="text-sm text-gray-500 py-8 text-center">No clients with articles yet.</p>
            @else
                @php $maxClient = max(1, $clientVolume->max('articles_count')); @endphp
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($clientVolume as $c)
                        <div class="flex items-center gap-3 p-3 bg-ink-800/40 border border-ink-700 rounded-lg">
                            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                <span class="text-xs font-medium text-white">{{ strtoupper(substr($c->name, 0, 2)) }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <p class="text-sm text-gray-100 truncate">{{ $c->name }}</p>
                                    <span class="text-xs font-medium text-gray-300 flex-shrink-0">{{ $c->articles_count }}</span>
                                </div>
                                <div class="h-1 bg-ink-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full" style="width: {{ ($c->articles_count / $maxClient) * 100 }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
