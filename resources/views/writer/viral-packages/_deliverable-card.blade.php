@php
    /** @var \App\Models\ViralPackage $package */
    /** @var \App\Models\ViralPackageDeliverable $d */

    $stageStyles = [
        'pending'     => ['ring' => 'ring-gray-500/20',    'badge' => 'bg-gray-500/15 text-gray-300 border-gray-500/30',         'glow' => ''],
        'in_progress' => ['ring' => 'ring-indigo-500/30',  'badge' => 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30',   'glow' => 'shadow-[0_0_0_1px_rgba(99,102,241,0.15)]'],
        'review'      => ['ring' => 'ring-amber-500/30',   'badge' => 'bg-amber-500/15 text-amber-300 border-amber-500/30',      'glow' => 'shadow-[0_0_0_1px_rgba(245,158,11,0.15)]'],
        'approved'    => ['ring' => 'ring-emerald-500/30', 'badge' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30', 'glow' => 'shadow-[0_0_0_1px_rgba(16,185,129,0.15)]'],
    ];
    $style = $stageStyles[$d->stage] ?? $stageStyles['pending'];

    $kindIcons = [
        'article'     => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'social_post' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
        'reel'        => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z',
    ];

    $kindColors = [
        'article'     => 'bg-blue-500/10 text-blue-300 border-blue-500/30',
        'social_post' => 'bg-pink-500/10 text-pink-300 border-pink-500/30',
        'reel'        => 'bg-violet-500/10 text-violet-300 border-violet-500/30',
    ];
    $kindColor = $kindColors[$d->kind] ?? $kindColors['article'];
@endphp

<div class="group relative bg-ink-850 border border-ink-700 rounded-xl overflow-hidden transition-all hover:border-ink-600 {{ $style['glow'] }}"
     x-data="{ uploadOpen: false, fileName: '', fileSize: '' }">

    {{-- Header --}}
    <div class="p-4 pb-3">
        <div class="flex items-start justify-between gap-2 mb-2">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-9 h-9 rounded-lg border {{ $kindColor }} flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $kindIcons[$d->kind] ?? $kindIcons['article'] }}"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h4 class="text-sm font-semibold text-gray-100 truncate">{{ $d->title }}</h4>
                    <p class="text-[11px] text-gray-500 uppercase tracking-wide">{{ $d->kindLabel() }}</p>
                </div>
            </div>
            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium border {{ $style['badge'] }} whitespace-nowrap flex-shrink-0">
                @if($d->stage === 'approved')
                    <svg class="w-2.5 h-2.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                @elseif($d->stage === 'review')
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mr-1.5 animate-pulse"></span>
                @elseif($d->stage === 'in_progress')
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 mr-1.5 animate-pulse"></span>
                @endif
                {{ $d->stageLabel() }}
            </span>
        </div>

        {{-- Existing uploaded file --}}
        @if($d->drive_file_id)
            <div class="flex items-center gap-2 px-2.5 py-1.5 bg-ink-900/70 border border-ink-700 rounded-lg mt-3">
                <svg class="w-3.5 h-3.5 text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-[11px] text-gray-400 flex-1 truncate" title="{{ $d->drive_filename }}">{{ $d->drive_filename }}</p>
                <a href="{{ route('writer.viral-packages.deliverables.download', ['viralPackage' => $package, 'deliverable' => $d]) }}"
                   class="text-[11px] text-indigo-400 hover:text-indigo-300 whitespace-nowrap font-medium">Download</a>
            </div>
        @endif

        {{-- Correction notes --}}
        @if($d->notes && in_array($d->stage, ['in_progress', 'pending'], true))
            <div class="px-3 py-2.5 mt-3 bg-amber-500/10 border border-amber-500/30 rounded-lg">
                <div class="flex items-center gap-1.5 mb-1">
                    <svg class="w-3 h-3 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p class="text-[10px] font-semibold text-amber-300 uppercase tracking-wide">Correction requested</p>
                </div>
                <p class="text-xs text-amber-100/90 whitespace-pre-wrap leading-relaxed">{{ $d->notes }}</p>
            </div>
        @endif

        {{-- Assignee info --}}
        @if($d->assignee && in_array($d->stage, ['in_progress', 'review'], true))
            <p class="text-[11px] text-gray-500 mt-3 flex items-center gap-1.5">
                <span class="w-4 h-4 rounded-full bg-indigo-500/20 text-indigo-300 flex items-center justify-center text-[9px] font-semibold flex-shrink-0">{{ strtoupper(substr($d->assignee->name, 0, 1)) }}</span>
                {{ $d->assignee->name }}
            </p>
        @endif
    </div>

    {{-- Footer / action bar --}}
    <div class="px-4 py-3 border-t border-ink-700 bg-ink-900/30">
        @if($d->stage === 'pending')
            <form method="POST" action="{{ route('writer.viral-packages.deliverables.pick-up', ['viralPackage' => $package, 'deliverable' => $d]) }}">
                @csrf
                <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-medium rounded-lg transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Start working
                </button>
            </form>
        @elseif($d->stage === 'in_progress')
            <button type="button" @click="uploadOpen = !uploadOpen"
                    class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-medium rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                {{ $d->drive_file_id ? 'Upload new version' : 'Upload & submit' }}
            </button>
        @elseif($d->stage === 'review')
            <div class="flex items-center justify-center gap-2 text-xs text-amber-300 py-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Waiting for sales review</span>
            </div>
        @elseif($d->stage === 'approved')
            <div class="flex items-center justify-center gap-2 text-xs text-emerald-300 py-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>Approved by sales</span>
            </div>
        @endif
    </div>

    {{-- Upload form (slides in) --}}
    <div x-show="uploadOpen" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="px-4 py-4 border-t border-ink-700 bg-ink-900/50">
        <form method="POST" enctype="multipart/form-data"
              action="{{ route('writer.viral-packages.deliverables.submit', ['viralPackage' => $package, 'deliverable' => $d]) }}"
              class="space-y-3">
            @csrf
            <label :for="`vd-file-{{ $d->id }}`"
                   :class="fileName ? 'border-emerald-500/40 bg-emerald-500/5' : 'border-ink-600 hover:border-indigo-500/60 hover:bg-indigo-500/5'"
                   class="flex items-center gap-2.5 px-3 py-2.5 border-2 border-dashed rounded-lg cursor-pointer transition-colors">
                <svg x-show="!fileName" class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <svg x-show="fileName" x-cloak class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1 min-w-0">
                    <p x-show="!fileName" class="text-xs text-indigo-400 font-medium">Choose your file</p>
                    <p x-show="fileName" x-cloak class="text-xs text-gray-100 truncate" x-text="fileName"></p>
                    <p x-show="fileName" x-cloak class="text-[10px] text-gray-500" x-text="fileSize"></p>
                </div>
            </label>
            <input type="file" :id="`vd-file-{{ $d->id }}`" name="file" required
                   @change="if ($event.target.files[0]) { fileName = $event.target.files[0].name; fileSize = ($event.target.files[0].size/1024/1024).toFixed(2) + ' MB'; }"
                   class="hidden"/>

            <textarea name="notes" rows="2" maxlength="1000" placeholder="Notes for sales (optional)"
                      class="w-full px-3 py-2 text-xs bg-ink-800 border border-ink-600 rounded-lg text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 resize-none"></textarea>

            <div class="flex justify-end gap-2">
                <button type="button" @click="uploadOpen = false; fileName = ''; fileSize = '';"
                        class="px-3 py-1.5 text-xs text-gray-400 hover:text-gray-200 rounded-md transition-colors">Cancel</button>
                <button type="submit" :disabled="!fileName"
                        class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-medium rounded-md transition-colors">
                    Submit for review
                </button>
            </div>
        </form>
    </div>
</div>
