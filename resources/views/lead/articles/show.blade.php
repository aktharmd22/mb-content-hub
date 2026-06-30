<x-app-layout>
    <x-slot name="header">{{ $article->article_code }}</x-slot>
    <x-slot name="title">{{ $article->title }}</x-slot>

    @php
        $stage = $article->current_stage;
        $canReview = $stage === \App\Enums\ArticleStage::INTERNAL_REVIEW;
    @endphp

    <div class="p-6 max-w-5xl">

        <a href="{{ route('lead.articles.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 mb-3 transition-colors">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to review queue
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
                    <p class="text-sm text-gray-900 dark:text-gray-100 mt-0.5">{{ $article->client?->displayName() ?? '—' }}</p>
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
                    <p class="text-xs text-gray-500 dark:text-gray-400">Deadline</p>
                    @if($article->deadline)
                        @php $days = $article->days_until_deadline; @endphp
                        <p class="text-sm mt-0.5
                                  {{ $days < 0 ? 'text-rose-600 dark:text-rose-400'
                                     : ($days <= 2 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-gray-100') }}">
                            {{ $article->deadline->format('M j, Y') }}
                            @if($days < 0)<span class="text-xs">({{ abs($days) }}d late)</span>
                            @elseif($days === 0)<span class="text-xs">(today)</span>
                            @elseif($days <= 7)<span class="text-xs">({{ $days }}d)</span>
                            @endif
                        </p>
                    @else
                        <p class="text-sm text-gray-400 mt-0.5">No deadline</p>
                    @endif
                </div>
            </div>

            @if($article->notes)
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Sales notes</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $article->notes }}</p>
                </div>
            @endif
        </div>

        <!-- Side-by-side files -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5 mb-6">
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">Compare files</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="p-4 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Original (from sales)</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100 mb-3">Source document</p>
                    @if($article->source_drive_file_id)
                        <a href="{{ route('lead.articles.download-source', $article) }}"
                           class="inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download original
                        </a>
                    @else
                        <p class="text-xs text-gray-400">No file</p>
                    @endif
                </div>

                <div class="p-4 bg-indigo-50/50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-900 rounded-lg">
                    <p class="text-xs text-indigo-700 dark:text-indigo-400 mb-1">Rewrite (from {{ $article->techWriter?->name ?? 'writer' }})</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100 mb-3">Latest version</p>
                    @if($article->current_drive_file_id)
                        <a href="{{ route('lead.articles.download', $article) }}"
                           class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download rewrite
                        </a>
                    @else
                        <p class="text-xs text-gray-400">No file</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Review actions -->
        @if($canReview)
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5 mb-6"
                 x-data="{ revisionOpen: false }">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Review decision</h3>

                <div class="flex flex-wrap items-center gap-2">
                    <form method="POST" action="{{ route('lead.articles.approve', $article) }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Approve & send to client
                        </button>
                    </form>
                    <button type="button" @click="revisionOpen = !revisionOpen" class="inline-flex items-center gap-2 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/40 dark:hover:bg-amber-950/60 text-amber-700 dark:text-amber-300 text-sm font-medium rounded-lg transition-colors">
                        Send back for revision
                    </button>
                </div>

                <form x-show="revisionOpen" x-cloak method="POST" action="{{ route('lead.articles.request-revision', $article) }}" class="mt-3 space-y-2">
                    @csrf
                    <textarea name="reason" required rows="3" maxlength="1000"
                              placeholder="What does the writer need to fix?"
                              class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="revisionOpen = false" class="px-3 py-1.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg">Cancel</button>
                        <button type="submit" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg">Send for revision</button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Reassign -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5 mb-6"
             x-data="{ reassignOpen: false }">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Writer assignment</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Currently assigned to <span class="text-gray-700 dark:text-gray-300">{{ $article->techWriter?->name ?? 'nobody' }}</span>
                    </p>
                </div>
                <button type="button" @click="reassignOpen = !reassignOpen" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                    Reassign
                </button>
            </div>

            <form x-show="reassignOpen" x-cloak method="POST" action="{{ route('lead.articles.reassign', $article) }}" class="mt-4 flex items-center gap-2">
                @csrf
                <select name="tech_writer_id" required
                        class="flex-1 px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Select a writer</option>
                    @foreach($writers as $w)
                        <option value="{{ $w->id }}" @selected($article->tech_writer_id === $w->id)>{{ $w->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">Reassign</button>
            </form>
        </div>

        @include('partials.article-assets-card', ['routeName' => 'lead.articles.assets.download'])

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

                <form method="POST" action="{{ route('lead.articles.comment', $article) }}" class="space-y-2">
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
</x-app-layout>
