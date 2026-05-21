@php
    /** @var \App\Models\ViralPackage $package */
    /** @var \App\Models\ViralPackageDeliverable $d */

    $stageBadge = [
        'pending'     => 'bg-gray-500/15 text-gray-300 border-gray-500/30',
        'in_progress' => 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30',
        'review'      => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
        'approved'    => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
    ];
    $badge = $stageBadge[$d->stage] ?? $stageBadge['pending'];

    $kindIcons = [
        'article'     => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'social_post' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
        'reel'        => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z',
    ];
@endphp

<div class="bg-ink-850 border border-ink-700 rounded-xl p-5 transition-colors hover:border-ink-600"
     x-data="{ uploadOpen: false, fileName: '', fileSize: '', historyOpen: false }">

    {{-- Title row --}}
    <div class="flex items-start justify-between gap-3 mb-4">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 rounded-lg bg-ink-900 border border-ink-700 flex items-center justify-center flex-shrink-0 text-gray-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $kindIcons[$d->kind] ?? $kindIcons['article'] }}"/>
                </svg>
            </div>
            <div class="min-w-0">
                <h4 class="text-base font-semibold text-gray-100 truncate">{{ $d->title }}</h4>
                <p class="text-xs text-gray-500">{{ $d->kindLabel() }}</p>
            </div>
        </div>
        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium border {{ $badge }} whitespace-nowrap">
            {{ $d->stageLabel() }}
        </span>
    </div>

    {{-- Existing uploaded file --}}
    @if($d->drive_file_id)
        <div class="flex items-center gap-2 px-3 py-2.5 bg-ink-900/70 border border-ink-700 rounded-lg mb-4">
            <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-xs text-gray-300 flex-1 truncate" title="{{ $d->drive_filename }}">{{ $d->drive_filename }}</p>
            <a href="{{ route('writer.viral-packages.deliverables.download', ['viralPackage' => $package, 'deliverable' => $d]) }}"
               class="text-xs text-indigo-400 hover:text-indigo-300 whitespace-nowrap font-medium">Download</a>
        </div>
    @endif

    {{-- Correction notes --}}
    @if($d->notes && in_array($d->stage, ['in_progress', 'pending'], true))
        <div class="px-3 py-2.5 mb-4 bg-amber-500/10 border border-amber-500/30 rounded-lg">
            <p class="text-xs font-semibold text-amber-300 uppercase tracking-wide mb-1">Correction request</p>
            <p class="text-sm text-amber-100 whitespace-pre-wrap leading-relaxed">{{ $d->notes }}</p>
        </div>
    @endif

    {{-- Action button (full-width, prominent) --}}
    @if(in_array($d->stage, ['pending', 'in_progress'], true))
        <button type="button" @click="uploadOpen = !uploadOpen"
                x-show="!uploadOpen"
                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-lg transition-colors">
            @if($d->stage === 'pending')
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Start working
            @else
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                {{ $d->drive_file_id ? 'Upload new version' : 'Upload & submit' }}
            @endif
        </button>

        <form x-show="uploadOpen" x-cloak method="POST" enctype="multipart/form-data"
              action="{{ route('writer.viral-packages.deliverables.submit', ['viralPackage' => $package, 'deliverable' => $d]) }}"
              x-transition:enter="transition ease-out duration-200"
              x-transition:enter-start="opacity-0 -translate-y-1"
              x-transition:enter-end="opacity-100 translate-y-0"
              class="space-y-3">
            @csrf
            <label :for="`vd-file-{{ $d->id }}`"
                   :class="fileName ? 'border-emerald-500/40 bg-emerald-500/5' : 'border-indigo-500/60 bg-indigo-500/5 hover:bg-indigo-500/10'"
                   class="flex items-center gap-3 px-4 py-4 border-2 border-dashed rounded-lg cursor-pointer transition-colors">
                <svg x-show="!fileName" class="w-6 h-6 text-indigo-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <svg x-show="fileName" x-cloak class="w-6 h-6 text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1 min-w-0">
                    <p x-show="!fileName" class="text-sm font-semibold text-indigo-300">Click to choose a file</p>
                    <p x-show="!fileName" class="text-xs text-gray-500 mt-0.5">or drag and drop here</p>
                    <p x-show="fileName" x-cloak class="text-sm font-medium text-gray-100 truncate" x-text="fileName"></p>
                    <p x-show="fileName" x-cloak class="text-xs text-gray-500 mt-0.5" x-text="fileSize"></p>
                </div>
            </label>
            <input type="file" :id="`vd-file-{{ $d->id }}`" name="file" required
                   @change="if ($event.target.files[0]) { fileName = $event.target.files[0].name; fileSize = ($event.target.files[0].size/1024/1024).toFixed(2) + ' MB'; }"
                   class="hidden"/>

            <textarea name="notes" rows="2" maxlength="1000" placeholder="Notes for sales (optional)"
                      class="w-full px-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 resize-none"></textarea>

            <div class="flex items-center gap-2">
                <button type="button" @click="uploadOpen = false; fileName = ''; fileSize = '';"
                        class="px-4 py-2.5 text-sm text-gray-400 hover:text-gray-200 hover:bg-ink-800 rounded-lg transition-colors">Cancel</button>
                <button type="submit" :disabled="!fileName"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Submit for review
                </button>
            </div>
        </form>
    @elseif($d->stage === 'review')
        <div class="flex items-center justify-center gap-2 px-4 py-3 bg-amber-500/10 border border-amber-500/30 text-amber-300 text-sm font-medium rounded-lg">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Waiting for sales review
        </div>
    @elseif($d->stage === 'approved')
        <div class="flex items-center justify-center gap-2 px-4 py-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm font-medium rounded-lg">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Approved by sales
        </div>
    @endif

    {{-- Review history (collapsible) --}}
    @if($d->history->isNotEmpty())
        <div class="mt-3 pt-3 border-t border-ink-700">
            <button type="button" @click="historyOpen = !historyOpen"
                    class="flex items-center justify-between w-full text-xs text-gray-500 hover:text-gray-300 transition-colors">
                <span class="flex items-center gap-1.5">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Review history ({{ $d->history->count() }})
                </span>
                <svg :class="historyOpen ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <ol x-show="historyOpen" x-cloak class="mt-3 space-y-3">
                @foreach($d->history->sortByDesc('changed_at') as $h)
                    <li class="flex gap-2.5">
                        <div class="w-1.5 h-1.5 rounded-full mt-1.5 flex-shrink-0
                            {{ $h->to_stage === 'approved' ? 'bg-emerald-500' : ($h->to_stage === 'review' ? 'bg-amber-500' : ($h->to_stage === 'in_progress' ? 'bg-indigo-500' : 'bg-gray-500')) }}"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs text-gray-300">
                                <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $h->to_stage)) }}</span>
                                <span class="text-gray-500">·</span>
                                <span class="text-gray-500">{{ $h->changedBy?->name ?? 'system' }}</span>
                            </p>
                            <p class="text-[10px] text-gray-500">{{ $h->changed_at->diffForHumans() }}</p>
                            @if($h->notes)
                                <p class="text-xs text-gray-400 mt-1 italic">"{{ $h->notes }}"</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    @endif
</div>
