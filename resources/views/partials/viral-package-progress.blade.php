@php
    /** @var \App\Models\ViralPackage $package */
    $approved = $package->approvedCount();
    $total    = $package->totalDeliverables();
    $percent  = $package->progressPercent();
@endphp

<div class="flex items-center gap-2">
    <div class="flex-1 h-1.5 bg-ink-800 rounded-full overflow-hidden min-w-[80px] max-w-[140px]">
        <div class="h-full rounded-full {{ $package->isCompleted() ? 'bg-emerald-500' : 'bg-indigo-500' }}"
             style="width: {{ $percent }}%"></div>
    </div>
    <span class="text-xs text-gray-400 whitespace-nowrap">{{ $approved }} / {{ $total }}</span>
</div>
