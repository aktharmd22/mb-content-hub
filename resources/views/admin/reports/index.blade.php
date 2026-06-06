<x-app-layout>
    <x-slot name="header">Reports & history</x-slot>
    <x-slot name="title">Reports</x-slot>

    @php
        $isMonthView = $selectedMonth !== null;

        // Chart max values — at least 1 to avoid div-by-zero, and we'll add a min-height for empty bars
        if ($isMonthView) {
            $maxDaily = max(1, collect($dailyBreakdown)->max(fn ($r) => max($r['submitted'], $r['published'])));
        } else {
            $maxMonthly = max(1, collect($monthly)->max(fn ($r) => max($r['submitted'], $r['published'])));
            $maxWeekly  = max(1, collect($weekly)->max(fn ($r) => max($r['submitted'], $r['published'])));
        }
        $maxYearly  = max(1, collect($yearly)->max(fn ($r) => max($r['submitted'], $r['published'])));
        $maxViral   = max(1, collect($viralMonthly)->max(fn ($r) => max($r['created'], $r['completed'])));
        $maxType    = max(1, collect($typeMix)->max('total'));

        $scopeLabel = $isMonthView ? $selectedMonth->format('F Y') : 'All time';
    @endphp

    <div class="p-6 max-w-7xl">

        {{-- Header with month filter --}}
        <div class="bg-ink-850 border border-ink-700 rounded-2xl p-5 mb-6">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase tracking-wider bg-indigo-500/15 text-indigo-300 border border-indigo-500/30">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 014-4h4M9 17h6M9 17H5a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-4"/></svg>
                            Reports
                        </span>
                        @if($isMonthView)
                            <span class="text-xs text-gray-500">·</span>
                            <span class="text-xs text-gray-400">Viewing: <span class="text-gray-200 font-medium">{{ $scopeLabel }}</span></span>
                        @endif
                    </div>
                    <h2 class="text-xl font-semibold text-gray-100">Reports &amp; history</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ $isMonthView ? 'Stats and breakdowns for the selected month.' : 'Lifetime stats and time-series breakdowns.' }}</p>
                </div>

                {{-- Month filter --}}
                <form method="GET" class="flex items-center gap-2 flex-shrink-0">
                    <label for="month-filter" class="text-xs text-gray-400 font-medium">Month:</label>
                    <select id="month-filter" name="month" onchange="this.form.submit()"
                            class="px-3 py-2 text-sm bg-ink-900 border border-ink-700 rounded-lg text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 min-w-[180px]">
                        <option value="">All time (lifetime)</option>
                        @foreach($availableMonths as $m)
                            <option value="{{ $m['value'] }}" @selected($selectedMonth && $selectedMonth->format('Y-m') === $m['value'])>
                                {{ $m['label'] }} ({{ $m['count'] }})
                            </option>
                        @endforeach
                    </select>
                    @if($isMonthView)
                        <a href="{{ route('admin.reports.index') }}"
                           class="inline-flex items-center justify-center w-9 h-9 text-gray-400 hover:text-gray-100 hover:bg-ink-700 rounded-lg transition-colors" title="Clear filter">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                </form>
            </div>
        </div>

        {{-- KPI strip — compact modern tiles --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
            <div class="bg-ink-850 border border-ink-700 rounded-xl p-4 relative overflow-hidden">
                <div class="absolute top-2 right-2 w-7 h-7 rounded-lg bg-indigo-500/15 text-indigo-300 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Submitted</p>
                <p class="text-2xl font-bold text-gray-100 mt-1.5">{{ number_format($totals['articles_submitted']) }}</p>
                <p class="text-[10px] text-gray-500 mt-0.5">articles</p>
            </div>
            <div class="bg-ink-850 border border-ink-700 rounded-xl p-4 relative overflow-hidden">
                <div class="absolute top-2 right-2 w-7 h-7 rounded-lg bg-emerald-500/15 text-emerald-300 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Published</p>
                <p class="text-2xl font-bold text-gray-100 mt-1.5">{{ number_format($totals['articles_published']) }}</p>
                <p class="text-[10px] text-gray-500 mt-0.5">articles</p>
            </div>
            <div class="bg-ink-850 border border-ink-700 rounded-xl p-4 relative overflow-hidden">
                <div class="absolute top-2 right-2 w-7 h-7 rounded-lg bg-blue-500/15 text-blue-300 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Clients</p>
                <p class="text-2xl font-bold text-gray-100 mt-1.5">{{ number_format($totals['clients_served']) }}</p>
                <p class="text-[10px] text-gray-500 mt-0.5">distinct</p>
            </div>
            <div class="bg-ink-850 border border-ink-700 rounded-xl p-4 relative overflow-hidden">
                <div class="absolute top-2 right-2 w-7 h-7 rounded-lg bg-pink-500/15 text-pink-300 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Viral</p>
                <p class="text-2xl font-bold text-gray-100 mt-1.5">{{ number_format($totals['viral_packages']) }}</p>
                <p class="text-[10px] text-gray-500 mt-0.5">{{ $totals['viral_delivered'] }} delivered</p>
            </div>
            <div class="bg-ink-850 border border-ink-700 rounded-xl p-4 relative overflow-hidden">
                <div class="absolute top-2 right-2 w-7 h-7 rounded-lg bg-gray-500/15 text-gray-300 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/></svg>
                </div>
                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Users</p>
                <p class="text-2xl font-bold text-gray-100 mt-1.5">{{ number_format($totals['total_users']) }}</p>
                <p class="text-[10px] text-gray-500 mt-0.5">active</p>
            </div>
            <div class="bg-ink-850 border border-ink-700 rounded-xl p-4 relative overflow-hidden">
                <div class="absolute top-2 right-2 w-7 h-7 rounded-lg bg-violet-500/15 text-violet-300 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Events</p>
                <p class="text-2xl font-bold text-gray-100 mt-1.5">{{ number_format($transitionStats['total_transitions']) }}</p>
                <p class="text-[10px] text-gray-500 mt-0.5">transitions</p>
            </div>
        </div>

        {{-- Time-series chart --}}
        @if($isMonthView)
            {{-- Daily breakdown for selected month --}}
            <div class="bg-ink-850 border border-ink-700 rounded-2xl p-5 mb-6">
                <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-100">Daily breakdown</h3>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $selectedMonth->format('F Y') }} · day by day</p>
                    </div>
                    <div class="flex items-center gap-4 text-xs">
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-indigo-500"></span><span class="text-gray-400">Submitted</span></span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500"></span><span class="text-gray-400">Published</span></span>
                    </div>
                </div>
                <div class="flex items-end gap-1 h-44">
                    @foreach($dailyBreakdown as $d)
                        @php
                            $subH = $d['submitted'] > 0 ? max(4, ($d['submitted'] / $maxDaily) * 100) : 0;
                            $pubH = $d['published'] > 0 ? max(4, ($d['published'] / $maxDaily) * 100) : 0;
                        @endphp
                        <div class="flex-1 flex flex-col items-center gap-1 group">
                            <div class="flex-1 w-full flex items-end gap-px relative">
                                @if($d['submitted'] > 0)
                                    <div class="flex-1 bg-indigo-500 hover:bg-indigo-400 rounded-t transition-colors" style="height: {{ $subH }}%" title="Day {{ $d['period'] }}: {{ $d['submitted'] }} submitted"></div>
                                @else
                                    <div class="flex-1"></div>
                                @endif
                                @if($d['published'] > 0)
                                    <div class="flex-1 bg-emerald-500 hover:bg-emerald-400 rounded-t transition-colors" style="height: {{ $pubH }}%" title="Day {{ $d['period'] }}: {{ $d['published'] }} published"></div>
                                @else
                                    <div class="flex-1"></div>
                                @endif
                            </div>
                            <p class="text-[9px] text-gray-600 group-hover:text-gray-300 tabular-nums">{{ $d['period'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            {{-- 12-month chart (lifetime view) --}}
            <div class="bg-ink-850 border border-ink-700 rounded-2xl p-5 mb-6">
                <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-100">Articles per month</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Last 12 months · click a month above to drill in</p>
                    </div>
                    <div class="flex items-center gap-4 text-xs">
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-indigo-500"></span><span class="text-gray-400">Submitted</span></span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500"></span><span class="text-gray-400">Published</span></span>
                        <a href="{{ route('admin.reports.export', ['kind' => 'monthly']) }}" class="text-indigo-400 hover:underline">Export CSV</a>
                    </div>
                </div>
                <div class="flex items-end gap-2 h-48 overflow-x-auto">
                    @foreach($monthly as $m)
                        @php
                            $monthValue = \Carbon\Carbon::parse($m['period'])->format('Y-m');
                            $subH = $m['submitted'] > 0 ? max(8, ($m['submitted'] / $maxMonthly) * 100) : 0;
                            $pubH = $m['published'] > 0 ? max(8, ($m['published'] / $maxMonthly) * 100) : 0;
                        @endphp
                        <a href="{{ route('admin.reports.index', ['month' => $monthValue]) }}"
                           class="flex-1 min-w-[50px] flex flex-col items-center gap-1 hover:bg-ink-800/50 rounded p-1 -m-1 transition-colors group">
                            <div class="flex-1 w-full flex items-end gap-1">
                                <div class="flex-1 bg-indigo-500 group-hover:bg-indigo-400 rounded-t transition-colors {{ $m['submitted'] === 0 ? 'bg-ink-700/40' : '' }}"
                                     style="height: {{ max(2, $subH) }}%"
                                     title="{{ $m['submitted'] }} submitted"></div>
                                <div class="flex-1 bg-emerald-500 group-hover:bg-emerald-400 rounded-t transition-colors {{ $m['published'] === 0 ? 'bg-ink-700/40' : '' }}"
                                     style="height: {{ max(2, $pubH) }}%"
                                     title="{{ $m['published'] }} published"></div>
                            </div>
                            <p class="text-[10px] text-gray-500 group-hover:text-gray-300 whitespace-nowrap">{{ $m['period'] }}</p>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- 12-week chart --}}
            <div class="bg-ink-850 border border-ink-700 rounded-2xl p-5 mb-6">
                <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-100">Articles per week</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Last 12 weeks</p>
                    </div>
                    <a href="{{ route('admin.reports.export', ['kind' => 'weekly']) }}" class="text-xs text-indigo-400 hover:underline">Export CSV</a>
                </div>
                <div class="flex items-end gap-2 h-40 overflow-x-auto">
                    @foreach($weekly as $w)
                        @php
                            $subH = $w['submitted'] > 0 ? max(6, ($w['submitted'] / $maxWeekly) * 100) : 0;
                            $pubH = $w['published'] > 0 ? max(6, ($w['published'] / $maxWeekly) * 100) : 0;
                        @endphp
                        <div class="flex-1 min-w-[60px] flex flex-col items-center gap-1">
                            <div class="flex-1 w-full flex items-end gap-1">
                                <div class="flex-1 bg-indigo-500 hover:bg-indigo-400 rounded-t transition-colors {{ $w['submitted'] === 0 ? 'bg-ink-700/40' : '' }}"
                                     style="height: {{ max(2, $subH) }}%"
                                     title="{{ $w['submitted'] }} submitted"></div>
                                <div class="flex-1 bg-emerald-500 hover:bg-emerald-400 rounded-t transition-colors {{ $w['published'] === 0 ? 'bg-ink-700/40' : '' }}"
                                     style="height: {{ max(2, $pubH) }}%"
                                     title="{{ $w['published'] }} published"></div>
                            </div>
                            <p class="text-[9px] text-gray-500 whitespace-nowrap">{{ $w['period'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Breakdown grid: yearly + type mix --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-ink-850 border border-ink-700 rounded-2xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-100">Per year</h3>
                        <p class="text-[11px] text-gray-500 mt-0.5">Lifetime aggregate</p>
                    </div>
                    <a href="{{ route('admin.reports.export', ['kind' => 'yearly']) }}" class="text-xs text-indigo-400 hover:underline">CSV</a>
                </div>
                @if(empty($yearly))
                    <p class="text-xs text-gray-500">No data yet.</p>
                @else
                    <table class="w-full text-sm">
                        <thead class="border-b border-ink-700">
                            <tr>
                                <th class="text-left py-2 text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Year</th>
                                <th class="text-right py-2 text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Submitted</th>
                                <th class="text-right py-2 text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Published</th>
                                <th class="text-right py-2 text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-700">
                            @foreach($yearly as $y)
                                <tr>
                                    <td class="py-2 text-gray-100 font-semibold">{{ $y['year'] }}</td>
                                    <td class="py-2 text-right text-gray-300 tabular-nums">{{ $y['submitted'] }}</td>
                                    <td class="py-2 text-right text-emerald-300 tabular-nums">{{ $y['published'] }}</td>
                                    <td class="py-2 text-right text-gray-400 text-xs tabular-nums">{{ $y['submitted'] > 0 ? round(($y['published']/$y['submitted'])*100) : 0 }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="bg-ink-850 border border-ink-700 rounded-2xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-100">Article type mix</h3>
                        <p class="text-[11px] text-gray-500 mt-0.5">{{ $scopeLabel }}</p>
                    </div>
                </div>
                @if(empty($typeMix))
                    <p class="text-xs text-gray-500">No data for this period.</p>
                @else
                    <ul class="space-y-3">
                        @foreach($typeMix as $t)
                            <li>
                                <div class="flex items-baseline justify-between mb-1.5">
                                    <p class="text-sm text-gray-200 font-medium">{{ $t['name'] }}</p>
                                    <p class="text-xs text-gray-500 tabular-nums">{{ $t['total'] }} · <span class="text-emerald-400">{{ $t['published'] }} pub</span></p>
                                </div>
                                <div class="h-2 bg-ink-900 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-indigo-500 to-violet-500" style="width: {{ ($t['total'] / $maxType) * 100 }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- Top clients --}}
        <div class="bg-ink-850 border border-ink-700 rounded-2xl p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-100">Top clients</h3>
                    <p class="text-[11px] text-gray-500 mt-0.5">{{ $scopeLabel }}</p>
                </div>
                <a href="{{ route('admin.reports.export', ['kind' => 'clients']) }}" class="text-xs text-indigo-400 hover:underline">Export CSV</a>
            </div>
            @if(empty($topClients))
                <p class="text-xs text-gray-500">No clients with articles in this period.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[480px]">
                        <thead class="border-b border-ink-700">
                            <tr>
                                <th class="text-left py-2 text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Client</th>
                                <th class="text-left py-2 text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Company</th>
                                <th class="text-right py-2 text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Articles</th>
                                <th class="text-right py-2 text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Published</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-700">
                            @foreach($topClients as $c)
                                <tr class="hover:bg-ink-900/40 transition-colors">
                                    <td class="py-2.5 text-gray-100 font-medium">{{ $c['name'] }}</td>
                                    <td class="py-2.5 text-gray-400 text-xs">{{ $c['company'] ?: '—' }}</td>
                                    <td class="py-2.5 text-right text-gray-300 tabular-nums">{{ $c['total_articles'] }}</td>
                                    <td class="py-2.5 text-right text-emerald-300 tabular-nums">{{ $c['published'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Performance grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-ink-850 border border-ink-700 rounded-2xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-100">Sales reps</h3>
                        <p class="text-[11px] text-gray-500 mt-0.5">{{ $scopeLabel }}</p>
                    </div>
                    <a href="{{ route('admin.reports.export', ['kind' => 'sales']) }}" class="text-xs text-indigo-400 hover:underline">CSV</a>
                </div>
                @if(empty($salesPerf))
                    <p class="text-xs text-gray-500">No sales reps yet.</p>
                @else
                    <table class="w-full text-sm">
                        <thead class="border-b border-ink-700">
                            <tr>
                                <th class="text-left py-2 text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Rep</th>
                                <th class="text-right py-2 text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Sub</th>
                                <th class="text-right py-2 text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Pub</th>
                                <th class="text-right py-2 text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Success</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-700">
                            @foreach($salesPerf as $s)
                                <tr>
                                    <td class="py-2.5 text-gray-100 font-medium">{{ $s['name'] }}</td>
                                    <td class="py-2.5 text-right text-gray-300 tabular-nums">{{ $s['submitted'] }}</td>
                                    <td class="py-2.5 text-right text-emerald-300 tabular-nums">{{ $s['published'] }}</td>
                                    <td class="py-2.5 text-right text-xs tabular-nums {{ $s['success_rate'] >= 70 ? 'text-emerald-400' : ($s['success_rate'] >= 40 ? 'text-amber-400' : 'text-gray-500') }}">{{ $s['success_rate'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="bg-ink-850 border border-ink-700 rounded-2xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-100">Tech writers</h3>
                        <p class="text-[11px] text-gray-500 mt-0.5">{{ $scopeLabel }}</p>
                    </div>
                    <a href="{{ route('admin.reports.export', ['kind' => 'writers']) }}" class="text-xs text-indigo-400 hover:underline">CSV</a>
                </div>
                @if(empty($writerPerf))
                    <p class="text-xs text-gray-500">No writers yet.</p>
                @else
                    <table class="w-full text-sm">
                        <thead class="border-b border-ink-700">
                            <tr>
                                <th class="text-left py-2 text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Writer</th>
                                <th class="text-right py-2 text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Assigned</th>
                                <th class="text-right py-2 text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Pub</th>
                                <th class="text-right py-2 text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Done</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-700">
                            @foreach($writerPerf as $w)
                                <tr>
                                    <td class="py-2.5 text-gray-100 font-medium">{{ $w['name'] }}</td>
                                    <td class="py-2.5 text-right text-gray-300 tabular-nums">{{ $w['assigned'] }}</td>
                                    <td class="py-2.5 text-right text-emerald-300 tabular-nums">{{ $w['published'] }}</td>
                                    <td class="py-2.5 text-right text-xs tabular-nums {{ $w['completion_rate'] >= 70 ? 'text-emerald-400' : ($w['completion_rate'] >= 40 ? 'text-amber-400' : 'text-gray-500') }}">{{ $w['completion_rate'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- Viral packages chart (lifetime, doesn't depend on month filter) --}}
        @unless($isMonthView)
            <div class="bg-ink-850 border border-ink-700 rounded-2xl p-5 mb-6">
                <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-100">Viral packages per month</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Last 12 months</p>
                    </div>
                    <div class="flex items-center gap-4 text-xs">
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-pink-500"></span><span class="text-gray-400">Created</span></span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500"></span><span class="text-gray-400">Delivered</span></span>
                        <a href="{{ route('admin.reports.export', ['kind' => 'viral']) }}" class="text-indigo-400 hover:underline">Export CSV</a>
                    </div>
                </div>
                <div class="flex items-end gap-2 h-40 overflow-x-auto">
                    @foreach($viralMonthly as $v)
                        @php
                            $crH = $v['created']   > 0 ? max(6, ($v['created']   / $maxViral) * 100) : 0;
                            $cmH = $v['completed'] > 0 ? max(6, ($v['completed'] / $maxViral) * 100) : 0;
                        @endphp
                        <div class="flex-1 min-w-[50px] flex flex-col items-center gap-1">
                            <div class="flex-1 w-full flex items-end gap-1">
                                <div class="flex-1 bg-pink-500 hover:bg-pink-400 rounded-t transition-colors {{ $v['created']   === 0 ? 'bg-ink-700/40' : '' }}" style="height: {{ max(2, $crH) }}%" title="Created: {{ $v['created'] }}"></div>
                                <div class="flex-1 bg-emerald-500 hover:bg-emerald-400 rounded-t transition-colors {{ $v['completed'] === 0 ? 'bg-ink-700/40' : '' }}" style="height: {{ max(2, $cmH) }}%" title="Delivered: {{ $v['completed'] }}"></div>
                            </div>
                            <p class="text-[10px] text-gray-500 whitespace-nowrap">{{ $v['period'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endunless

        {{-- Stage transition counters --}}
        <div class="bg-ink-850 border border-ink-700 rounded-2xl p-5">
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-gray-100">Stage transitions</h3>
                <p class="text-[11px] text-gray-500 mt-0.5">{{ $scopeLabel }}</p>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                <div class="bg-ink-900/60 border border-ink-700 rounded-lg p-3">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Assignments</p>
                    <p class="text-xl font-bold text-indigo-300 mt-1 tabular-nums">{{ number_format($transitionStats['total_assignments']) }}</p>
                </div>
                <div class="bg-ink-900/60 border border-ink-700 rounded-lg p-3">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Corrections</p>
                    <p class="text-xl font-bold text-amber-300 mt-1 tabular-nums">{{ number_format($transitionStats['total_corrections']) }}</p>
                </div>
                <div class="bg-ink-900/60 border border-ink-700 rounded-lg p-3">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Approvals</p>
                    <p class="text-xl font-bold text-emerald-300 mt-1 tabular-nums">{{ number_format($transitionStats['total_approvals']) }}</p>
                </div>
                <div class="bg-ink-900/60 border border-ink-700 rounded-lg p-3">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Publications</p>
                    <p class="text-xl font-bold text-violet-300 mt-1 tabular-nums">{{ number_format($transitionStats['total_published']) }}</p>
                </div>
                <div class="bg-ink-900/60 border border-ink-700 rounded-lg p-3">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Total events</p>
                    <p class="text-xl font-bold text-gray-200 mt-1 tabular-nums">{{ number_format($transitionStats['total_transitions']) }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
