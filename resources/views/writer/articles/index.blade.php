<x-app-layout>
    <x-slot name="header">{{ request('stage') === 'revisions' ? 'Revisions needed' : 'Assignments' }}</x-slot>
    <x-slot name="title">{{ request('stage') === 'revisions' ? 'Revisions' : 'Assignments' }}</x-slot>

    <div class="p-6">

        <div class="mb-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ request('stage') === 'revisions' ? 'Revisions needed' : 'Assigned articles' }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                {{ request('stage') === 'revisions'
                    ? 'Articles that bounced back from review or client.'
                    : 'Everything currently on your plate.' }}
            </p>
        </div>

        <!-- Filters -->
        <form method="GET" class="flex flex-wrap items-center gap-2 mb-4">
            <div class="relative flex-1 min-w-[200px]">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Search by title or code"
                       class="w-full pl-9 pr-3 py-1.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"/>
            </div>

            <select name="stage" class="px-3 py-1.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All stages</option>
                @foreach($stages as $s)
                    <option value="{{ $s->value }}" @selected(request('stage') === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>

            <button type="submit" class="px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">Filter</button>
            @if(request()->hasAny(['q', 'stage']))
                <a href="{{ route('writer.articles.index') }}" class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">Clear</a>
            @endif
        </form>

        <div data-live="writer-articles-list" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
            @if($articles->count() === 0)
                <div class="p-12 text-center">
                    <svg class="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ request('stage') === 'revisions' ? 'No revisions outstanding.' : 'No assignments yet.' }}
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead class="bg-ink-800/40 border-b border-ink-700">
                        <tr>
                            <th class="text-left px-4 py-3 font-medium text-[10px] text-gray-500 uppercase tracking-wider w-20">Code</th>
                            <th class="text-left px-4 py-3 font-medium text-[10px] text-gray-500 uppercase tracking-wider">Article</th>
                            <th class="text-left px-4 py-3 font-medium text-[10px] text-gray-500 uppercase tracking-wider w-32">Stage</th>
                            <th class="text-left px-4 py-3 font-medium text-[10px] text-gray-500 uppercase tracking-wider w-32">Deadline</th>
                            <th class="text-right px-4 py-3 font-medium text-[10px] text-gray-500 uppercase tracking-wider w-72">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-700">
                        @foreach($articles as $a)
                            @php
                                $isInbox = $a->current_stage === \App\Enums\ArticleStage::INBOX;
                                $isAdmin = auth()->user()->isAdmin();
                            @endphp
                            <tr class="group hover:bg-ink-800/40 transition-colors {{ $isInbox ? 'bg-indigo-500/5' : '' }}">
                                <td class="px-4 py-3.5 align-middle">
                                    <a href="{{ route('writer.articles.show', $a) }}" class="font-mono text-xs text-gray-500 group-hover:text-indigo-400 transition-colors">
                                        {{ $a->article_code }}
                                    </a>
                                </td>
                                <td class="px-4 py-3.5 align-middle">
                                    <a href="{{ route('writer.articles.show', $a) }}" class="block">
                                        <div class="flex items-baseline gap-2 flex-wrap">
                                            <p class="text-sm font-medium text-gray-100">{{ $a->title }}</p>
                                            @if($a->priority === 'high')
                                                <span class="inline-flex items-center gap-0.5 text-[10px] text-rose-400 font-medium">
                                                    <span class="w-1 h-1 rounded-full bg-rose-400"></span> High
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-1.5 mt-1 text-xs text-gray-500 flex-wrap">
                                            @if($isInbox)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-indigo-500/15 text-indigo-300 border border-indigo-500/30">Available</span>
                                            @endif
                                            @if($a->articleType)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-violet-500/10 text-violet-300 border border-violet-500/20">{{ $a->articleType->name }}</span>
                                            @endif
                                            @if($a->client)
                                                <span>·</span>
                                                <span class="truncate">{{ $a->client->displayName() }}</span>
                                            @endif
                                            @if($a->salesRep)
                                                <span>·</span>
                                                <span>Sales: <span class="text-gray-300">{{ $a->salesRep->name }}</span></span>
                                            @endif
                                            <span>·</span>
                                            @if($a->techWriter)
                                                <span>Writer:
                                                    <span class="inline-flex items-center gap-1 text-emerald-300">
                                                        <span class="w-3 h-3 rounded-full bg-emerald-500/20 text-emerald-300 flex items-center justify-center text-[8px] font-bold">{{ strtoupper(substr($a->techWriter->name, 0, 1)) }}</span>
                                                        {{ $a->techWriter->name }}
                                                    </span>
                                                </span>
                                            @else
                                                <span class="text-amber-400 italic">Unassigned</span>
                                            @endif
                                            @if($a->submitted_at)
                                                <span>·</span>
                                                <span class="text-gray-600">{{ $a->submitted_at->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE, short: true) }} ago</span>
                                            @endif
                                        </div>
                                    </a>
                                </td>
                                <td class="px-4 py-3.5 align-middle"><x-stage-badge :stage="$a->current_stage" /></td>
                                <td class="px-4 py-3.5 align-middle text-xs">
                                    @if($a->deadline)
                                        @php $days = $a->days_until_deadline; @endphp
                                        @if($days < 0)
                                            <span class="inline-flex items-center gap-1 text-rose-400">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                                {{ $a->deadline->format('M j') }} <span class="text-rose-500">({{ abs($days) }}d late)</span>
                                            </span>
                                        @elseif($days === 0)
                                            <span class="inline-flex items-center gap-1 text-rose-400 font-medium">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-pulse"></span> Today
                                            </span>
                                        @elseif($days <= 2)
                                            <span class="text-amber-400">{{ $a->deadline->format('M j') }} <span class="text-amber-500/70">({{ $days }}d)</span></span>
                                        @elseif($days <= 7)
                                            <span class="text-gray-300">{{ $a->deadline->format('M j') }} <span class="text-gray-500">({{ $days }}d)</span></span>
                                        @else
                                            <span class="text-gray-400">{{ $a->deadline->format('M j') }}</span>
                                        @endif
                                    @else
                                        <span class="text-gray-600">No deadline</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-right align-middle">
                                    <div class="inline-flex items-center gap-1.5">
                                        @if($a->current_drive_file_id)
                                            <a href="{{ route('writer.articles.download', $a) }}"
                                               class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium bg-ink-700 hover:bg-ink-600 border border-ink-600 text-gray-200 rounded-md transition-colors">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                </svg>
                                                Download
                                            </a>
                                        @endif
                                        @if($isInbox)
                                            <form method="POST" action="{{ route('writer.articles.pick-up', $a) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white rounded-md shadow shadow-indigo-500/20 transition-all">
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                                                    </svg>
                                                    Pick up
                                                </button>
                                            </form>
                                        @endif
                                        @if($isAdmin)
                                            <form method="POST" action="{{ route('writer.articles.destroy', $a) }}"
                                                  onsubmit="return confirm('Delete {{ $a->article_code }} — {{ $a->title }}? The Drive file will also be removed. This cannot be undone.');"
                                                  class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" title="Delete article"
                                                        class="inline-flex items-center justify-center w-7 h-7 text-gray-500 hover:text-rose-400 hover:bg-rose-500/10 border border-transparent hover:border-rose-500/30 rounded-md transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
                <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800">
                    {{ $articles->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
