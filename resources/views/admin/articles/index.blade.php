<x-app-layout>
    <x-slot name="header">All articles</x-slot>
    <x-slot name="title">All articles</x-slot>

    <div class="p-6">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-medium text-gray-100">All articles</h2>
                <p class="text-sm text-gray-500 mt-0.5">Filter, search, and bulk-manage every article in the system.</p>
            </div>
            <a href="{{ route('admin.articles.export', request()->query()) }}"
               class="inline-flex items-center gap-2 px-3 py-1.5 bg-ink-800 hover:bg-ink-700 border border-ink-600 text-gray-200 text-sm font-medium rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export CSV
            </a>
        </div>

        <!-- Filters -->
        <form method="GET" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-3 mb-4 grid grid-cols-2 md:grid-cols-4 gap-2">
            <div class="col-span-2 md:col-span-2 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Search title or code"
                       class="w-full pl-9 pr-3 py-1.5 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"/>
            </div>

            <select name="stage" class="px-3 py-1.5 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All stages</option>
                @foreach($stages as $s)
                    <option value="{{ $s->value }}" @selected(request('stage') === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>

            <select name="client_id" class="px-3 py-1.5 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All clients</option>
                @foreach($clients as $c)
                    <option value="{{ $c->id }}" @selected(request('client_id') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>

            <select name="sales_rep_id" class="px-3 py-1.5 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All sales reps</option>
                @foreach($salesReps as $r)
                    <option value="{{ $r->id }}" @selected(request('sales_rep_id') == $r->id)>{{ $r->name }}</option>
                @endforeach
            </select>

            <select name="tech_writer_id" class="px-3 py-1.5 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All writers</option>
                @foreach($writers as $w)
                    <option value="{{ $w->id }}" @selected(request('tech_writer_id') == $w->id)>{{ $w->name }}</option>
                @endforeach
            </select>

            <input type="date" name="from" value="{{ request('from') }}" placeholder="From" title="Submitted after"
                   class="px-3 py-1.5 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"/>
            <input type="date" name="to" value="{{ request('to') }}" placeholder="To" title="Submitted before"
                   class="px-3 py-1.5 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"/>

            <div class="col-span-2 md:col-span-4 flex items-center gap-2">
                <button type="submit" class="px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                    Apply filters
                </button>
                @if(request()->hasAny(['q', 'stage', 'client_id', 'sales_rep_id', 'tech_writer_id', 'from', 'to']))
                    <a href="{{ route('admin.articles.index') }}" class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">Clear all</a>
                @endif
            </div>
        </form>

        <!-- Bulk + table share Alpine state, but live in SEPARATE forms so per-row delete forms aren't illegally nested. -->
        <div x-data="{ selected: [], action: '' }">

            <!-- Bulk action bar (its own form) -->
            <form method="POST" action="{{ route('admin.articles.bulk') }}"
                  x-show="selected.length > 0" x-cloak
                  @submit="if (selected.length === 0) { event.preventDefault(); window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Select at least one article.' } })); }"
                  class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-3 mb-2 flex flex-wrap items-center gap-2">
                @csrf
                <!-- Selected article IDs are mirrored from Alpine state into hidden inputs -->
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="article_ids[]" :value="id"/>
                </template>

                <span class="text-sm text-gray-700 dark:text-gray-300" x-text="selected.length + ' selected'"></span>

                <select name="action" x-model="action" required
                        class="px-3 py-1.5 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Choose action...</option>
                    <option value="reassign_writer">Reassign writer</option>
                    <option value="change_deadline">Change deadline</option>
                    <option value="archive">Archive</option>
                </select>

                <select name="tech_writer_id" x-show="action === 'reassign_writer'" x-cloak
                        class="px-3 py-1.5 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Select writer</option>
                    @foreach($writers as $w)
                        <option value="{{ $w->id }}">{{ $w->name }}</option>
                    @endforeach
                </select>

                <input type="date" name="deadline" x-show="action === 'change_deadline'" x-cloak
                       class="px-3 py-1.5 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"/>

                <button type="submit"
                        x-bind:disabled="!action"
                        @click="if (action === 'archive' && ! confirm('Archive ' + selected.length + ' articles?')) { event.preventDefault(); }"
                        class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition-colors">
                    Apply
                </button>
                <button type="button" @click="selected = []; action = ''" class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">Cancel</button>
            </form>

            <!-- Table (not inside any form, so per-row delete forms are valid) -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
                @if($articles->count() === 0)
                    <div class="p-12 text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">No articles match your filters.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[720px]">
                        <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="w-8 px-3 py-2.5">
                                    <input type="checkbox"
                                           @click="selected = $event.target.checked ? Array.from(document.querySelectorAll('.row-check')).map(c => c.value) : []"
                                           class="w-3.5 h-3.5 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500"/>
                                </th>
                                <th class="text-left px-3 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Code</th>
                                <th class="text-left px-3 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Title</th>
                                <th class="text-left px-3 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Client</th>
                                <th class="text-left px-3 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Sales</th>
                                <th class="text-left px-3 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Writer</th>
                                <th class="text-left px-3 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Stage</th>
                                <th class="text-left px-3 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Deadline</th>
                                <th class="text-right px-3 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($articles as $a)
                                <tr class="group hover:bg-gray-50 dark:hover:bg-gray-950/50 transition-colors">
                                    <td class="px-3 py-3">
                                        <input type="checkbox" value="{{ $a->id }}"
                                               x-model="selected" class="row-check w-3.5 h-3.5 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500"/>
                                    </td>
                                    <td class="px-3 py-3 text-xs font-mono text-gray-500 dark:text-gray-400">{{ $a->article_code }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $a->title }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $a->client?->name ?? '—' }}</td>
                                    <td class="px-3 py-3 text-xs text-gray-700 dark:text-gray-300">{{ $a->salesRep?->name ?? '—' }}</td>
                                    <td class="px-3 py-3 text-xs text-gray-700 dark:text-gray-300">{{ $a->techWriter?->name ?? '—' }}</td>
                                    <td class="px-3 py-3"><x-stage-badge :stage="$a->current_stage" /></td>
                                    <td class="px-3 py-3 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $a->deadline?->format('M j') ?? '—' }}
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <form method="POST" action="{{ route('admin.articles.destroy', $a) }}"
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
    </div>
</x-app-layout>
