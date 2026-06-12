@php
    /** @var \App\Models\InboxConversation $conversation */
    $user        = auth()->user();
    $participant = $conversation->participants->firstWhere('user_id', $user->id);
    $context     = $conversation->context();
    $others      = $conversation->participants->where('user_id', '!=', $user->id);
    $headerOther = $others->first()?->user;
    $isPinned    = $participant?->pinned;
    $msgCount    = $conversation->messages->count();
@endphp

<div class="flex flex-col h-full bg-ink-900">
    {{-- ==================== Thread header ==================== --}}
    <div class="flex items-center justify-between gap-3 px-6 py-3.5 border-b border-ink-700 bg-ink-850/80 backdrop-blur flex-shrink-0">
        <div class="flex items-center gap-3 min-w-0">
            {{-- Back button on mobile --}}
            <a href="{{ route('inbox.index') }}" class="sm:hidden text-gray-400 hover:text-gray-200">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>

            <div class="relative flex-shrink-0">
                <div class="w-11 h-11 rounded-full bg-gradient-to-br
                    @if($others->count() > 1)
                        from-pink-500 to-violet-600
                    @elseif($headerOther && $headerOther->role === 'admin')
                        from-rose-500 to-orange-600
                    @elseif($headerOther && $headerOther->role === 'sales')
                        from-indigo-500 to-violet-600
                    @elseif($headerOther && $headerOther->role === 'tech_team')
                        from-emerald-500 to-teal-600
                    @else
                        from-slate-500 to-slate-600
                    @endif
                    flex items-center justify-center text-white font-semibold text-base shadow-lg">
                    @if($others->count() > 1)
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    @else
                        {{ strtoupper(substr($headerOther?->name ?? '?', 0, 1)) }}
                    @endif
                </div>
                <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 border-2 border-ink-850 rounded-full"></span>
            </div>

            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-100 truncate">{{ $conversation->displayTitle($user) }}</p>
                <p class="text-[11px] text-gray-500 truncate">
                    @if($others->count() > 1)
                        {{ $conversation->participants->count() }} members ·
                        @foreach($others->take(3) as $p)
                            <span class="text-gray-400">{{ $p->user?->name }}</span>@if(! $loop->last), @endif
                        @endforeach
                    @else
                        <span class="inline-flex items-center gap-1">
                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                            {{ ucwords(str_replace('_', ' ', $headerOther?->role ?? '')) }}
                        </span>
                    @endif
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1 flex-shrink-0">
            @if($participant)
                <form method="POST" action="{{ route('inbox.pin', $conversation) }}" class="inline">
                    @csrf
                    <button type="submit" title="{{ $isPinned ? 'Unpin' : 'Pin' }}"
                            class="inline-flex items-center justify-center w-9 h-9 {{ $isPinned ? 'text-amber-400' : 'text-gray-400 hover:text-gray-200' }} hover:bg-ink-700 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="{{ $isPinned ? 'currentColor' : 'none' }}" viewBox="0 0 20 20" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 4v6L5 14v2h10v-2l-4-4V4h2V2H7v2h2z"/>
                        </svg>
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- ==================== Context banner ==================== --}}
    @if($context)
        <div class="px-6 py-2 bg-indigo-500/10 border-b border-indigo-500/20 flex-shrink-0">
            <div class="flex items-center gap-2 text-xs">
                <svg class="w-3.5 h-3.5 text-indigo-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    @if($conversation->context_type === 'article')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    @endif
                </svg>
                <span class="text-gray-400">About:</span>
                <span class="text-indigo-300 font-medium">
                    @if($conversation->context_type === 'article')
                        {{ $context->article_code }} · {{ $context->title }}
                    @else
                        Viral package · {{ $context->client?->name }}
                    @endif
                </span>
            </div>
        </div>
    @endif

    {{-- ==================== Messages stream ==================== --}}
    <div class="flex-1 overflow-y-auto px-6 py-6" id="messages-scroll"
         style="background-image: radial-gradient(circle at 20% 20%, rgba(99,102,241,0.04) 0%, transparent 50%), radial-gradient(circle at 80% 80%, rgba(168,85,247,0.04) 0%, transparent 50%);">

        @if($msgCount === 0)
            {{-- Empty state — inviting hero --}}
            <div class="h-full flex flex-col items-center justify-center text-center px-6">
                <div class="relative mb-6">
                    <div class="w-24 h-24 rounded-3xl bg-gradient-to-br
                        @if($others->count() > 1) from-pink-500/20 to-violet-500/20
                        @elseif($headerOther && $headerOther->role === 'admin') from-rose-500/20 to-orange-500/20
                        @elseif($headerOther && $headerOther->role === 'sales') from-indigo-500/20 to-violet-500/20
                        @else from-emerald-500/20 to-teal-500/20
                        @endif
                        border border-indigo-500/30 flex items-center justify-center shadow-xl shadow-indigo-500/10">
                        <svg class="w-12 h-12 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <span class="absolute -bottom-1 -right-1 px-2 py-0.5 bg-indigo-500 text-white text-[9px] font-bold rounded-full uppercase tracking-wider shadow-lg">New</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-100 mb-1.5">
                    @if($others->count() > 1)
                        Start the group chat
                    @else
                        Say hi to {{ $headerOther?->name ?? 'your teammate' }}
                    @endif
                </h3>
                <p class="text-sm text-gray-500 max-w-sm mb-6">
                    This is the start of your conversation. Messages are private to the participants.
                </p>

                {{-- Quick start chips --}}
                <div class="flex flex-wrap items-center justify-center gap-2 max-w-md">
                    @foreach(['👋 Hi there!', '📋 Quick question', '🚀 Got an update', '✅ Looks good'] as $suggestion)
                        <button type="button"
                                onclick="document.querySelector('#message-input-{{ $conversation->id }}').value='{{ $suggestion }}'.replace(/^[^\s]+\s/, ''); document.querySelector('#message-input-{{ $conversation->id }}').focus();"
                                class="px-3 py-1.5 bg-ink-800 hover:bg-ink-700 border border-ink-600 hover:border-indigo-500/50 rounded-full text-xs text-gray-300 transition-colors">
                            {{ $suggestion }}
                        </button>
                    @endforeach
                </div>
            </div>
        @else
            @php $prevSender = null; $prevDate = null; @endphp
            @foreach($conversation->messages as $msg)
                @php
                    $isOwn      = $msg->user_id === $user->id;
                    $sameSender = $prevSender === $msg->user_id;
                    $date       = $msg->created_at->format('Y-m-d');
                    $newDay     = $prevDate !== $date;
                    $prevSender = $msg->user_id;
                    $prevDate   = $date;
                @endphp

                @if($newDay)
                    <div class="flex items-center gap-3 my-6">
                        <div class="flex-1 h-px bg-gradient-to-r from-transparent via-ink-700 to-transparent"></div>
                        <span class="px-3 py-1 bg-ink-800 border border-ink-700 rounded-full text-[10px] text-gray-400 uppercase tracking-wider font-semibold">
                            @if($date === now()->format('Y-m-d'))
                                Today
                            @elseif($date === now()->subDay()->format('Y-m-d'))
                                Yesterday
                            @else
                                {{ $msg->created_at->format('F j, Y') }}
                            @endif
                        </span>
                        <div class="flex-1 h-px bg-gradient-to-r from-transparent via-ink-700 to-transparent"></div>
                    </div>
                @endif

                <div class="flex items-end gap-2.5 {{ $isOwn ? 'flex-row-reverse' : '' }} {{ $sameSender && ! $newDay ? 'mt-1' : 'mt-4' }}">
                    {{-- Avatar (only on first of grouped messages) --}}
                    <div class="w-8 h-8 flex-shrink-0">
                        @if(! $sameSender || $newDay)
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br
                                @if($msg->user?->role === 'admin') from-rose-500 to-orange-600
                                @elseif($msg->user?->role === 'sales') from-indigo-500 to-violet-600
                                @else from-emerald-500 to-teal-600
                                @endif
                                flex items-center justify-center text-white text-xs font-semibold shadow-md">
                                {{ strtoupper(substr($msg->user?->name ?? '?', 0, 1)) }}
                            </div>
                        @endif
                    </div>

                    <div class="max-w-[70%] {{ $isOwn ? 'items-end' : 'items-start' }} flex flex-col">
                        <div class="rounded-2xl px-4 py-2.5 shadow-sm
                            {{ $isOwn
                                ? 'bg-gradient-to-br from-indigo-600 to-indigo-700 text-white rounded-br-md'
                                : 'bg-ink-800 text-gray-100 border border-ink-700 rounded-bl-md' }}">

                            @if($msg->body)
                                <p class="text-sm whitespace-pre-wrap break-words leading-relaxed">{{ $msg->body }}</p>
                            @endif

                            @if($msg->hasAttachment())
                                <a href="{{ route('inbox.attachment.download', ['conversation' => $conversation, 'message' => $msg]) }}"
                                   class="mt-2 flex items-center gap-2.5 px-3 py-2 rounded-xl
                                   {{ $isOwn ? 'bg-white/10 hover:bg-white/20' : 'bg-ink-700 hover:bg-ink-600' }} transition-colors">
                                    <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0
                                        {{ $isOwn ? 'bg-white/10 text-white' : 'bg-ink-800 text-indigo-400' }}">
                                        @if($msg->attachmentKind() === 'image')
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        @elseif($msg->attachmentKind() === 'video')
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-medium {{ $isOwn ? 'text-white' : 'text-gray-100' }} truncate">{{ $msg->attachment_filename }}</p>
                                        @if($msg->attachment_size)
                                            <p class="text-[10px] {{ $isOwn ? 'text-white/70' : 'text-gray-500' }}">{{ number_format($msg->attachment_size / 1024 / 1024, 2) }} MB</p>
                                        @endif
                                    </div>
                                    <svg class="w-3.5 h-3.5 {{ $isOwn ? 'text-white/70' : 'text-gray-500' }} flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                </a>
                            @endif
                        </div>

                        {{-- Name + time + delivery indicator (BELOW bubble, like reference) --}}
                        @php
                            $nextMsg = $conversation->messages[$loop->index + 1] ?? null;
                            $lastInGroup = ! $nextMsg || $nextMsg->user_id !== $msg->user_id || $nextMsg->created_at->format('Y-m-d') !== $date;
                        @endphp
                        @if($lastInGroup)
                            <div class="flex items-center gap-1.5 mt-1 px-1 {{ $isOwn ? 'flex-row-reverse' : '' }}">
                                <span class="text-[10px] text-gray-500">{{ $msg->created_at->format('g:i A') }}</span>
                                <span class="text-[10px] text-gray-600">·</span>
                                <span class="text-[10px] font-medium text-gray-400">{{ $isOwn ? 'You' : ($msg->user?->name ?? 'Unknown') }}</span>
                                @if($isOwn)
                                    <svg class="w-3 h-3 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- ==================== Compose area ==================== --}}
    @if($participant || $user->isAdmin())
        <div class="border-t border-ink-700 bg-ink-850 px-4 py-3 flex-shrink-0">
            <form method="POST" enctype="multipart/form-data"
                  action="{{ route('inbox.messages.store', $conversation) }}"
                  x-data="{ attachmentName: '', body: '' }"
                  class="flex flex-col gap-2">
                @csrf

                {{-- Attachment chip --}}
                <div x-show="attachmentName" x-cloak style="display: none;"
                     class="flex items-center gap-2 px-3 py-2 bg-indigo-500/10 border border-indigo-500/30 rounded-lg text-xs text-indigo-300">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                    <span class="truncate flex-1" x-text="attachmentName"></span>
                    <button type="button" @click="attachmentName=''; document.getElementById('attachment-{{ $conversation->id }}').value=''"
                            class="text-gray-500 hover:text-rose-400">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Input row --}}
                <div class="flex items-end gap-2 bg-ink-800 border border-ink-600 focus-within:border-indigo-500/60 focus-within:ring-2 focus-within:ring-indigo-500/20 rounded-2xl px-2 py-1.5 transition-all">
                    {{-- Attachment button --}}
                    <label for="attachment-{{ $conversation->id }}"
                           class="inline-flex items-center justify-center w-9 h-9 text-gray-400 hover:text-indigo-400 hover:bg-ink-700 rounded-xl cursor-pointer transition-colors flex-shrink-0"
                           title="Attach file">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                    </label>
                    <input type="file" id="attachment-{{ $conversation->id }}" name="attachment"
                           @change="attachmentName = $event.target.files[0]?.name || ''"
                           class="hidden"/>

                    {{-- Textarea --}}
                    <textarea id="message-input-{{ $conversation->id }}" name="body" rows="1" maxlength="5000"
                              x-model="body"
                              placeholder="Type a message..."
                              @keydown.enter.prevent="if (! $event.shiftKey && (body.trim() || attachmentName)) { $el.form.submit(); }"
                              @input="$el.style.height='auto'; $el.style.height=Math.min($el.scrollHeight, 128)+'px';"
                              class="flex-1 min-w-0 px-2 py-2 text-sm bg-transparent border-0 text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-0 resize-none max-h-32 leading-relaxed"></textarea>

                    {{-- Send button --}}
                    <button type="submit"
                            :disabled="!body.trim() && !attachmentName"
                            :class="(body.trim() || attachmentName) ? 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-md shadow-indigo-500/30' : 'bg-ink-700 text-gray-500 cursor-not-allowed'"
                            class="inline-flex items-center justify-center w-9 h-9 rounded-xl transition-all flex-shrink-0"
                            title="Send (Enter)">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12l14-7-3 9 3 9-14-7v-4z"/>
                        </svg>
                    </button>
                </div>

                <p class="text-[10px] text-gray-600 px-2 flex items-center gap-2">
                    <kbd class="px-1.5 py-0.5 bg-ink-800 border border-ink-700 rounded text-[9px] text-gray-500 font-mono">Enter</kbd> send
                    <span class="text-gray-700">·</span>
                    <kbd class="px-1.5 py-0.5 bg-ink-800 border border-ink-700 rounded text-[9px] text-gray-500 font-mono">Shift+Enter</kbd> new line
                    <span class="text-gray-700">·</span>
                    <span>Max 50MB attachment</span>
                </p>
            </form>
        </div>
    @else
        <div class="border-t border-ink-700 bg-ink-850 p-4 flex-shrink-0 text-center">
            <p class="text-xs text-gray-500 inline-flex items-center gap-2">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                Read-only — you're not a participant in this conversation
            </p>
        </div>
    @endif
</div>

<script>
    // Auto-scroll to bottom of messages on load
    setTimeout(() => {
        const scroll = document.getElementById('messages-scroll');
        if (scroll) scroll.scrollTop = scroll.scrollHeight;
    }, 50);
</script>
