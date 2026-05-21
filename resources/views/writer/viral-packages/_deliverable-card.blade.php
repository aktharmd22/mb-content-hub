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

<div class="border border-ink-700 rounded-lg p-4 bg-ink-800/30"
     x-data="{ uploadOpen: false, fileName: '', fileSize: '' }">

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
            <a href="{{ route('writer.viral-packages.deliverables.download', ['viralPackage' => $package, 'deliverable' => $d]) }}"
               class="text-xs text-indigo-400 hover:underline whitespace-nowrap">Download</a>
        </div>
    @endif

    @if($d->notes && in_array($d->stage, ['in_progress', 'pending']))
        <div class="px-3 py-2 mb-3 bg-amber-500/10 border border-amber-500/30 rounded-md">
            <p class="text-[10px] font-medium text-amber-300 uppercase tracking-wide">Correction request</p>
            <p class="text-xs text-amber-100 mt-0.5 whitespace-pre-wrap">{{ $d->notes }}</p>
        </div>
    @endif

    @if($d->stage === 'pending')
        <form method="POST" action="{{ route('writer.viral-packages.deliverables.pick-up', ['viralPackage' => $package, 'deliverable' => $d]) }}">
            @csrf
            <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-medium rounded-md transition-colors">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Start working
            </button>
        </form>
    @elseif($d->stage === 'in_progress')
        <button type="button" @click="uploadOpen = !uploadOpen"
                class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-medium rounded-md transition-colors">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            {{ $d->drive_file_id ? 'Upload new version' : 'Upload & submit' }}
        </button>

        <form x-show="uploadOpen" x-cloak method="POST" enctype="multipart/form-data"
              action="{{ route('writer.viral-packages.deliverables.submit', ['viralPackage' => $package, 'deliverable' => $d]) }}"
              class="mt-3 space-y-2">
            @csrf
            <label :for="`vd-file-{{ $d->id }}`"
                   :class="fileName ? 'border-emerald-500/40 bg-emerald-500/5' : 'border-ink-600 hover:border-indigo-500/60'"
                   class="flex items-center gap-2 px-3 py-2 border-2 border-dashed rounded-md cursor-pointer transition-colors">
                <span x-show="!fileName" class="text-xs text-indigo-400">Click to choose your file</span>
                <span x-show="fileName" x-cloak class="text-xs text-gray-100 truncate" x-text="fileName"></span>
                <span x-show="fileName" x-cloak class="text-[10px] text-gray-500" x-text="fileSize"></span>
            </label>
            <input type="file" :id="`vd-file-{{ $d->id }}`" name="file" required
                   @change="if ($event.target.files[0]) { fileName = $event.target.files[0].name; fileSize = ($event.target.files[0].size/1024/1024).toFixed(2) + ' MB'; }"
                   class="hidden"/>

            <textarea name="notes" rows="2" maxlength="1000" placeholder="Notes for sales (optional)"
                      class="w-full px-3 py-2 text-xs bg-ink-800 border border-ink-600 rounded text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"></textarea>
            <div class="flex justify-end gap-2">
                <button type="button" @click="uploadOpen = false; fileName = ''; fileSize = '';" class="text-xs text-gray-500 hover:text-gray-300">Cancel</button>
                <button type="submit" :disabled="!fileName" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white text-xs font-medium rounded-md">Submit for review</button>
            </div>
        </form>
    @elseif($d->stage === 'review')
        <p class="text-xs text-amber-300 flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Submitted — waiting for sales review
        </p>
    @elseif($d->stage === 'approved')
        <p class="text-xs text-emerald-400 flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Approved by sales
        </p>
    @endif
</div>
