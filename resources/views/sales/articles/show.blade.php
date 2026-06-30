<x-app-layout>
    <x-slot name="header">{{ $article->article_code }}</x-slot>
    <x-slot name="title">{{ $article->title }}</x-slot>

    @php
        $stage = $article->current_stage;
        $isOwn = $article->sales_rep_id === auth()->id();
        $canActOnClientApproval = $isOwn && $stage === \App\Enums\ArticleStage::CLIENT_APPROVAL;
        // Sales' job ends at "Verified". Tech team handles publishing the article on the website.
        $verifiedAwaitingPublish = $isOwn && $stage === \App\Enums\ArticleStage::APPROVED;
        $canRevokeRevision       = $isOwn && $stage === \App\Enums\ArticleStage::REVISIONS;
    @endphp

    <div class="p-6 max-w-5xl">

        <a href="{{ route('sales.articles.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 mb-3 transition-colors">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to articles
        </a>

        <!-- Header card -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-6 mb-6">
            <div class="flex items-start justify-between gap-4 mb-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $article->article_code }}</span>
                        <x-stage-badge :stage="$stage" />
                        @if($article->priority === 'high')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300">High priority</span>
                        @endif
                    </div>
                    <h1 class="text-xl font-medium text-gray-900 dark:text-gray-100">{{ $article->title }}</h1>
                </div>

                @if($article->current_drive_file_id)
                    <a href="{{ route('sales.articles.download', $article) }}"
                       class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download current file
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Client</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100 mt-0.5">{{ $article->client?->displayName() ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Deadline</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100 mt-0.5">
                        {{ $article->deadline?->format('M j, Y') ?? '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Word target</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100 mt-0.5">{{ $article->word_count_target ? number_format($article->word_count_target) : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Submitted</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100 mt-0.5">{{ $article->submitted_at?->diffForHumans() ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Sales rep</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100 mt-0.5">{{ $article->salesRep?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Tech writer</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100 mt-0.5">{{ $article->techWriter?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Tech lead</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100 mt-0.5">{{ $article->techLead?->name ?? '—' }}</p>
                </div>
                @if($article->published_url)
                    <div class="col-span-2 sm:col-span-1" x-data="{ copied: false }">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Published URL</p>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <a href="{{ $article->published_url }}" target="_blank" rel="noopener"
                               class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline truncate flex-1 min-w-0">{{ $article->published_url }}</a>
                            <button type="button"
                                    @click="navigator.clipboard.writeText(@js($article->published_url)).then(() => { copied = true; setTimeout(() => copied = false, 1500); })"
                                    :title="copied ? 'Copied!' : 'Copy URL'"
                                    class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-md text-gray-500 hover:text-indigo-400 hover:bg-indigo-500/10 transition-colors">
                                <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <svg x-show="copied" x-cloak class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            @if($article->notes)
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Notes for the writer</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $article->notes }}</p>
                </div>
            @endif
        </div>

        <!-- Stage actions -->
        @if($canActOnClientApproval || $verifiedAwaitingPublish || $canRevokeRevision)
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5 mb-6"
                 x-data="{ revisionOpen: false }">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Actions</h3>

                @if($canActOnClientApproval)
                    <div class="flex flex-wrap items-center gap-2">
                        <form method="POST" action="{{ route('sales.articles.client-approved', $article) }}" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Mark client-approved
                            </button>
                        </form>
                        <button type="button" @click="revisionOpen = !revisionOpen" class="inline-flex items-center gap-2 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/40 dark:hover:bg-amber-950/60 text-amber-700 dark:text-amber-300 text-sm font-medium rounded-lg transition-colors">
                            Send for revision
                        </button>
                    </div>

                    <form x-show="revisionOpen" x-cloak method="POST" action="{{ route('sales.articles.request-revision', $article) }}"
                          enctype="multipart/form-data"
                          x-data="revisionAssets()"
                          class="mt-3 space-y-3">
                        @csrf
                        <textarea name="reason" required rows="3" maxlength="1000"
                                  placeholder="Why is the client requesting changes?"
                                  class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>

                        <!-- Revision assets -->
                        <div class="pt-3 border-t border-gray-100 dark:border-gray-800">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <h4 class="text-xs font-medium text-gray-700 dark:text-gray-300">Reference assets <span class="text-gray-500 font-normal">(optional)</span></h4>
                                    <p class="text-[11px] text-gray-500 mt-0.5">Saved to a "Correction needed" subfolder so tech team can see exactly what to change.</p>
                                </div>
                                <button type="button" @click="addAsset()"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium bg-amber-500/15 hover:bg-amber-500/25 text-amber-300 border border-amber-500/30 rounded-md transition-colors">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Add asset
                                </button>
                            </div>

                            <div class="space-y-2">
                                <template x-for="(asset, i) in assets" :key="asset.uid">
                                    <div class="bg-ink-800/40 border border-ink-700 rounded-lg overflow-hidden">
                                        <div class="flex items-center justify-between px-3 py-2 bg-ink-800/60 border-b border-ink-700">
                                            <div class="inline-flex bg-ink-900 border border-ink-700 rounded-md p-0.5">
                                                <button type="button"
                                                        @click="asset.type = 'file'; asset.url = ''"
                                                        :class="asset.type === 'file' ? 'bg-amber-600 text-white' : 'text-gray-400 hover:text-gray-200'"
                                                        class="inline-flex items-center gap-1.5 px-2 py-0.5 text-xs font-medium rounded transition-colors">File</button>
                                                <button type="button"
                                                        @click="asset.type = 'link'; asset.fileName = ''; asset.fileSize = ''"
                                                        :class="asset.type === 'link' ? 'bg-amber-600 text-white' : 'text-gray-400 hover:text-gray-200'"
                                                        class="inline-flex items-center gap-1.5 px-2 py-0.5 text-xs font-medium rounded transition-colors">Link</button>
                                            </div>
                                            <button type="button" @click="removeAsset(i)" class="text-xs text-gray-500 hover:text-rose-400">Remove</button>
                                        </div>
                                        <input type="hidden" :name="`assets[${i}][type]`" :value="asset.type"/>

                                        <template x-if="asset.type === 'file'">
                                            <div class="p-2">
                                                <label :for="`rev-asset-file-${asset.uid}`"
                                                       :class="asset.fileName ? 'border-emerald-500/40 bg-emerald-500/5' : 'border-ink-600 hover:border-amber-500/60 hover:bg-amber-500/5'"
                                                       class="flex items-center gap-3 px-3 py-2 border-2 border-dashed rounded-md cursor-pointer transition-colors">
                                                    <svg x-show="!asset.fileName" class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                                    </svg>
                                                    <svg x-show="asset.fileName" x-cloak class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    <div class="flex-1 min-w-0">
                                                        <p x-show="!asset.fileName" class="text-xs text-gray-300"><span class="text-amber-400 font-medium">Click to choose a file</span></p>
                                                        <p x-show="asset.fileName" x-cloak class="text-xs text-gray-100 truncate" x-text="asset.fileName"></p>
                                                        <p x-show="asset.fileName" x-cloak class="text-[11px] text-gray-500" x-text="asset.fileSize"></p>
                                                    </div>
                                                </label>
                                                <input type="file"
                                                       :id="`rev-asset-file-${asset.uid}`"
                                                       :name="`assets[${i}][file]`"
                                                       @change="handleAssetFile(i, $event.target.files[0])"
                                                       accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.mov,.avi,.webm,.mp3,.wav,.m4a,.pdf,.doc,.docx,.txt"
                                                       class="hidden"/>
                                            </div>
                                        </template>

                                        <template x-if="asset.type === 'link'">
                                            <div class="p-2">
                                                <input type="url" :name="`assets[${i}][url]`" x-model="asset.url"
                                                       placeholder="https://..."
                                                       class="w-full px-3 py-1.5 text-xs bg-ink-800 border border-ink-600 rounded text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-amber-500/50"/>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button" @click="revisionOpen = false" class="px-3 py-1.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg">Cancel</button>
                            <button type="submit" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg">Send for revision</button>
                        </div>
                    </form>

                    <script>
                        function revisionAssets() {
                            return {
                                assets: [],
                                addAsset() {
                                    this.assets.push({ uid: Date.now() + Math.random(), type: 'file', url: '', fileName: '', fileSize: '' });
                                },
                                removeAsset(i) { this.assets.splice(i, 1); },
                                handleAssetFile(i, f) {
                                    if (! f) return;
                                    if (f.size > 200 * 1024 * 1024) {
                                        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'File is larger than 200 MB.' } }));
                                        return;
                                    }
                                    this.assets[i].fileName = f.name;
                                    this.assets[i].fileSize = this.formatBytes(f.size);
                                },
                                formatBytes(b) {
                                    if (b < 1024) return b + ' B';
                                    if (b < 1024 * 1024) return (b / 1024).toFixed(1) + ' KB';
                                    return (b / 1024 / 1024).toFixed(1) + ' MB';
                                },
                            }
                        }
                    </script>
                @endif

                @if($canRevokeRevision)
                    <div class="space-y-3">
                        <div class="flex items-start gap-3 p-3 bg-amber-500/10 border border-amber-500/30 rounded-lg">
                            <svg class="w-4 h-4 text-amber-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-amber-200">Sent for correction</p>
                                <p class="text-xs text-amber-300/80 mt-0.5">If you sent this back by mistake, you can revoke and put it back into sales review.</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('sales.articles.revoke-revision', $article) }}"
                              onsubmit="return confirm('Revoke the correction request? The article will go back to Sales review.');">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 px-3 py-1.5 bg-amber-500/15 hover:bg-amber-500/25 text-amber-200 border border-amber-500/30 text-sm font-medium rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                </svg>
                                Revoke correction
                            </button>
                        </form>
                    </div>
                @endif

                @if($verifiedAwaitingPublish)
                    <div class="flex items-start gap-3 p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-lg">
                        <svg class="w-4 h-4 text-emerald-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-emerald-200">Verified — your part is done.</p>
                            <p class="text-xs text-emerald-300/80 mt-0.5">The tech team will publish this article on the website and update its status here.</p>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        @include('partials.article-assets-card', ['routeName' => 'sales.articles.assets.download'])

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Stage history -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">Stage history</h3>
                @if($article->history->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">No history yet.</p>
                @else
                    <ol class="relative space-y-4">
                        @foreach($article->history as $i => $h)
                            <li class="flex gap-3">
                                <div class="flex flex-col items-center flex-shrink-0">
                                    <div class="w-2 h-2 rounded-full bg-indigo-500 mt-1.5"></div>
                                    @unless($loop->last)
                                        <div class="flex-1 w-px bg-gray-200 dark:bg-gray-800 mt-1"></div>
                                    @endunless
                                </div>
                                <div class="flex-1 pb-2 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        @if($h->from_stage)
                                            <x-stage-badge :stage="$h->from_stage" />
                                            <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                            </svg>
                                        @endif
                                        <x-stage-badge :stage="$h->to_stage" />
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $h->changedBy?->name ?? 'system' }} · {{ $h->changed_at->diffForHumans() }}
                                    </p>
                                    @if($h->notes)
                                        <p class="text-xs text-gray-700 dark:text-gray-300 mt-1 whitespace-pre-wrap">{{ $h->notes }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>

            <!-- Comments -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">Comments</h3>

                @if($article->comments->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">No comments yet.</p>
                @else
                    <div class="space-y-3 mb-4">
                        @foreach($article->comments as $c)
                            <div class="flex gap-3">
                                <div class="w-7 h-7 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-medium text-indigo-700 dark:text-indigo-300">{{ strtoupper(substr($c->user->name, 0, 1)) }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $c->user->name }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $c->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-0.5 whitespace-pre-wrap">{{ $c->comment }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('sales.articles.comment', $article) }}" class="space-y-2">
                    @csrf
                    <textarea name="comment" required rows="2" maxlength="2000"
                              placeholder="Add a comment..."
                              class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    <div class="flex justify-end">
                        <button type="submit" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg transition-colors">
                            Post comment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
