<x-app-layout>
    <x-slot name="header">Inbox</x-slot>
    <x-slot name="title">Inbox</x-slot>

    @php
        $user = auth()->user();
        $isAdmin = $user->isAdmin();
    @endphp

    {{-- ==================== Two-pane chat layout (inline styles to bypass Tailwind JIT misses) ==================== --}}
    <div style="display: flex; height: calc(100vh - 3.5rem); overflow: hidden;">

        {{-- =============== LEFT PANE — Conversation list =============== --}}
        <aside style="width: 340px; flex-shrink: 0; display: flex; flex-direction: column;"
               class="border-r border-ink-700 bg-ink-900 {{ $active ? 'hidden md:flex' : '' }}"
               x-data="{ newOpen: false, q: '' }">

            {{-- Header --}}
            <div class="px-5 pt-5 pb-3 flex-shrink-0">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-100">Chats</h2>
                        <p class="text-[11px] text-gray-500 mt-0.5">
                            {{ $conversations->count() }} {{ Str::plural('conversation', $conversations->count()) }}
                            @if($showingAll) · <span class="text-amber-400">Admin view</span>@endif
                        </p>
                    </div>
                    <button @click="newOpen = true"
                            class="inline-flex items-center justify-center w-9 h-9 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl shadow-lg shadow-indigo-500/30 transition-colors"
                            title="New conversation">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                    </button>
                </div>

                {{-- Search bar --}}
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" x-model="q" placeholder="Search"
                           class="w-full pl-9 pr-3 py-2 text-sm bg-ink-800 border border-ink-700 rounded-lg text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500/50"/>
                </div>

                @if($isAdmin)
                    <div class="inline-flex bg-ink-800 border border-ink-700 rounded-lg p-0.5 w-full mt-3">
                        <a href="{{ route('inbox.index') }}"
                           class="flex-1 text-center px-2 py-1 text-[11px] font-medium rounded-md transition-colors {{ ! $showingAll ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:text-gray-200' }}">
                            Mine
                        </a>
                        <a href="{{ route('inbox.index', ['all' => 1]) }}"
                           class="flex-1 text-center px-2 py-1 text-[11px] font-medium rounded-md transition-colors {{ $showingAll ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:text-gray-200' }}">
                            All
                        </a>
                    </div>
                @endif
            </div>

            <div class="px-5 pb-2 flex-shrink-0">
                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">All Chats</p>
            </div>

            {{-- Conversation list --}}
            <div class="flex-1 overflow-y-auto px-2 pb-4">
                @if($conversations->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-ink-800 border border-ink-700 flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-200">No conversations yet</p>
                        <p class="text-xs text-gray-500 mt-1">Tap <span class="text-indigo-400 font-medium">+</span> to start one.</p>
                    </div>
                @else
                    @foreach($conversations as $c)
                        @php
                            $participant = $c->participants->firstWhere('user_id', $user->id);
                            $isPinned    = $participant?->pinned;
                            $unread      = $c->unreadCountFor($user);
                            $lastMsg     = $c->lastMessage;
                            $other       = $c->participants->where('user_id', '!=', $user->id)->first()?->user;
                            $isActive    = $active && $active->id === $c->id;
                            $title       = $c->displayTitle($user);
                            $searchKey   = strtolower($title);
                        @endphp
                        <a href="{{ route('inbox.index', ['open' => $c->id]) }}"
                           x-show="q === '' || '{{ $searchKey }}'.includes(q.toLowerCase())"
                           class="flex items-center gap-3 px-3 py-3 rounded-xl mb-1 transition-all {{ $isActive ? 'bg-indigo-500/15 ring-1 ring-indigo-500/30' : 'hover:bg-ink-800/60' }}">
                            {{-- Avatar --}}
                            <div class="relative flex-shrink-0">
                                <div class="w-11 h-11 rounded-full bg-gradient-to-br
                                    @if($c->participants->count() > 2) from-pink-500 to-violet-600
                                    @elseif($other && $other->role === 'admin') from-rose-500 to-orange-600
                                    @elseif($other && $other->role === 'sales') from-indigo-500 to-violet-600
                                    @elseif($other && $other->role === 'tech_team') from-emerald-500 to-teal-600
                                    @else from-slate-500 to-slate-600
                                    @endif
                                    flex items-center justify-center text-white font-semibold text-sm shadow-md">
                                    @if($c->participants->count() > 2)
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    @else
                                        {{ strtoupper(substr($other?->name ?? '?', 0, 1)) }}
                                    @endif
                                </div>
                                @if($unread > 0 && ! $isActive)
                                    <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 bg-rose-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center border-2 border-ink-900 shadow">{{ $unread > 9 ? '9+' : $unread }}</span>
                                @endif
                            </div>

                            {{-- Body --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold {{ $unread > 0 && ! $isActive ? 'text-white' : 'text-gray-200' }} truncate flex items-center gap-1">
                                        @if($isPinned)
                                            <svg class="w-3 h-3 text-amber-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M9 4v6L5 14v2h10v-2l-4-4V4h2V2H7v2h2z"/></svg>
                                        @endif
                                        {{ $title }}
                                    </p>
                                    @if($lastMsg)
                                        <span class="text-[10px] {{ $unread > 0 && ! $isActive ? 'text-indigo-400 font-semibold' : 'text-gray-500' }} whitespace-nowrap flex-shrink-0">{{ $lastMsg->created_at->diffForHumans(short: true) }}</span>
                                    @endif
                                </div>

                                <div class="flex items-center gap-1.5 mt-0.5">
                                    @if($lastMsg && $lastMsg->user_id === $user->id)
                                        <svg class="w-3 h-3 text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @endif
                                    <p class="text-xs text-gray-500 truncate flex-1">
                                        @if($lastMsg)
                                            @if($lastMsg->user_id === $user->id)
                                                <span class="text-gray-600">You:</span>
                                            @endif
                                            {{ $lastMsg->body ?: ($lastMsg->hasAttachment() ? '📎 ' . $lastMsg->attachment_filename : '') }}
                                        @else
                                            <em class="text-gray-600">No messages yet</em>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </a>
                    @endforeach
                @endif
            </div>

            {{-- ==================== New conversation modal (teleported to body) ==================== --}}
            <template x-teleport="body">
                <div x-show="newOpen" x-cloak x-transition.opacity
                     style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(0,0,0,0.65); padding: 1rem; overflow-y: auto;"
                     @click.self="newOpen = false"
                     @keydown.escape.window="newOpen = false">
                    <div style="width: 420px; max-width: calc(100vw - 2rem); margin: 4rem auto;"
                         class="bg-ink-850 border border-ink-700 rounded-xl shadow-2xl overflow-hidden"
                         x-data="{ search: '', selected: [] }"
                         @click.stop>
                        <form method="POST" action="{{ route('inbox.store') }}">
                            @csrf
                            <div class="px-4 py-3 border-b border-ink-700 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-100">New conversation</h3>
                                <button type="button" @click="newOpen = false" class="text-gray-500 hover:text-gray-200">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <div class="p-4 space-y-3">
                                <div class="relative">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    <input type="text" x-model="search" placeholder="Search teammates..."
                                           class="w-full pl-9 pr-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"/>
                                </div>

                                <div style="max-height: 14rem;" class="overflow-y-auto border border-ink-700 rounded-lg">
                                    @foreach($teammates as $t)
                                        <label class="flex items-center gap-2.5 px-3 py-2 hover:bg-ink-800/60 cursor-pointer border-b border-ink-700/50 last:border-b-0"
                                               x-show="search === '' || '{{ strtolower($t->name) }}'.includes(search.toLowerCase())">
                                            <input type="checkbox" name="participants[]" value="{{ $t->id }}"
                                                   @change="selected = selected.includes({{ $t->id }}) ? selected.filter(i => i !== {{ $t->id }}) : [...selected, {{ $t->id }}]"
                                                   class="w-4 h-4 rounded border-ink-500 bg-ink-800 text-indigo-600 focus:ring-indigo-500"/>
                                            <div class="w-7 h-7 rounded-full bg-gradient-to-br
                                                @if($t->role === 'admin') from-rose-500 to-orange-600
                                                @elseif($t->role === 'sales') from-indigo-500 to-violet-600
                                                @else from-emerald-500 to-teal-600
                                                @endif
                                                flex items-center justify-center text-white font-semibold text-xs flex-shrink-0">
                                                {{ strtoupper(substr($t->name, 0, 1)) }}
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm text-gray-100 truncate leading-tight">{{ $t->name }}</p>
                                                <p class="text-[10px] text-gray-500 uppercase tracking-wider">{{ str_replace('_', ' ', $t->role) }}</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>

                                <div x-show="selected.length > 1" x-cloak>
                                    <input name="title" type="text" maxlength="120"
                                           placeholder="Group name (optional)"
                                           class="w-full px-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"/>
                                </div>

                                <textarea name="first_message" rows="2" maxlength="5000"
                                          placeholder="First message (optional)"
                                          class="w-full px-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 resize-none"></textarea>
                            </div>

                            <div class="px-4 py-3 bg-ink-900/40 border-t border-ink-700 flex items-center justify-between gap-2">
                                <span x-show="selected.length > 0" class="text-[11px] text-indigo-400" x-text="selected.length + ' selected'"></span>
                                <span x-show="selected.length === 0" class="text-[11px] text-gray-500">Pick at least one</span>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="newOpen = false" class="px-3 py-1.5 text-xs text-gray-400 hover:text-gray-200 rounded-lg transition-colors">Cancel</button>
                                    <button type="submit" :disabled="selected.length === 0"
                                            class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-semibold rounded-lg transition-colors">Start</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </template>
        </aside>

        {{-- =============== RIGHT PANE — Active conversation =============== --}}
        <main style="flex: 1 1 0%; display: flex; flex-direction: column; min-width: 0;"
              class="bg-ink-900 {{ $active ? '' : 'hidden md:flex' }}">
            @if($active)
                @include('inbox._thread', ['conversation' => $active])
            @else
                <div style="flex: 1 1 0%; display: flex; align-items: center; justify-content: center;" class="p-8">
                    <div class="text-center max-w-sm">
                        <div class="w-20 h-20 mx-auto mb-4 rounded-3xl bg-gradient-to-br from-indigo-500/20 to-violet-500/20 border border-indigo-500/30 flex items-center justify-center shadow-xl shadow-indigo-500/10">
                            <svg class="w-10 h-10 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-100 mb-2">Your messages</h3>
                        <p class="text-sm text-gray-500">Pick a conversation from the left, or start a new chat.</p>
                    </div>
                </div>
            @endif
        </main>
    </div>
</x-app-layout>
