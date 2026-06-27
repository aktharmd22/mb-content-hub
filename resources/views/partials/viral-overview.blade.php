{{-- Viral package overview widget. Expects: $viral (['stats'=>[['label','value'],...], 'packages'=>Collection]), $viralRole ('writer'|'sales'|'admin') --}}
@php
    $showRoute  = $viralRole . '.viral-packages.show';
    $indexRoute = $viralRole . '.viral-packages.index';
@endphp
<div class="bg-ink-850 border border-ink-700 rounded-xl p-5 mb-6">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <h3 class="text-sm font-semibold text-gray-100">Viral package overview</h3>
        </div>
        <a href="{{ route($indexRoute) }}" class="text-xs text-indigo-400 hover:underline">View all</a>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-4">
        @foreach($viral['stats'] as $s)
            <div class="bg-ink-800 border border-ink-700 rounded-lg p-3">
                <p class="text-[11px] text-gray-500 uppercase tracking-wider">{{ $s['label'] }}</p>
                <p class="text-2xl font-bold text-gray-100 mt-0.5 tabular-nums">{{ $s['value'] }}</p>
            </div>
        @endforeach
    </div>

    @if($viral['packages']->isEmpty())
        <p class="text-xs text-gray-500 py-2">No active viral packages.</p>
    @else
        <div class="space-y-2">
            @foreach($viral['packages'] as $p)
                <a href="{{ route($showRoute, $p) }}"
                   class="flex items-center justify-between gap-3 px-3 py-2.5 bg-ink-800/50 border border-ink-700 rounded-lg hover:border-indigo-500/40 transition-colors">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-100 truncate">{{ $p->client?->name ?? '—' }}</p>
                        <p class="text-[11px] text-gray-500">
                            {{ $p->approvedCount() }}/{{ $p->totalDeliverables() }} approved
                            @if($viralRole === 'admin' || $viralRole === 'sales')
                                @if($p->techTeam) · {{ $p->techTeam->name }} @endif
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0" style="width: 120px;">
                        <div class="flex-1 h-1.5 bg-ink-700 rounded-full overflow-hidden">
                            <div class="h-full bg-indigo-500 rounded-full" style="width: {{ $p->progressPercent() }}%"></div>
                        </div>
                        <span class="text-[11px] text-gray-400 tabular-nums w-8 text-right">{{ $p->progressPercent() }}%</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
