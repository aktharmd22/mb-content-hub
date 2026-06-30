{{-- Viral package overview widget.
     Expects: $viral (['stats'=>[['label','value','hint'?,'color'?],...], 'packages'=>Collection]), $viralRole ('writer'|'sales'|'admin') --}}
@php
    $showRoute  = $viralRole . '.viral-packages.show';
    $indexRoute = $viralRole . '.viral-packages.index';

    $tileColor = [
        'indigo'  => 'border-l-indigo-500',
        'amber'   => 'border-l-amber-500',
        'blue'    => 'border-l-blue-500',
        'emerald' => 'border-l-emerald-500',
        'gray'    => 'border-l-gray-500',
    ];
@endphp
<div class="bg-ink-850 border border-ink-700 rounded-xl p-5 mb-6">
    {{-- Header --}}
    <div class="flex items-start justify-between gap-3 mb-4">
        <div class="flex items-start gap-3">
            <div class="w-9 h-9 rounded-lg bg-indigo-500/15 text-indigo-400 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-100">Viral packages</h3>
                <p class="text-xs text-gray-500">
                    @if($viralRole === 'writer') Campaigns assigned to you and what needs work.
                    @elseif($viralRole === 'sales') Your campaigns and their progress.
                    @else All active campaigns across the business.
                    @endif
                </p>
            </div>
        </div>
        <a href="{{ route($indexRoute) }}" class="text-xs font-medium text-indigo-400 hover:text-indigo-300 whitespace-nowrap mt-1">View all →</a>
    </div>

    {{-- Stat tiles --}}
    <div class="grid grid-cols-3 gap-3 mb-5">
        @foreach($viral['stats'] as $s)
            <div class="bg-ink-800 border border-ink-700 border-l-4 {{ $tileColor[$s['color'] ?? 'indigo'] ?? $tileColor['indigo'] }} rounded-lg px-3 py-2.5">
                <p class="text-2xl font-bold text-gray-100 leading-none tabular-nums">{{ $s['value'] }}</p>
                <p class="text-[11px] font-medium text-gray-300 mt-1.5">{{ $s['label'] }}</p>
                @if(! empty($s['hint']))
                    <p class="text-[10px] text-gray-500 leading-tight mt-0.5">{{ $s['hint'] }}</p>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Package list --}}
    @if($viral['packages']->isEmpty())
        <div class="text-center py-6 border border-dashed border-ink-700 rounded-lg">
            <p class="text-xs text-gray-500">No active viral packages right now.</p>
        </div>
    @else
        <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-2">Active campaigns</p>
        <div class="space-y-2">
            @foreach($viral['packages'] as $p)
                @php $pct = $p->progressPercent(); $ready = $p->canBeMarkedDelivered(); @endphp
                <a href="{{ route($showRoute, $p) }}"
                   class="flex items-center justify-between gap-3 px-3 py-2.5 bg-ink-800/50 border border-ink-700 rounded-lg hover:border-indigo-500/40 transition-colors">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-100 truncate">{{ $p->client?->displayName() ?? '—' }}</p>
                        <p class="text-[11px] text-gray-500">
                            {{ $p->approvedCount() }}/{{ $p->totalDeliverables() }} approved
                            @if($viralRole !== 'writer' && $p->techTeam) · {{ $p->techTeam->name }} @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-2.5 flex-shrink-0">
                        <div class="hidden sm:block" style="width: 90px;">
                            <div class="h-1.5 bg-ink-700 rounded-full overflow-hidden">
                                <div class="h-full {{ $ready ? 'bg-emerald-500' : 'bg-indigo-500' }} rounded-full" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                        @if($ready)
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-300 whitespace-nowrap">Ready</span>
                        @else
                            <span class="text-[11px] text-gray-400 tabular-nums w-9 text-right">{{ $pct }}%</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
