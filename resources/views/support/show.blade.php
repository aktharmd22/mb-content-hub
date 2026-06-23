<x-app-layout>
    <x-slot name="header">Ticket {{ $ticket->code }}</x-slot>
    <x-slot name="title">{{ $ticket->code }} — {{ $ticket->subject }}</x-slot>

    @php
        $user = auth()->user();
        $statusStyle = [
            'open'         => ['bg-blue-500/15 text-blue-300',     'Open'],
            'in_progress'  => ['bg-amber-500/15 text-amber-300',   'In Progress'],
            'waiting_user' => ['bg-violet-500/15 text-violet-300', 'Waiting on user'],
            'resolved'     => ['bg-emerald-500/15 text-emerald-300','Resolved'],
            'closed'       => ['bg-gray-500/15 text-gray-400',     'Closed'],
        ];
        $priorityStyle = [
            'urgent' => ['bg-rose-500/15 text-rose-300',     '● Urgent'],
            'high'   => ['bg-orange-500/15 text-orange-300', '● High'],
            'normal' => ['bg-slate-500/15 text-slate-300',   '● Normal'],
            'low'    => ['bg-gray-500/15 text-gray-400',     '● Low'],
        ];
        $roleLabel = fn($r) => match($r) {
            'admin' => 'Admin', 'sales' => 'Sales',
            'tech_team' => 'Tech Team', 'content_team' => 'Content Team',
            default => strtoupper(str_replace('_', ' ', (string) $r)),
        };
        $roleChip = fn($r) => match($r) {
            'admin'        => 'bg-rose-500/15 text-rose-300',
            'sales'        => 'bg-indigo-500/15 text-indigo-300',
            'tech_team'    => 'bg-emerald-500/15 text-emerald-300',
            'content_team' => 'bg-amber-500/15 text-amber-300',
            default        => 'bg-gray-500/15 text-gray-300',
        };
        // Inline gradient CSS (Tailwind gradient classes aren't all in the compiled bundle).
        $roleGradient = fn($r) => match($r) {
            'admin'        => 'linear-gradient(135deg, #f43f5e, #ea580c)',
            'sales'        => 'linear-gradient(135deg, #6366f1, #7c3aed)',
            'tech_team'    => 'linear-gradient(135deg, #10b981, #0d9488)',
            'content_team' => 'linear-gradient(135deg, #f59e0b, #ea580c)',
            default        => 'linear-gradient(135deg, #64748b, #475569)',
        };
        [$sBg, $sLabel] = $statusStyle[$ticket->status];
        [$pBg, $pLabel] = $priorityStyle[$ticket->priority];

        $canActOnStatus = $isAdmin || $ticket->assignee_id === $user->id || $ticket->reporter_id === $user->id;
        $canBounce      = $ticket->assignee_id === $user->id && ! $isAdmin;

        // Shared style tokens — soft, no harsh white lines.
        $card  = 'background:#161d2b; border:1px solid rgba(148,163,184,0.08); border-radius:16px;';
        $field = 'width:100%; padding:9px 12px; background:#0f1623; border:1px solid rgba(148,163,184,0.10); border-radius:10px; color:#e2e8f0; font-size:13px; outline:none;';
        $chip  = 'font-size:9px; padding:2px 7px; border-radius:5px; text-transform:uppercase; letter-spacing:0.04em; font-weight:700;';

        $fmtSize = function ($bytes) {
            if (! $bytes) return '';
            $u = ['B','KB','MB','GB']; $i = 0; $b = (float) $bytes;
            while ($b >= 1024 && $i < 3) { $b /= 1024; $i++; }
            return round($b, $i ? 1 : 0) . ' ' . $u[$i];
        };
    @endphp

    <div class="p-6 w-full" style="max-width: 1100px; margin: 0 auto;">
        {{-- Back link --}}
        <a href="{{ route('support.index') }}" class="inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-300 mb-4 transition-colors">
            <svg style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            All tickets
        </a>

        {{-- ============ Header card ============ --}}
        <div style="{{ $card }} padding: 22px 24px; margin-bottom: 16px;">
            <div class="flex items-start justify-between gap-4 flex-wrap mb-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap mb-2">
                        <span style="font-family: ui-monospace, monospace; font-size: 12px; color: #818cf8; font-weight: 700;">{{ $ticket->code }}</span>
                        <span style="{{ $chip }}" class="{{ $pBg }}">{{ $pLabel }}</span>
                        <span style="font-size: 11px; padding: 3px 11px; border-radius: 999px; font-weight: 600;" class="{{ $sBg }}">{{ $sLabel }}</span>
                    </div>
                    <h1 class="text-xl font-bold text-gray-100">{{ $ticket->subject }}</h1>
                </div>
                <div class="text-right text-[11px] text-gray-500 flex-shrink-0 leading-relaxed">
                    <p>Created {{ $ticket->created_at->diffForHumans() }}</p>
                    <p>Active {{ $ticket->last_activity_at?->diffForHumans() }}</p>
                </div>
            </div>

            {{-- Reporter → Assignee --}}
            <div class="flex items-center gap-3 flex-wrap pt-4" style="border-top: 1px solid rgba(148,163,184,0.06);">
                <span class="text-[10px] uppercase tracking-wider font-semibold text-gray-600">From</span>
                @php $rep = $ticket->reporter; @endphp
                <div class="flex items-center gap-2">
                    <div style="width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 13px; background: {{ $roleGradient($rep?->role) }};">
                        {{ strtoupper(substr($rep?->name ?? '?', 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-200 leading-tight">{{ $rep?->name }}</p>
                        @if($rep?->role)
                            <span style="{{ $chip }}" class="{{ $roleChip($rep->role) }}">{{ $roleLabel($rep->role) }}</span>
                        @endif
                    </div>
                </div>

                <svg style="width: 18px; height: 18px; color: #475569; margin: 0 6px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>

                <span class="text-[10px] uppercase tracking-wider font-semibold text-gray-600">To</span>
                @if($ticket->assignee)
                    @php $asg = $ticket->assignee; @endphp
                    <div class="flex items-center gap-2">
                        <div style="width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 13px; background: {{ $roleGradient($asg->role) }};">
                            {{ strtoupper(substr($asg->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-200 leading-tight">{{ $asg->name }}</p>
                            @if($asg->role)
                                <span style="{{ $chip }}" class="{{ $roleChip($asg->role) }}">{{ $roleLabel($asg->role) }}</span>
                            @endif
                        </div>
                    </div>
                @else
                    <span style="padding: 6px 12px; font-size: 12px; background: rgba(245,158,11,0.1); color: #fbbf24; border-radius: 999px; font-weight: 600;">Admin pool (unassigned)</span>
                @endif
            </div>
        </div>

        {{-- ============ Two columns: thread + sidebar ============ --}}
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_290px] gap-4">

            {{-- LEFT: conversation thread --}}
            <div style="{{ $card }} padding: 22px;">
                @if(session('success'))
                    <div style="padding: 10px 14px; background: rgba(16,185,129,0.1); border-radius: 10px; color: #6ee7b7; font-size: 13px; margin-bottom: 18px;">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Original description --}}
                <div class="flex items-start gap-3 mb-5 pb-5" style="border-bottom: 1px solid rgba(148,163,184,0.06);">
                    <div style="width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px; flex-shrink: 0; background: {{ $roleGradient($rep?->role) }};">
                        {{ strtoupper(substr($rep?->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                            <span class="text-sm font-semibold text-gray-100">{{ $rep?->name }}</span>
                            @if($rep?->role)
                                <span style="{{ $chip }}" class="{{ $roleChip($rep->role) }}">{{ $roleLabel($rep->role) }}</span>
                            @endif
                            <span class="text-[11px] text-gray-500">opened · {{ $ticket->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-sm text-gray-200 whitespace-pre-wrap leading-relaxed">{{ $ticket->description }}</p>
                        @if($ticket->attachments->isNotEmpty())
                            <div class="mt-2 flex flex-col gap-2" style="max-width: 360px;">
                                @foreach($ticket->attachments as $att)
                                    @include('support._attachment-chip', ['url' => route('support.attachment', ['ticket' => $ticket, 'attachment' => $att->id]), 'att' => $att])
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Replies --}}
                @forelse($ticket->replies as $reply)
                    @if($reply->is_system)
                        <div class="flex items-center gap-3 my-4 text-[11px] text-gray-500">
                            <div class="flex-1 h-px" style="background: rgba(148,163,184,0.08);"></div>
                            <span style="padding: 3px 12px; background: #0f1623; border-radius: 999px;">{!! preg_replace('/\*\*(.+?)\*\*/', '<strong class="text-gray-400">$1</strong>', e($reply->body)) !!} · {{ $reply->created_at->diffForHumans() }}</span>
                            <div class="flex-1 h-px" style="background: rgba(148,163,184,0.08);"></div>
                        </div>
                    @else
                        @php $u = $reply->user; @endphp
                        <div class="flex items-start gap-3 mb-4">
                            <div style="width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px; flex-shrink: 0; background: {{ $roleGradient($u?->role) }};">
                                {{ strtoupper(substr($u?->name ?? '?', 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                    <span class="text-sm font-semibold text-gray-100">{{ $u?->name ?? 'Unknown' }}</span>
                                    @if($u?->role)
                                        <span style="{{ $chip }}" class="{{ $roleChip($u->role) }}">{{ $roleLabel($u->role) }}</span>
                                    @endif
                                    <span class="text-[11px] text-gray-500">{{ $reply->created_at->diffForHumans() }}</span>
                                </div>
                                @if($reply->body)
                                    <div style="background: #0f1623; border-radius: 12px; padding: 11px 15px;">
                                        <p class="text-sm text-gray-200 whitespace-pre-wrap leading-relaxed">{{ $reply->body }}</p>
                                    </div>
                                @endif
                                @if($reply->attachments->isNotEmpty())
                                    <div class="mt-2 flex flex-col gap-2" style="max-width: 360px;">
                                        @foreach($reply->attachments as $att)
                                            @include('support._attachment-chip', ['url' => route('support.attachment', ['ticket' => $ticket, 'attachment' => $att->id]), 'att' => $att])
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                @empty
                    <p class="text-xs text-gray-500 italic py-2">No replies yet. Be the first to respond.</p>
                @endforelse

                {{-- Composer --}}
                @if($ticket->canBeRepliedBy($user))
                    <form method="POST" action="{{ route('support.reply', $ticket) }}" enctype="multipart/form-data"
                          x-data="{ files: [] }" class="mt-6 pt-5" style="border-top: 1px solid rgba(148,163,184,0.06);">
                        @csrf
                        <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2">Add reply</label>
                        <textarea name="body" rows="3" maxlength="5000"
                                  placeholder="Type your response..."
                                  style="{{ $field }} resize: vertical; padding: 12px 14px;"
                                  onfocus="this.style.borderColor='rgba(99,102,241,0.5)';"
                                  onblur="this.style.borderColor='rgba(148,163,184,0.10)';"></textarea>
                        @error('body') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror

                        {{-- Selected files list --}}
                        <div x-show="files.length" x-cloak class="mt-2 flex flex-wrap gap-1.5">
                            <template x-for="(f, i) in files" :key="i">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] text-gray-200"
                                      style="background: #0f1623; border: 1px solid rgba(99,102,241,0.3);">
                                    <svg class="w-3 h-3" style="color:#818cf8;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span class="truncate" style="max-width: 160px;" x-text="f"></span>
                                </span>
                            </template>
                        </div>

                        <div class="flex items-center justify-between gap-3 mt-3 flex-wrap">
                            {{-- Attach (multiple) --}}
                            <label style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; background: #0f1623; border: 1px solid rgba(148,163,184,0.10); border-radius: 9px; cursor: pointer; font-size: 12px; color: #94a3b8;"
                                   class="hover:text-gray-200 hover:border-indigo-500/40 transition-colors"
                                   x-bind:style="files.length ? 'border-color: rgba(99,102,241,0.5); color: #a5b4fc;' : ''">
                                <input type="file" name="attachments[]" multiple class="hidden" @change="files = Array.from($event.target.files).map(f => f.name)"/>
                                <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                <span x-text="files.length ? (files.length + ' file' + (files.length>1?'s':'')) : 'Attach files'"></span>
                                <span x-show="files.length" @click.prevent="files=[]; $el.closest('label').querySelector('input[type=file]').value=''" class="text-gray-500 hover:text-rose-400 ml-1">✕</span>
                            </label>

                            <button type="submit"
                                    style="display: inline-flex; align-items: center; gap: 8px; padding: 9px 18px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border-radius: 10px; font-weight: 600; font-size: 13px; box-shadow: 0 4px 14px rgba(99,102,241,0.35);"
                                    class="hover:opacity-90 transition-opacity">
                                Send reply
                                <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12l14-7-3 9 3 9-14-7v-4z"/></svg>
                            </button>
                        </div>
                    </form>
                @else
                    <div style="margin-top: 20px; padding: 12px; background: rgba(100,116,139,0.08); border-radius: 10px; text-align: center;">
                        <p class="text-xs text-gray-400">This ticket is closed. Replies are disabled.</p>
                    </div>
                @endif
            </div>

            {{-- RIGHT: action sidebar --}}
            <div class="space-y-3">
                @if($canActOnStatus)
                    <div style="{{ $card }} padding: 16px;">
                        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2.5">Status</p>
                        <form method="POST" action="{{ route('support.status', $ticket) }}" x-data>
                            @csrf @method('PATCH')
                            <select name="status" @change="$el.form.submit()" style="{{ $field }}">
                                <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                                <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="waiting_user" {{ $ticket->status === 'waiting_user' ? 'selected' : '' }}>Waiting on user</option>
                                <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                            </select>
                        </form>
                    </div>
                @endif

                @if($isAdmin)
                    <div style="{{ $card }} padding: 16px;">
                        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2.5">Priority</p>
                        <form method="POST" action="{{ route('support.priority', $ticket) }}" x-data>
                            @csrf @method('PATCH')
                            <select name="priority" @change="$el.form.submit()" style="{{ $field }}">
                                <option value="low" {{ $ticket->priority === 'low' ? 'selected' : '' }}>Low</option>
                                <option value="normal" {{ $ticket->priority === 'normal' ? 'selected' : '' }}>Normal</option>
                                <option value="high" {{ $ticket->priority === 'high' ? 'selected' : '' }}>High</option>
                                <option value="urgent" {{ $ticket->priority === 'urgent' ? 'selected' : '' }}>Urgent</option>
                            </select>
                        </form>
                    </div>

                    <div style="{{ $card }} padding: 16px;">
                        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2.5">Assigned to</p>
                        <form method="POST" action="{{ route('support.assign', $ticket) }}" x-data>
                            @csrf @method('PATCH')
                            <select name="assignee_id" @change="$el.form.submit()" style="{{ $field }}">
                                <option value="">— Admin pool —</option>
                                @foreach($assignableUsers as $u)
                                    <option value="{{ $u->id }}" {{ $ticket->assignee_id === $u->id ? 'selected' : '' }}>{{ $u->name }} — {{ $roleLabel($u->role) }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                @endif

                @if($canBounce)
                    <div style="{{ $card }} padding: 16px;">
                        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2.5">Wrong person?</p>
                        <form method="POST" action="{{ route('support.bounce', $ticket) }}"
                              onsubmit="return confirm('Bounce this ticket back to the Admin pool? An admin will reassign it.');">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    style="width: 100%; padding: 9px 12px; background: rgba(245,158,11,0.12); color: #fbbf24; border-radius: 9px; font-size: 12px; font-weight: 600;"
                                    class="hover:bg-amber-500/20 transition-colors">
                                Bounce to Admin pool
                            </button>
                        </form>
                    </div>
                @endif

                <div style="{{ $card }} padding: 16px;">
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2.5">Details</p>
                    <dl class="space-y-2 text-xs">
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-500">Opened</dt>
                            <dd class="text-gray-300">{{ $ticket->created_at->format('M j, g:i A') }}</dd>
                        </div>
                        @if($ticket->resolved_at)
                            <div class="flex justify-between gap-2">
                                <dt class="text-gray-500">Resolved</dt>
                                <dd class="text-gray-300">{{ $ticket->resolved_at->format('M j') }}</dd>
                            </div>
                        @endif
                        @if($ticket->closed_at)
                            <div class="flex justify-between gap-2">
                                <dt class="text-gray-500">Closed</dt>
                                <dd class="text-gray-300">{{ $ticket->closed_at->format('M j') }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                @if($isAdmin)
                    <div style="{{ $card }} padding: 16px; border-color: rgba(244,63,94,0.15);">
                        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2.5">Danger zone</p>
                        <form method="POST" action="{{ route('support.destroy', $ticket) }}"
                              onsubmit="return confirm('Delete ticket {{ $ticket->code }} permanently? This removes all its replies.');">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    style="width: 100%; padding: 9px 12px; background: rgba(244,63,94,0.12); color: #fca5a5; border-radius: 9px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 6px;"
                                    class="hover:bg-rose-500/20 transition-colors">
                                <svg style="width: 13px; height: 13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a2 2 0 012-2h2a2 2 0 012 2v3"/></svg>
                                Delete ticket
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
