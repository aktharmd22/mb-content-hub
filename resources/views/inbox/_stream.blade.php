{{-- Renders new message bubbles for polling endpoint. Simpler than full thread — always shows avatar + name + time on each bubble. --}}
@foreach($messages as $msg)
    @php
        $isOwn = $msg->user_id === $user->id;
    @endphp

    <div data-message-id="{{ $msg->id }}"
         style="display: flex; align-items: flex-end; gap: 0.625rem; {{ $isOwn ? 'flex-direction: row-reverse;' : '' }}"
         class="mt-4">
        <div class="w-8 h-8 flex-shrink-0">
            <div class="w-8 h-8 rounded-full bg-gradient-to-br
                @if($msg->user?->role === 'admin') from-rose-500 to-orange-600
                @elseif($msg->user?->role === 'sales') from-indigo-500 to-violet-600
                @else from-emerald-500 to-teal-600
                @endif
                flex items-center justify-center text-white text-xs font-semibold shadow-md">
                {{ strtoupper(substr($msg->user?->name ?? '?', 0, 1)) }}
            </div>
        </div>

        <div style="display: flex; flex-direction: column; max-width: 70%; {{ $isOwn ? 'align-items: flex-end;' : 'align-items: flex-start;' }}">
            <div class="rounded-2xl px-4 py-2.5 shadow-sm
                {{ $isOwn
                    ? 'bg-gradient-to-br from-indigo-600 to-indigo-700 text-white rounded-br-md'
                    : 'bg-ink-800 text-gray-100 border border-ink-700 rounded-bl-md' }}">

                @if($msg->body)
                    <p class="text-sm whitespace-pre-wrap break-words leading-relaxed">{{ $msg->body }}</p>
                @endif

                @if($msg->hasAttachment())
                    <a href="{{ route('inbox.attachment.download', ['conversation' => $conversation, 'message' => $msg]) }}"
                       class="mt-2 flex items-center gap-2.5 px-3 py-2 rounded-xl {{ $isOwn ? 'bg-white/10 hover:bg-white/20' : 'bg-ink-700 hover:bg-ink-600' }} transition-colors">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 {{ $isOwn ? 'bg-white/10 text-white' : 'bg-ink-800 text-indigo-400' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium {{ $isOwn ? 'text-white' : 'text-gray-100' }} truncate">{{ $msg->attachment_filename }}</p>
                            @if($msg->attachment_size)
                                <p class="text-[10px] {{ $isOwn ? 'text-white/70' : 'text-gray-500' }}">{{ number_format($msg->attachment_size / 1024 / 1024, 2) }} MB</p>
                            @endif
                        </div>
                    </a>
                @endif
            </div>

            @php
                $role = $msg->user?->role;
                $roleLabel = match($role) {
                    'admin'        => 'Admin',
                    'sales'        => 'Sales',
                    'tech_team'    => 'Tech Team',
                    'content_team' => 'Content Team',
                    default        => strtoupper(str_replace('_', ' ', (string) $role)),
                };
                $roleClass = match($role) {
                    'admin'        => 'bg-rose-500/15 text-rose-300 border-rose-500/30',
                    'sales'        => 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30',
                    'tech_team'    => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
                    'content_team' => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
                    default        => 'bg-gray-500/15 text-gray-300 border-gray-500/30',
                };
            @endphp
            <div style="display: flex; align-items: center; gap: 0.375rem; flex-wrap: wrap; {{ $isOwn ? 'flex-direction: row-reverse;' : '' }}" class="mt-1.5 px-1">
                <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-wider border {{ $roleClass }}">{{ $roleLabel }}</span>
                <span class="text-[10px] font-medium text-gray-300">{{ $isOwn ? 'You' : ($msg->user?->name ?? 'Unknown') }}</span>
                <span class="text-[10px] text-gray-600">·</span>
                <span class="text-[10px] text-gray-500" data-utc="{{ $msg->created_at->toIso8601String() }}" data-utc-format="time">{{ $msg->created_at->format('g:i A') }}</span>
                @if($isOwn)
                    <svg class="w-3 h-3 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                @endif
            </div>
        </div>
    </div>
@endforeach
