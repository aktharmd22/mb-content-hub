<x-app-layout>
    <x-slot name="header">{{ $article->article_code }}</x-slot>
    <x-slot name="title">{{ $article->title }}</x-slot>

    @php
        $stage = $article->current_stage;
        $canStart   = in_array($stage, [\App\Enums\ArticleStage::ASSIGNED, \App\Enums\ArticleStage::REVISIONS], true);
        $canSubmit  = $stage === \App\Enums\ArticleStage::IN_PROGRESS;
        $canPublish = $stage === \App\Enums\ArticleStage::APPROVED;
        $isPassive  = in_array($stage, [
            \App\Enums\ArticleStage::INTERNAL_REVIEW,
            \App\Enums\ArticleStage::CLIENT_APPROVAL,
            \App\Enums\ArticleStage::PUBLISHED,
        ], true);

        $latestRevision = $stage === \App\Enums\ArticleStage::REVISIONS
            ? $article->history->where('to_stage', \App\Enums\ArticleStage::REVISIONS)->sortByDesc('changed_at')->first()
            : null;
    @endphp

    <div class="p-6 max-w-5xl">

        <a href="{{ route('writer.articles.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 mb-3 transition-colors">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to assignments
        </a>

        <!-- Header card -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-6 mb-6">
            <div class="flex items-start justify-between gap-4 mb-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $article->article_code }}</span>
                        <x-stage-badge :stage="$stage" />
                        @if($article->priority === 'high')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300">High priority</span>
                        @endif
                    </div>
                    <h1 class="text-xl font-medium text-gray-900 dark:text-gray-100">{{ $article->title }}</h1>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Client</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100 mt-0.5">{{ $article->client?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Deadline</p>
                    @if($article->deadline)
                        @php $days = $article->days_until_deadline; @endphp
                        <p class="text-sm mt-0.5
                                  {{ $days < 0 ? 'text-rose-600 dark:text-rose-400'
                                     : ($days <= 2 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-gray-100') }}">
                            {{ $article->deadline->format('M j, Y') }}
                            @if($days < 0)
                                <span class="text-xs">({{ abs($days) }}d late)</span>
                            @elseif($days === 0)
                                <span class="text-xs">(today)</span>
                            @elseif($days <= 7)
                                <span class="text-xs">({{ $days }}d left)</span>
                            @endif
                        </p>
                    @else
                        <p class="text-sm text-gray-400 mt-0.5">No deadline</p>
                    @endif
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Word target</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100 mt-0.5">{{ $article->word_count_target ? number_format($article->word_count_target) . ' words' : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Sales rep</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100 mt-0.5">{{ $article->salesRep?->name ?? '—' }}</p>
                </div>
            </div>

            @if($article->notes)
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Notes from sales</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $article->notes }}</p>
                </div>
            @endif
        </div>

        <!-- Revision banner -->
        @if($latestRevision)
            <div class="bg-orange-50 dark:bg-orange-950/30 border border-orange-200 dark:border-orange-900 rounded-lg p-4 mb-6">
                <div class="flex items-start gap-3">
                    <svg class="w-4 h-4 text-orange-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-orange-900 dark:text-orange-200">Revision requested by {{ $latestRevision->changedBy?->name ?? 'someone' }}</p>
                        @if($latestRevision->notes)
                            <p class="text-sm text-orange-800 dark:text-orange-300 mt-1 whitespace-pre-wrap">{{ $latestRevision->notes }}</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Work area -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5 mb-6"
             x-data="writerActions()">
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">Files & actions</h3>

            <!-- Source file row -->
            <div class="flex items-center justify-between gap-4 py-3 border-b border-gray-100 dark:border-gray-800">
                <div class="min-w-0">
                    <p class="text-sm text-gray-900 dark:text-gray-100">Source file</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Original document from sales</p>
                </div>
                @if($article->source_drive_file_id)
                    <a href="{{ route('writer.articles.download-source', $article) }}"
                       class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-lg transition-colors whitespace-nowrap">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download source
                    </a>
                @else
                    <span class="text-xs text-gray-400">No file</span>
                @endif
            </div>

            <!-- Current file row (only if writer has uploaded a rewrite) -->
            @if($article->current_drive_file_id && $article->current_drive_file_id !== $article->source_drive_file_id)
                <div class="flex items-center justify-between gap-4 py-3 border-b border-gray-100 dark:border-gray-800">
                    <div class="min-w-0">
                        <p class="text-sm text-gray-900 dark:text-gray-100">Latest version</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Most recent rewrite uploaded</p>
                    </div>
                    <a href="{{ route('writer.articles.download', $article) }}"
                       class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-lg transition-colors whitespace-nowrap">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download latest
                    </a>
                </div>
            @endif

            <!-- Stage-specific action -->
            <div class="pt-4">
                @if($canStart)
                    <form method="POST" action="{{ route('writer.articles.start', $article) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ $stage === \App\Enums\ArticleStage::REVISIONS ? 'Resume work' : 'Start working' }}
                        </button>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">This moves the article to "In progress" and lets you upload your rewrite.</p>
                    </form>
                @elseif($canSubmit)
                    <form method="POST" action="{{ route('writer.articles.submit-review', $article) }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Upload your rewrite <span class="text-rose-500">*</span>
                            </label>
                            <div
                                @dragover.prevent="dragOver = true"
                                @dragleave.prevent="dragOver = false"
                                @drop.prevent="handleDrop($event)"
                                @click="$refs.fileInput.click()"
                                :class="{
                                    'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/40': dragOver,
                                    'border-gray-300 dark:border-gray-700': !dragOver && !file,
                                    'border-emerald-300 dark:border-emerald-700 bg-emerald-50/50 dark:bg-emerald-950/20': file
                                }"
                                class="border-2 border-dashed rounded-lg px-4 py-6 text-center cursor-pointer transition-colors"
                            >
                                <template x-if="!file">
                                    <div>
                                        <svg class="w-7 h-7 mx-auto mb-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                        </svg>
                                        <p class="text-sm text-gray-700 dark:text-gray-300">
                                            <span class="font-medium text-indigo-600 dark:text-indigo-400">Click</span> or drag the rewrite here
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">.doc, .docx, .pdf, .txt — up to 50 MB</p>
                                    </div>
                                </template>
                                <template x-if="file">
                                    <div class="flex items-center justify-center gap-3">
                                        <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <div class="text-left min-w-0">
                                            <p class="text-sm text-gray-900 dark:text-gray-100 truncate" x-text="file.name"></p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="formatBytes(file.size)"></p>
                                        </div>
                                        <button type="button" @click.stop="clearFile()" class="text-xs text-rose-600 hover:underline">Remove</button>
                                    </div>
                                </template>
                            </div>
                            <input type="file" name="file" x-ref="fileInput" @change="handleFile($event.target.files[0])"
                                   accept=".doc,.docx,.pdf,.txt"
                                   class="hidden" required/>
                            @error('file')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="notes" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Notes for the reviewer (optional)</label>
                            <textarea id="notes" name="notes" rows="2" maxlength="1000"
                                      placeholder="Anything the tech lead should know..."
                                      class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                            @error('notes')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" :disabled="!file"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors">
                                Submit to sales for review
                            </button>
                        </div>
                    </form>
                @elseif($canPublish)
                    <div x-data="{ publishOpen: false }">
                        <div class="flex items-center gap-3 mb-3 p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-lg">
                            <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm text-emerald-200">Sales has verified this article. Publish it to mark complete.</p>
                        </div>

                        <button type="button" @click="publishOpen = !publishOpen"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-sm font-medium rounded-lg transition-all shadow-lg shadow-indigo-500/20">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                            Mark as published
                        </button>

                        <form x-show="publishOpen" x-cloak method="POST" action="{{ route('writer.articles.publish', $article) }}"
                              class="mt-3 space-y-2">
                            @csrf
                            <label class="block text-xs font-medium text-gray-300 mb-1">Published URL <span class="text-rose-400">*</span></label>
                            <div class="flex items-center gap-2">
                                <input type="url" name="published_url" required
                                       placeholder="https://malayznbeat.com/articles/..."
                                       class="flex-1 px-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"/>
                                <button type="submit" class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">Publish</button>
                                <button type="button" @click="publishOpen = false" class="px-3 py-2 text-sm text-gray-400 hover:text-gray-200">Cancel</button>
                            </div>
                            <p class="text-xs text-gray-500">Paste the live URL where the article is now published. The Drive file will move to "Upload to Website 6".</p>
                        </form>
                    </div>
                @elseif($isPassive)
                    @if($stage === \App\Enums\ArticleStage::PUBLISHED)
                        <div class="flex items-start gap-3 p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-lg">
                            <svg class="w-4 h-4 text-emerald-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-emerald-200">Published</p>
                                @if($article->published_url)
                                    <a href="{{ $article->published_url }}" target="_blank" rel="noopener" class="text-xs text-emerald-300 hover:underline truncate block">{{ $article->published_url }}</a>
                                @endif
                            </div>
                        </div>
                    @elseif($stage === \App\Enums\ArticleStage::CLIENT_APPROVAL)
                        <p class="text-sm text-gray-500">Sales is reviewing the rewrite. You'll be notified if it needs changes or once it's verified.</p>
                    @else
                        <p class="text-sm text-gray-500">This article has moved past your stage.</p>
                    @endif
                @endif
            </div>
        </div>

        @include('partials.article-assets-card', ['routeName' => 'writer.articles.assets.download'])

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Stage history -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">Stage history</h3>
                @if($article->history->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">No history yet.</p>
                @else
                    <ol class="relative space-y-4">
                        @foreach($article->history as $h)
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

                <form method="POST" action="{{ route('writer.articles.comment', $article) }}" class="space-y-2">
                    @csrf
                    <textarea name="comment" required rows="2" maxlength="2000"
                              placeholder="Add a comment..."
                              class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    <div class="flex justify-end">
                        <button type="submit" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg transition-colors">Post comment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function writerActions() {
            return {
                file: null,
                dragOver: false,
                handleFile(f) {
                    if (! f) return;
                    if (f.size > 50 * 1024 * 1024) {
                        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'File is larger than 50 MB.' } }));
                        return;
                    }
                    this.file = f;
                    this.$refs.fileInput.files = this.makeFileList(f);
                },
                handleDrop(e) {
                    this.dragOver = false;
                    if (e.dataTransfer.files.length) this.handleFile(e.dataTransfer.files[0]);
                },
                clearFile() {
                    this.file = null;
                    this.$refs.fileInput.value = '';
                },
                makeFileList(file) {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    return dt.files;
                },
                formatBytes(b) {
                    if (b < 1024) return b + ' B';
                    if (b < 1024 * 1024) return (b / 1024).toFixed(1) + ' KB';
                    return (b / 1024 / 1024).toFixed(1) + ' MB';
                },
            }
        }
    </script>
</x-app-layout>
