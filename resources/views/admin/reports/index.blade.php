<x-app-layout>
    <x-slot name="header">Reports & history</x-slot>
    <x-slot name="title">Reports</x-slot>

    @php
        $maxMonthly = max(1, collect($monthly)->max(fn ($r) => max($r['submitted'], $r['published'])));
        $maxWeekly  = max(1, collect($weekly)->max(fn ($r) => max($r['submitted'], $r['published'])));
        $maxYearly  = max(1, collect($yearly)->max(fn ($r) => max($r['submitted'], $r['published'])));
        $maxViral   = max(1, collect($viralMonthly)->max(fn ($r) => max($r['created'], $r['completed'])));
        $maxType    = max(1, collect($typeMix)->max('total'));
    @endphp

    <div class="p-6 max-w-7xl">

        <div class="mb-6 flex items-start justify-between gap-3 flex-wrap">
            <div>
                <h2 class="text-lg font-medium text-gray-100">Reports &amp; history</h2>
                <p class="text-sm text-gray-500 mt-0.5">Lifetime stats and time-series breakdowns across the whole platform.</p>
            </div>
        </div>

        {{-- KPI strip --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
            <div class="bg-gradient-to-br from-indigo-500/10 to-indigo-500/5 border border-indigo-500/20 rounded-lg p-4">
                <p class="text-[10px] font-medium text-indigo-300 uppercase tracking-wider">Submitted</p>
                <p class="text-2xl font-semibold text-gray-100 mt-1">{{ number_format($totals['articles_submitted']) }}</p>
                <p class="text-[10px] text-gray-500 mt-0.5">articles all-time</p>
            </div>
            <div class="bg-gradient-to-br from-emerald-500/10 to-emerald-500/5 border border-emerald-500/20 rounded-lg p-4">
                <p class="text-[10px] font-medium text-emerald-300 uppercase tracking-wider">Published</p>
                <p class="text-2xl font-semibold text-gray-100 mt-1">{{ number_format($totals['articles_published']) }}</p>
                <p class="text-[10px] text-gray-500 mt-0.5">articles all-time</p>
            </div>
            <div class="bg-ink-850 border border-ink-700 rounded-lg p-4">
                <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">Clients served</p>
                <p class="text-2xl font-semibold text-gray-100 mt-1">{{ number_format($totals['clients_served']) }}</p>
                <p class="text-[10px] text-gray-500 mt-0.5">distinct clients</p>
            </div>
            <div class="bg-gradient-to-br from-pink-500/10 to-pink-500/5 border border-pink-500/20 rounded-lg p-4">
                <p class="text-[10px] font-medium text-pink-300 uppercase tracking-wider">Viral packages</p>
                <p class="text-2xl font-semibold text-gray-100 mt-1">{{ number_format($totals['viral_packages']) }}</p>
                <p class="text-[10px] text-gray-500 mt-0.5">{{ $totals['viral_delivered'] }} delivered</p>
            </div>
            <div class="bg-ink-850 border border-ink-700 rounded-lg p-4">
                <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">Active users</p>
                <p class="text-2xl font-semibold text-gray-100 mt-1">{{ number_format($totals['total_users']) }}</p>
                <p class="text-[10px] text-gray-500 mt-0.5">across all roles</p>
            </div>
            <div class="bg-gradient-to-br from-violet-500/10 to-violet-500/5 border border-violet-500/20 rounded-lg p-4">
                <p class="text-[10px] font-medium text-violet-300 uppercase tracking-wider">Stage events</p>
                <p class="text-2xl font-semibold text-gray-100 mt-1">{{ number_format($transitionStats['total_transitions']) }}</p>
                <p class="text-[10px] text-gray-500 mt-0.5">total transitions</p>
            </div>
        </div>

        {{-- Monthly chart --}}
        <div class="bg-ink-850 border border-ink-700 rounded-xl p-5 mb-6">
            <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-gray-100">Articles per month</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Last 12 months</p>
                </div>
                <div class="flex items-center gap-4 text-xs">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-indigo-500"></span><span class="text-gray-400">Submitted</span></span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500"></span><span class="text-gray-400">Published</span></span>
                    <a href="{{ route('admin.reports.export', ['kind' => 'monthly']) }}" class="text-indigo-400 hover:underline">Export CSV</a>
                </div>
            </div>
            <div class="flex items-end gap-2 h-48 overflow-x-auto pb-2">
                @foreach($monthly as $m)
                    <div class="flex-1 min-w-[44px] flex flex-col items-center gap-1">
                        <div class="flex-1 w-full flex items-end gap-0.5">
                            <div class="flex-1 bg-indigo-500/80 rounded-t" style="height: {{ ($m['submitted'] / $maxMonthly) * 100 }}%" title="Submitted: {{ $m['submitted'] }}"></div>
                            <div class="flex-1 bg-emerald-500/80 rounded-t" style="height: {{ ($m['published'] / $maxMonthly) * 100 }}%" title="Published: {{ $m['published'] }}"></div>
                        </div>
                        <p class="text-[10px] text-gray-500 whitespace-nowrap">{{ $m['period'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Weekly chart --}}
        <div class="bg-ink-850 border border-ink-700 rounded-xl p-5 mb-6">
            <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-gray-100">Articles per week</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Last 12 weeks</p>
                </div>
                <a href="{{ route('admin.reports.export', ['kind' => 'weekly']) }}" class="text-xs text-indigo-400 hover:underline">Export CSV</a>
            </div>
            <div class="flex items-end gap-2 h-40 overflow-x-auto pb-2">
                @foreach($weekly as $w)
                    <div class="flex-1 min-w-[60px] flex flex-col items-center gap-1">
                        <div class="flex-1 w-full flex items-end gap-0.5">
                            <div class="flex-1 bg-indigo-500/80 rounded-t" style="height: {{ ($w['submitted'] / $maxWeekly) * 100 }}%" title="Submitted: {{ $w['submitted'] }}"></div>
                            <div class="flex-1 bg-emerald-500/80 rounded-t" style="height: {{ ($w['published'] / $maxWeekly) * 100 }}%" title="Published: {{ $w['published'] }}"></div>
                        </div>
                        <p class="text-[10px] text-gray-500 whitespace-nowrap">{{ $w['period'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            {{-- Yearly breakdown --}}
            <div class="bg-ink-850 border border-ink-700 rounded-xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-100">Per year</h3>
                    <a href="{{ route('admin.reports.export', ['kind' => 'yearly']) }}" class="text-xs text-indigo-400 hover:underline">CSV</a>
                </div>
                @if(empty($yearly))
                    <p class="text-xs text-gray-500">No data yet.</p>
                @else
                    <table class="w-full text-sm">
                        <thead class="border-b border-ink-700">
                            <tr>
                                <th class="text-left py-2 text-xs text-gray-400 font-medium">Year</th>
                                <th class="text-right py-2 text-xs text-gray-400 font-medium">Submitted</th>
                                <th class="text-right py-2 text-xs text-gray-400 font-medium">Published</th>
                                <th class="text-right py-2 text-xs text-gray-400 font-medium">Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-700">
                            @foreach($yearly as $y)
                                <tr>
                                    <td class="py-2 text-gray-100 font-medium">{{ $y['year'] }}</td>
                                    <td class="py-2 text-right text-gray-300">{{ $y['submitted'] }}</td>
                                    <td class="py-2 text-right text-emerald-300">{{ $y['published'] }}</td>
                                    <td class="py-2 text-right text-gray-400 text-xs">{{ $y['submitted'] > 0 ? round(($y['published']/$y['submitted'])*100) : 0 }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Article types --}}
            <div class="bg-ink-850 border border-ink-700 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-gray-100 mb-4">Article type mix</h3>
                @if(empty($typeMix))
                    <p class="text-xs text-gray-500">No article types yet.</p>
                @else
                    <ul class="space-y-3">
                        @foreach($typeMix as $t)
                            <li>
                                <div class="flex items-baseline justify-between mb-1">
                                    <p class="text-sm text-gray-200">{{ $t['name'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $t['total'] }} total · {{ $t['published'] }} published</p>
                                </div>
                                <div class="h-1.5 bg-ink-900 rounded-full overflow-hidden">
                                    <div class="h-full bg-indigo-500" style="width: {{ ($t['total'] / $maxType) * 100 }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- Top clients --}}
        <div class="bg-ink-850 border border-ink-700 rounded-xl p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-100">Top clients (all time)</h3>
                <a href="{{ route('admin.reports.export', ['kind' => 'clients']) }}" class="text-xs text-indigo-400 hover:underline">Export CSV</a>
            </div>
            @if(empty($topClients))
                <p class="text-xs text-gray-500">No clients with articles yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[480px]">
                        <thead class="border-b border-ink-700">
                            <tr>
                                <th class="text-left py-2 text-xs text-gray-400 font-medium">Client</th>
                                <th class="text-left py-2 text-xs text-gray-400 font-medium">Company</th>
                                <th class="text-right py-2 text-xs text-gray-400 font-medium">Articles</th>
                                <th class="text-right py-2 text-xs text-gray-400 font-medium">Published</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-700">
                            @foreach($topClients as $c)
                                <tr>
                                    <td class="py-2 text-gray-100 font-medium">{{ $c['name'] }}</td>
                                    <td class="py-2 text-gray-400 text-xs">{{ $c['company'] ?: '—' }}</td>
                                    <td class="py-2 text-right text-gray-300">{{ $c['total_articles'] }}</td>
                                    <td class="py-2 text-right text-emerald-300">{{ $c['published'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            {{-- Sales performance --}}
            <div class="bg-ink-850 border border-ink-700 rounded-xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-100">Sales reps (all time)</h3>
                    <a href="{{ route('admin.reports.export', ['kind' => 'sales']) }}" class="text-xs text-indigo-400 hover:underline">CSV</a>
                </div>
                @if(empty($salesPerf))
                    <p class="text-xs text-gray-500">No sales reps yet.</p>
                @else
                    <table class="w-full text-sm">
                        <thead class="border-b border-ink-700">
                            <tr>
                                <th class="text-left py-2 text-xs text-gray-400 font-medium">Rep</th>
                                <th class="text-right py-2 text-xs text-gray-400 font-medium">Submitted</th>
                                <th class="text-right py-2 text-xs text-gray-400 font-medium">Published</th>
                                <th class="text-right py-2 text-xs text-gray-400 font-medium">Success</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-700">
                            @foreach($salesPerf as $s)
                                <tr>
                                    <td class="py-2 text-gray-100 font-medium">{{ $s['name'] }}</td>
                                    <td class="py-2 text-right text-gray-300">{{ $s['submitted'] }}</td>
                                    <td class="py-2 text-right text-emerald-300">{{ $s['published'] }}</td>
                                    <td class="py-2 text-right text-xs {{ $s['success_rate'] >= 70 ? 'text-emerald-400' : ($s['success_rate'] >= 40 ? 'text-amber-400' : 'text-gray-500') }}">{{ $s['success_rate'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Writer performance --}}
            <div class="bg-ink-850 border border-ink-700 rounded-xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-100">Tech writers (all time)</h3>
                    <a href="{{ route('admin.reports.export', ['kind' => 'writers']) }}" class="text-xs text-indigo-400 hover:underline">CSV</a>
                </div>
                @if(empty($writerPerf))
                    <p class="text-xs text-gray-500">No writers yet.</p>
                @else
                    <table class="w-full text-sm">
                        <thead class="border-b border-ink-700">
                            <tr>
                                <th class="text-left py-2 text-xs text-gray-400 font-medium">Writer</th>
                                <th class="text-right py-2 text-xs text-gray-400 font-medium">Assigned</th>
                                <th class="text-right py-2 text-xs text-gray-400 font-medium">Published</th>
                                <th class="text-right py-2 text-xs text-gray-400 font-medium">Done %</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-700">
                            @foreach($writerPerf as $w)
                                <tr>
                                    <td class="py-2 text-gray-100 font-medium">{{ $w['name'] }}</td>
                                    <td class="py-2 text-right text-gray-300">{{ $w['assigned'] }}</td>
                                    <td class="py-2 text-right text-emerald-300">{{ $w['published'] }}</td>
                                    <td class="py-2 text-right text-xs {{ $w['completion_rate'] >= 70 ? 'text-emerald-400' : ($w['completion_rate'] >= 40 ? 'text-amber-400' : 'text-gray-500') }}">{{ $w['completion_rate'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- Viral packages chart --}}
        <div class="bg-ink-850 border border-ink-700 rounded-xl p-5 mb-6">
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
            <div class="flex items-end gap-2 h-40 overflow-x-auto pb-2">
                @foreach($viralMonthly as $v)
                    <div class="flex-1 min-w-[44px] flex flex-col items-center gap-1">
                        <div class="flex-1 w-full flex items-end gap-0.5">
                            <div class="flex-1 bg-pink-500/80 rounded-t" style="height: {{ ($v['created'] / $maxViral) * 100 }}%" title="Created: {{ $v['created'] }}"></div>
                            <div class="flex-1 bg-emerald-500/80 rounded-t" style="height: {{ ($v['completed'] / $maxViral) * 100 }}%" title="Delivered: {{ $v['completed'] }}"></div>
                        </div>
                        <p class="text-[10px] text-gray-500 whitespace-nowrap">{{ $v['period'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Stage transition counters --}}
        <div class="bg-ink-850 border border-ink-700 rounded-xl p-5">
            <h3 class="text-sm font-semibold text-gray-100 mb-4">Lifetime stage transitions</h3>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <div class="bg-ink-900/60 border border-ink-700 rounded-lg p-3">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wider">Assignments</p>
                    <p class="text-xl font-semibold text-indigo-300 mt-1">{{ number_format($transitionStats['total_assignments']) }}</p>
                </div>
                <div class="bg-ink-900/60 border border-ink-700 rounded-lg p-3">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wider">Corrections</p>
                    <p class="text-xl font-semibold text-amber-300 mt-1">{{ number_format($transitionStats['total_corrections']) }}</p>
                </div>
                <div class="bg-ink-900/60 border border-ink-700 rounded-lg p-3">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wider">Approvals</p>
                    <p class="text-xl font-semibold text-emerald-300 mt-1">{{ number_format($transitionStats['total_approvals']) }}</p>
                </div>
                <div class="bg-ink-900/60 border border-ink-700 rounded-lg p-3">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wider">Publications</p>
                    <p class="text-xl font-semibold text-violet-300 mt-1">{{ number_format($transitionStats['total_published']) }}</p>
                </div>
                <div class="bg-ink-900/60 border border-ink-700 rounded-lg p-3">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wider">Total events</p>
                    <p class="text-xl font-semibold text-gray-200 mt-1">{{ number_format($transitionStats['total_transitions']) }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
