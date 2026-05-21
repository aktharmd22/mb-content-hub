@php
    /** @var \App\Models\ViralPackage $package */
    /** @var \App\Models\ViralPackageDeliverable $d */
    $stageColors = [
        'pending'     => ['bg' => 'bg-gray-500/10',    'text' => 'text-gray-400',    'border' => 'border-gray-500/30'],
        'in_progress' => ['bg' => 'bg-indigo-500/10',  'text' => 'text-indigo-300',  'border' => 'border-indigo-500/30'],
        'review'      => ['bg' => 'bg-amber-500/10',   'text' => 'text-amber-300',   'border' => 'border-amber-500/30'],
        'approved'    => ['bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-300', 'border' => 'border-emerald-500/30'],
    ];
    $colors = $stageColors[$d->stage] ?? $stageColors['pending'];
    $kindIcons = [
        'article'     => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'social_post' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
        'reel'        => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z',
    ];
@endphp

<div class="border border-ink-700 rounded-lg p-4 bg-ink-800/30 hover:bg-ink-800/50 transition-colors"
     x-data="{ correctionOpen: false }">
    <div class="flex items-start justify-between gap-2 mb-3">
        <div class="flex items-center gap-2 min-w-0">
            <div class="w-8 h-8 rounded-md bg-ink-900 border border-ink-700 flex items-center justify-center flex-shrink-0 text-gray-400">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $kindIcons[$d->kind] ?? $kindIcons['article'] }}"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-medium text-gray-100 truncate">{{ $d->title }}</p>
                <p class="text-xs text-gray-500">{{ $d->kindLabel() }}</p>
            </div>
        </div>
        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium {{ $colors['bg'] }} {{ $colors['text'] }} border {{ $colors['border'] }} whitespace-nowrap">
            {{ $d->stageLabel() }}
        </span>
    </div>

    @if($d->drive_file_id)
        <div class="flex items-center gap-2 px-3 py-2 bg-ink-900/60 border border-ink-700 rounded-md mb-3">
            <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-xs text-gray-300 flex-1 truncate" title="{{ $d->drive_filename }}">{{ $d->drive_filename }}</p>
            <a href="{{ route('sales.viral-packages.deliverables.download', ['viralPackage' => $package, 'deliverable' => $d]) }}"
               class="text-xs text-indigo-400 hover:underline whitespace-nowrap">Download</a>
        </div>
    @endif

    @if($d->stage === 'review' && ! $package->isCompleted())
        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('sales.viral-packages.deliverables.approve', ['viralPackage' => $package, 'deliverable' => $d]) }}" class="flex-1">
                @csrf
                <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-medium rounded-md transition-colors">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Approve
                </button>
            </form>
            <button type="button" @click="correctionOpen = !correctionOpen"
                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-amber-500/15 hover:bg-amber-500/25 text-amber-300 border border-amber-500/30 text-xs font-medium rounded-md transition-colors">
                Request changes
            </button>
        </div>

        <form x-show="correctionOpen" x-cloak method="POST" action="{{ route('sales.viral-packages.deliverables.correction', ['viralPackage' => $package, 'deliverable' => $d]) }}"
              enctype="multipart/form-data" class="mt-3 space-y-2">
            @csrf
            <textarea name="reason" required rows="3" maxlength="1000" placeholder="What needs to change?"
                      class="w-full px-3 py-2 text-xs bg-ink-800 border border-ink-600 rounded text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-amber-500/50"></textarea>
            <div class="flex justify-end gap-2">
                <button type="button" @click="correctionOpen = false" class="text-xs text-gray-500 hover:text-gray-300">Cancel</button>
                <button type="submit" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-500 text-white text-xs font-medium rounded-md">Send for correction</button>
            </div>
        </form>
    @elseif($d->stage === 'approved')
        <p class="text-xs text-emerald-400 flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Approved {{ $d->approved_at?->diffForHumans() }}
        </p>
    @elseif($d->stage === 'pending')
        <p class="text-xs text-gray-500">Waiting for tech team to pick up.</p>
    @elseif($d->stage === 'in_progress')
        <p class="text-xs text-indigo-400">{{ $d->assignee?->name ?? 'Tech team' }} is working on it.</p>
    @endif

    @if($d->notes && $d->stage !== 'approved')
        <p class="text-xs text-gray-400 mt-2 italic line-clamp-2" title="{{ $d->notes }}">{{ $d->notes }}</p>
    @endif
</div>
