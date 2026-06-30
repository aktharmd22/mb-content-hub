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
        'landing_page'=> 'M21 12a9 9 0 11-18 0 9 9 0 0118 0z M3.6 9h16.8 M3.6 15h16.8 M12 3a15 15 0 010 18 M12 3a15 15 0 000 18',
    ];
@endphp

<div class="bg-ink-850 border border-ink-700 rounded-xl p-5 transition-colors hover:border-ink-600"
     x-data="{
        uploadOpen: false, fileName: '', fileSize: '', historyOpen: false,
        uploading: false, progress: 0, done: false,
        upload(form) {
            if (this.uploading) return;
            this.uploading = true; this.progress = 0;
            const xhr = new XMLHttpRequest();
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) this.progress = Math.round(e.loaded / e.total * 100);
            });
            xhr.addEventListener('load', () => {
                this.uploading = false;
                if (xhr.status >= 200 && xhr.status < 300) {
                    this.done = true; this.uploadOpen = false; this.fileName = ''; this.fileSize = '';
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: @js($d->title) + ' submitted for review' } }));
                } else {
                    let msg = 'Upload failed. Please try again.';
                    try { const j = JSON.parse(xhr.responseText); if (j.message) msg = j.message; } catch (err) {}
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: msg } }));
                }
            });
            xhr.addEventListener('error', () => {
                this.uploading = false;
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Network error during upload — please retry.' } }));
            });
            xhr.open('POST', form.action);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.send(new FormData(form));
        }
     }">

    {{-- Pauses live auto-refresh of this section while an upload form is open OR a file is uploading, so it isn't wiped mid-edit/mid-upload (lets other cards upload in parallel safely) --}}
    <template x-if="uploadOpen || uploading"><span data-live-lock="1" style="display:none"></span></template>

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
                @if($d->assignee)
                    @php $isMine = (int) $d->assigned_to === auth()->id(); @endphp
                    <p class="text-[11px] mt-0.5 inline-flex items-center gap-1 {{ $isMine ? 'text-indigo-300 font-medium' : 'text-gray-500' }}">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        {{ $isMine ? 'You' : $d->assignee->name }}
                    </p>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium border {{ $badge }} whitespace-nowrap">
                {{ $d->stageLabel() }}
            </span>
            @if(in_array($d->kind, ['social_post', 'reel'], true) && ! $package->isCompleted())
                <form method="POST" action="{{ route('writer.viral-packages.posts.remove', ['viralPackage' => $package, 'deliverable' => $d]) }}"
                      onsubmit="return confirm('Remove {{ $d->title }}? This cannot be undone.');">
                    @csrf @method('DELETE')
                    <button type="submit" title="Remove this post"
                            class="inline-flex items-center justify-center w-7 h-7 text-gray-500 hover:text-rose-400 hover:bg-rose-500/10 rounded-md transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a2 2 0 012-2h2a2 2 0 012 2v3"/></svg>
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Existing uploaded file --}}
    @if($d->drive_file_id)
        @php
            $isImage = str_starts_with((string) $d->mime_type, 'image/');
            $isPdf   = str_contains((string) $d->mime_type, 'pdf') || str_ends_with(strtolower((string) $d->drive_filename), '.pdf');
        @endphp
        @if($isImage)
            {{-- Image preview --}}
            <a href="{{ route('writer.viral-packages.deliverables.download', ['viralPackage' => $package, 'deliverable' => $d]) }}?inline=1"
               target="_blank" rel="noopener" class="block mb-2 rounded-lg overflow-hidden border border-ink-700 bg-ink-900/70">
                <img loading="lazy"
                     src="{{ route('writer.viral-packages.deliverables.download', ['viralPackage' => $package, 'deliverable' => $d]) }}?inline=1"
                     alt="{{ $d->drive_filename }}"
                     class="w-full h-auto max-h-96 object-contain hover:opacity-90 transition-opacity"/>
            </a>
        @endif
        <div class="flex items-center gap-2 px-3 py-2.5 bg-ink-900/70 border border-ink-700 rounded-lg mb-4">
            <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-xs text-gray-300 flex-1 truncate" title="{{ $d->drive_filename }}">{{ $d->drive_filename }}</p>
            @if($isPdf)
                <a href="{{ route('writer.viral-packages.deliverables.download', ['viralPackage' => $package, 'deliverable' => $d]) }}?inline=1"
                   target="_blank" rel="noopener"
                   class="text-xs text-emerald-400 hover:text-emerald-300 whitespace-nowrap font-medium">View PDF</a>
            @endif
            <a href="{{ route('writer.viral-packages.deliverables.download', ['viralPackage' => $package, 'deliverable' => $d]) }}"
               class="text-xs text-indigo-400 hover:text-indigo-300 whitespace-nowrap font-medium">Download</a>
            @if($d->stage !== 'approved' && ! $package->isCompleted())
                <form method="POST" action="{{ route('writer.viral-packages.deliverables.clear-file', ['viralPackage' => $package, 'deliverable' => $d]) }}"
                      onsubmit="return confirm('Delete this content? The deliverable will go back to in-progress so you can upload again later.');">
                    @csrf
                    <button type="submit" title="Delete content"
                            class="inline-flex items-center justify-center w-6 h-6 text-gray-500 hover:text-rose-400 hover:bg-rose-500/10 rounded transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a2 2 0 012-2h2a2 2 0 012 2v3"/></svg>
                    </button>
                </form>
            @endif
        </div>
    @endif

    {{-- Correction notes + attachments from sales --}}
    @if($d->notes && in_array($d->stage, ['in_progress', 'pending'], true))
        <div class="px-3 py-2.5 mb-4 bg-amber-500/10 border border-amber-500/30 rounded-lg">
            <p class="text-xs font-semibold text-amber-300 uppercase tracking-wide mb-1">Correction request</p>
            <p class="text-sm text-amber-100 whitespace-pre-wrap leading-relaxed">{{ $d->notes }}</p>

            @if($d->correctionAssets->isNotEmpty())
                <div class="mt-3 pt-3 border-t border-amber-500/20 space-y-1.5">
                    <p class="text-[10px] font-semibold text-amber-300/80 uppercase tracking-wide">Reference files</p>
                    @foreach($d->correctionAssets as $ca)
                        @if($ca->type === 'link')
                            <a href="{{ $ca->url }}" target="_blank" rel="noopener"
                               class="flex items-center gap-2 px-2.5 py-2 bg-ink-900/60 border border-amber-500/20 rounded-lg hover:border-amber-500/40 transition-colors">
                                <svg class="w-3.5 h-3.5 text-amber-300 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m6.828-2.828a4 4 0 015.656 0l.001.001a4 4 0 010 5.656l-1.5 1.5"/></svg>
                                <span class="text-xs text-amber-100 truncate flex-1">{{ $ca->url }}</span>
                                <span class="text-[10px] text-amber-300 whitespace-nowrap">Open</span>
                            </a>
                        @else
                            <a href="{{ route('writer.viral-packages.assets.download', ['viralPackage' => $package, 'asset' => $ca]) }}"
                               class="flex items-center gap-2 px-2.5 py-2 bg-ink-900/60 border border-amber-500/20 rounded-lg hover:border-amber-500/40 transition-colors">
                                <svg class="w-3.5 h-3.5 text-amber-300 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span class="text-xs text-amber-100 truncate flex-1" title="{{ $ca->original_filename }}">{{ $ca->original_filename }}</span>
                                <span class="text-[10px] text-amber-300 whitespace-nowrap">Download</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- Action area — upload available for pending, in_progress, AND review (replace) --}}
    @if($d->kind === 'landing_page')
        @include('writer.viral-packages._landing-page-action', ['package' => $package, 'd' => $d])
    @elseif(in_array($d->stage, ['pending', 'in_progress', 'review'], true))
        @if($d->stage === 'review')
            {{-- Closed state in review: waiting banner + "delete & upload new" --}}
            <div x-show="!uploadOpen && !done" class="space-y-2">
                <div class="flex items-center justify-center gap-2 px-4 py-3 bg-amber-500/10 border border-amber-500/30 text-amber-300 text-sm font-medium rounded-lg">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Waiting for sales review
                </div>
                <button type="button" @click="uploadOpen = true"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-ink-800 hover:bg-ink-700 border border-ink-600 text-gray-300 hover:text-gray-100 text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a2 2 0 012-2h2a2 2 0 012 2v3"/></svg>
                    Delete &amp; upload new content
                </button>
            </div>
        @else
            <button type="button" @click="uploadOpen = !uploadOpen"
                    x-show="!uploadOpen && !done"
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
        @endif

        <form x-show="uploadOpen" x-cloak method="POST" enctype="multipart/form-data"
              @submit.prevent="upload($el)"
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

            {{-- Upload progress (keeps running in the background — you can open another post and submit it too) --}}
            <div x-show="uploading" x-cloak class="space-y-1.5">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-indigo-300 font-medium">Uploading…</span>
                    <span class="text-gray-400" x-text="progress + '%'"></span>
                </div>
                <div class="w-full h-2 bg-ink-800 rounded-full overflow-hidden">
                    <div class="h-full bg-indigo-500 rounded-full transition-all duration-150" :style="`width: ${progress}%`"></div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" @click="uploadOpen = false; fileName = ''; fileSize = '';" :disabled="uploading"
                        class="px-4 py-2.5 text-sm text-gray-400 hover:text-gray-200 hover:bg-ink-800 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg transition-colors">Cancel</button>
                <button type="submit" :disabled="!fileName || uploading"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg transition-colors">
                    <svg x-show="!uploading" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <svg x-show="uploading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="uploading ? 'Uploading…' : 'Submit for review'"></span>
                </button>
            </div>
        </form>

        {{-- Shown right after a successful background upload, until the live refresh syncs the card --}}
        <div x-show="done" x-cloak class="flex items-center justify-center gap-2 px-4 py-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm font-medium rounded-lg">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Submitted for review
        </div>
    @elseif($d->stage === 'approved')
        <div class="flex items-center justify-center gap-2 px-4 py-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm font-medium rounded-lg">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Approved by sales
        </div>
    @endif

    {{-- Caption & hashtags — editable once approved (posts & reels) --}}
    @if($d->stage === 'approved' && in_array($d->kind, ['social_post', 'reel'], true))
        <form method="POST" action="{{ route('writer.viral-packages.deliverables.caption', ['viralPackage' => $package, 'deliverable' => $d]) }}"
              class="mt-3 pt-3 border-t border-ink-700 space-y-2">
            @csrf
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Caption & hashtags</p>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="text-[11px] text-gray-500">Caption</label>
                    <button type="button" onclick="copyToClipboard(document.getElementById('cap-{{ $d->id }}').value)"
                            class="text-[11px] text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        Copy
                    </button>
                </div>
                <textarea id="cap-{{ $d->id }}" name="caption" rows="3" maxlength="5000"
                          placeholder="Write the caption for this post…"
                          class="w-full px-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 resize-none">{{ $d->caption }}</textarea>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="text-[11px] text-gray-500">Hashtags</label>
                    <button type="button" onclick="copyToClipboard(document.getElementById('tags-{{ $d->id }}').value)"
                            class="text-[11px] text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        Copy
                    </button>
                </div>
                <textarea id="tags-{{ $d->id }}" name="hashtags" rows="2" maxlength="2000"
                          placeholder="#example #tags"
                          class="w-full px-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 resize-none">{{ $d->hashtags }}</textarea>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="text-[11px] text-gray-500">Target audience</label>
                    <button type="button" onclick="copyToClipboard(document.getElementById('aud-{{ $d->id }}').value)"
                            class="text-[11px] text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        Copy
                    </button>
                </div>
                <textarea id="aud-{{ $d->id }}" name="target_audience" rows="2" maxlength="2000"
                          placeholder="e.g. Women 25-40 in Malaysia interested in natural skincare"
                          class="w-full px-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 resize-none">{{ $d->target_audience }}</textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-lg transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    {{ ($d->caption || $d->hashtags || $d->target_audience) ? 'Update' : 'Save' }} caption
                </button>
            </div>
        </form>
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
