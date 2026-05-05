<!-- Global search overlay -->
<div x-show="searchOpen" x-cloak
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm"
     @click.self="searchOpen = false"
>
    <div
        x-data="globalSearch()"
        x-init="$watch('$root.searchOpen', open => { if (open) { $nextTick(() => $refs.input.focus()); } else { reset(); } })"
        class="max-w-2xl mx-auto mt-24 px-4"
    >
        <div class="bg-ink-850 border border-ink-700 rounded-xl shadow-2xl shadow-black/50 overflow-hidden">
            <div class="flex items-center gap-3 px-4 py-3 border-b border-ink-700">
                <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    x-ref="input"
                    x-model="query"
                    @input.debounce.250ms="search()"
                    type="text"
                    placeholder="Search articles, clients, users..."
                    class="flex-1 bg-transparent text-sm text-gray-100 placeholder-gray-500 focus:outline-none"
                />
                <kbd class="px-1.5 py-0.5 rounded bg-ink-700 border border-ink-600 text-[10px] font-mono text-gray-400">ESC</kbd>
            </div>

            <div class="max-h-96 overflow-y-auto">
                <template x-if="loading">
                    <div class="px-4 py-6 text-center text-xs text-gray-500">Searching...</div>
                </template>

                <template x-if="!loading && hasNoResults">
                    <div class="px-4 py-12 text-center">
                        <svg class="w-7 h-7 text-gray-700 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-gray-400">No results for "<span x-text="query"></span>"</p>
                    </div>
                </template>

                <template x-if="!loading && query.length < 2">
                    <div class="px-4 py-8">
                        <p class="text-xs text-gray-500 text-center mb-4">Type at least 2 characters</p>
                        <div class="flex flex-wrap items-center justify-center gap-2 text-xs text-gray-500">
                            <span>Searches:</span>
                            <span class="px-2 py-0.5 rounded bg-ink-700 border border-ink-600">articles</span>
                            <span class="px-2 py-0.5 rounded bg-ink-700 border border-ink-600">clients</span>
                            <span x-show="{{ auth()->user()->isAdmin() ? 'true' : 'false' }}" class="px-2 py-0.5 rounded bg-ink-700 border border-ink-600">users</span>
                        </div>
                    </div>
                </template>

                <template x-if="!loading && results.articles.length > 0">
                    <div class="border-b border-ink-700">
                        <p class="px-4 py-2 text-[10px] font-medium text-gray-500 uppercase tracking-wider">Articles</p>
                        <template x-for="r in results.articles" :key="'a-'+r.id">
                            <a :href="r.url" class="flex items-center gap-3 px-4 py-2.5 hover:bg-ink-700 transition-colors">
                                <span class="text-xs font-mono text-gray-500 w-14 flex-shrink-0" x-text="r.code"></span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-100 truncate" x-text="r.title"></p>
                                    <p class="text-xs text-gray-500 truncate" x-text="r.meta"></p>
                                </div>
                                <span :class="stageBadgeClass(r.stage_color)" class="px-2 py-0.5 rounded text-xs font-medium" x-text="r.stage_label"></span>
                            </a>
                        </template>
                    </div>
                </template>

                <template x-if="!loading && results.clients.length > 0">
                    <div class="border-b border-ink-700">
                        <p class="px-4 py-2 text-[10px] font-medium text-gray-500 uppercase tracking-wider">Clients</p>
                        <template x-for="r in results.clients" :key="'c-'+r.id">
                            <a :href="r.url" class="flex items-center gap-3 px-4 py-2.5 hover:bg-ink-700 transition-colors">
                                <svg class="w-4 h-4 text-blue-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-100 truncate" x-text="r.name"></p>
                                    <p class="text-xs text-gray-500 truncate" x-text="r.meta"></p>
                                </div>
                            </a>
                        </template>
                    </div>
                </template>

                <template x-if="!loading && results.users.length > 0">
                    <div>
                        <p class="px-4 py-2 text-[10px] font-medium text-gray-500 uppercase tracking-wider">Users</p>
                        <template x-for="r in results.users" :key="'u-'+r.id">
                            <a :href="r.url" class="flex items-center gap-3 px-4 py-2.5 hover:bg-ink-700 transition-colors">
                                <svg class="w-4 h-4 text-violet-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-100 truncate" x-text="r.name"></p>
                                    <p class="text-xs text-gray-500 truncate" x-text="r.meta"></p>
                                </div>
                            </a>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
    function globalSearch() {
        return {
            query: '',
            loading: false,
            results: { articles: [], clients: [], users: [] },
            get hasNoResults() {
                return this.query.length >= 2
                    && !this.results.articles.length
                    && !this.results.clients.length
                    && !this.results.users.length;
            },
            reset() {
                this.query = '';
                this.results = { articles: [], clients: [], users: [] };
            },
            async search() {
                if (this.query.length < 2) {
                    this.results = { articles: [], clients: [], users: [] };
                    return;
                }
                this.loading = true;
                try {
                    const r = await fetch('{{ route("search") }}?q=' + encodeURIComponent(this.query), {
                        headers: { 'Accept': 'application/json' },
                    });
                    this.results = await r.json();
                } catch (e) { /* ignore */ }
                this.loading = false;
            },
            stageBadgeClass(color) {
                const map = {
                    gray:    'bg-gray-700 text-gray-300',
                    blue:    'bg-blue-950 text-blue-300',
                    indigo:  'bg-indigo-950 text-indigo-300',
                    amber:   'bg-amber-950 text-amber-300',
                    pink:    'bg-pink-950 text-pink-300',
                    orange:  'bg-orange-950 text-orange-300',
                    emerald: 'bg-emerald-950 text-emerald-300',
                    green:   'bg-green-950 text-green-300',
                };
                return map[color] || map.gray;
            },
        }
    }
</script>
