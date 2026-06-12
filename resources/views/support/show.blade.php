<x-app-layout>
    <x-slot name="header">Ticket {{ $ticket->code }}</x-slot>
    <x-slot name="title">{{ $ticket->code }} — {{ $ticket->subject }}</x-slot>

    @php
        $user = auth()->user();
        $statusStyle = [
            'open'         => ['bg-blue-500/15 text-blue-300 border-blue-500/30',     'Open'],
            'in_progress'  => ['bg-amber-500/15 text-amber-300 border-amber-500/30',  'In Progress'],
            'waiting_user' => ['bg-violet-500/15 text-violet-300 border-violet-500/30', 'Waiting on user'],
            'resolved'     => ['bg-emerald-500/15 text-emerald-300 border-emerald-500/30', 'Resolved'],
            'closed'       => ['bg-gray-500/15 text-gray-400 border-gray-500/30',     'Closed'],
        ];
        $priorityStyle = [
            'urgent' => ['bg-rose-500/15 text-rose-300 border-rose-500/30',     '● Urgent'],
            'high'   => ['bg-orange-500/15 text-orange-300 border-orange-500/30','● High'],
            'normal' => ['bg-slate-500/15 text-slate-300 border-slate-500/30',  '● Normal'],
            'low'    => ['bg-gray-500/15 text-gray-400 border-gray-500/30',     '● Low'],
        ];
        $roleLabel = fn($r) => match($r) {
            'admin' => 'Admin', 'sales' => 'Sales',
            'tech_team' => 'Tech Team', 'content_team' => 'Content Team',
            default => strtoupper(str_replace('_', ' ', (string) $r)),
        };
        $roleClass = fn($r) => match($r) {
            'admin'        => 'bg-rose-500/15 text-rose-300 border-rose-500/30',
            'sales'        => 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30',
            'tech_team'    => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
            'content_team' => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
            default        => 'bg-gray-500/15 text-gray-300 border-gray-500/30',
        };
        $roleGradient = fn($r) => match($r) {
            'admin' => 'from-rose-500 to-orange-600',
            'sales' => 'from-indigo-500 to-violet-600',
            'tech_team' => 'from-emerald-500 to-teal-600',
            'content_team' => 'from-amber-500 to-orange-600',
            default => 'from-slate-500 to-slate-600',
        };
        [$sBg, $sLabel] = $statusStyle[$ticket->status];
        [$pBg, $pLabel] = $priorityStyle[$ticket->priority];

        $canActOnStatus = $isAdmin || $ticket->assignee_id === $user->id || $ticket->reporter_id === $user->id;
        $canBounce      = $ticket->assignee_id === $user->id && ! $isAdmin;
    @endphp

    <div class="p-6 max-w-6xl mx-auto">
        {{-- Back link --}}
        <a href="{{ route('support.index') }}" class="inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-300 mb-4">
            <svg style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            All tickets
        </a>

        {{-- Header card --}}
        <div style="background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 20px 24px; margin-bottom: 16px;">
            <div class="flex items-start justify-between gap-4 flex-wrap mb-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap mb-2">
                        <span style="font-family: ui-monospace, monospace; font-size: 12px; color: #818cf8; font-weight: 700;">{{ $ticket->code }}</span>
                        <span style="font-size: 10px; padding: 2px 8px; border: 1px solid; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;" class="{{ $pBg }}">{{ $pLabel }}</span>
                        <span style="font-size: 11px; padding: 3px 10px; border: 1px solid; border-radius: 999px; font-weight: 600;" class="{{ $sBg }}">{{ $sLabel }}</span>
                        <span style="font-size: 10px; padding: 2px 8px; background: rgba(255,255,255,0.05); border-radius: 999px; color: #94a3b8;">{{ $ticket->categoryLabel() }}</span>
                    </div>
                    <h1 class="text-xl font-bold text-gray-100">{{ $ticket->subject }}</h1>
                </div>
                <div class="text-right text-xs text-gray-500 flex-shrink-0">
                    <p>Created {{ $ticket->created_at->diffForHumans() }}</p>
                    <p>Last activity {{ $ticket->last_activity_at?->diffForHumans() }}</p>
                </div>
            </div>

            {{-- Reporter → Assignee --}}
            <div class="flex items-center gap-3 flex-wrap pt-3 border-t border-ink-700/60">
                <span class="text-[10px] uppercase tracking-wider font-semibold text-gray-500">From</span>
                @php $rep = $ticket->reporter; @endphp
                <div class="flex items-center gap-2">
                    <div style="width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 12px;"
                         class="bg-gradient-to-br {{ $roleGradient($rep?->role) }}">
                        {{ strtoupper(substr($rep?->name ?? '?', 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-200 leading-tight">{{ $rep?->name }}</p>
                        @if($rep?->role)
                            <span style="font-size: 9px; padding: 1px 6px; border: 1px solid; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;" class="{{ $roleClass($rep->role) }}">{{ $roleLabel($rep->role) }}</span>
                        @endif
                    </div>
                </div>

                <svg style="width: 16px; height: 16px; color: #475569; margin: 0 8px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>

                <span class="text-[10px] uppercase tracking-wider font-semibold text-gray-500">To</span>
                @if($ticket->assignee)
                    @php $asg = $ticket->assignee; @endphp
                    <div class="flex items-center gap-2">
                        <div style="width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 12px;"
                             class="bg-gradient-to-br {{ $roleGradient($asg->role) }}">
                            {{ strtoupper(substr($asg->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-200 leading-tight">{{ $asg->name }}</p>
                            @if($asg->role)
                                <span style="font-size: 9px; padding: 1px 6px; border: 1px solid; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;" class="{{ $roleClass($asg->role) }}">{{ $roleLabel($asg->role) }}</span>
                            @endif
                        </div>
                    </div>
                @else
                    <span style="padding: 6px 12px; font-size: 12px; background: rgba(245,158,11,0.1); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); border-radius: 999px; font-weight: 600;">Admin pool (unassigned)</span>
                @endif
            </div>
        </div>

        {{-- Two-column grid: thread + actions --}}
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-4">

            {{-- LEFT: Thread --}}
            <div style="background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 20px;">
                @if(session('success'))
                    <div style="padding: 10px 14px; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); border-radius: 8px; color: #6ee7b7; font-size: 13px; margin-bottom: 16px;">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Original description as first message --}}
                <div class="flex items-start gap-3 mb-5 pb-5 border-b border-ink-700/60">
                    <div style="width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 13px; flex-shrink: 0;"
                         class="bg-gradient-to-br {{ $roleGradient($rep?->role) }}">
                        {{ strtoupper(substr($rep?->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-sm font-semibold text-gray-100">{{ $rep?->name }}</span>
                            @if($rep?->role)
                                <span style="font-size: 9px; padding: 1px 6px; border: 1px solid; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;" class="{{ $roleClass($rep->role) }}">{{ $roleLabel($rep->role) }}</span>
                            @endif
                            <span class="text-[11px] text-gray-500">opened ticket · {{ $ticket->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-sm text-gray-200 whitespace-pre-wrap leading-relaxed">{{ $ticket->description }}</p>
                    </div>
                </div>

                {{-- Replies --}}
                @forelse($ticket->replies as $reply)
                    @if($reply->is_system)
                        <div class="flex items-center gap-2 my-4 text-[11px] text-gray-500">
                            <div class="flex-1 h-px bg-ink-700"></div>
                            <span class="px-2 py-0.5 bg-ink-800 rounded-full border border-ink-700">{!! preg_replace('/\*\*(.+?)\*\*/', '<strong class="text-gray-400">$1</strong>', e($reply->body)) !!} · {{ $reply->created_at->diffForHumans() }}</span>
                            <div class="flex-1 h-px bg-ink-700"></div>
                        </div>
                    @else
                        @php $u = $reply->user; @endphp
                        <div class="flex items-start gap-3 mb-4">
                            <div style="width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 13px; flex-shrink: 0;"
                                 class="bg-gradient-to-br {{ $roleGradient($u?->role) }}">
                                {{ strtoupper(substr($u?->name ?? '?', 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                    <span class="text-sm font-semibold text-gray-100">{{ $u?->name ?? 'Unknown' }}</span>
                                    @if($u?->role)
                                        <span style="font-size: 9px; padding: 1px 6px; border: 1px solid; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;" class="{{ $roleClass($u->role) }}">{{ $roleLabel($u->role) }}</span>
                                    @endif
                                    <span class="text-[11px] text-gray-500">{{ $reply->created_at->diffForHumans() }}</span>
                                </div>
                                <div style="background: #0f172a; border: 1px solid #334155; border-radius: 10px; padding: 10px 14px;">
                                    <p class="text-sm text-gray-200 whitespace-pre-wrap leading-relaxed">{{ $reply->body }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                @empty
                    <p class="text-xs text-gray-500 italic">No replies yet. Be the first to respond.</p>
                @endforelse

                {{-- Reply composer --}}
                @if($ticket->canBeRepliedBy($user))
                    <form method="POST" action="{{ route('support.reply', $ticket) }}" class="mt-6 pt-5 border-t border-ink-700/60">
                        @csrf
                        <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Add reply</label>
                        <textarea name="body" rows="3" maxlength="5000" required
                                  placeholder="Type your response..."
                                  style="width: 100%; padding: 12px 14px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: #f1f5f9; font-size: 14px; resize: vertical;"
                                  class="focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500/50"></textarea>
                        @error('body') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                        <div class="flex items-center justify-end mt-3">
                            <button type="submit"
                                    style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border-radius: 10px; font-weight: 600; font-size: 13px; box-shadow: 0 4px 12px rgba(99,102,241,0.3);"
                                    class="hover:opacity-90 transition-opacity">
                                Send reply
                                <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12l14-7-3 9 3 9-14-7v-4z"/></svg>
                            </button>
                        </div>
                    </form>
                @else
                    <div style="margin-top: 20px; padding: 12px; background: rgba(100,116,139,0.1); border: 1px solid rgba(100,116,139,0.3); border-radius: 10px; text-align: center;">
                        <p class="text-xs text-gray-400">This ticket is closed. Replies are disabled.</p>
                    </div>
                @endif
            </div>

            {{-- RIGHT: Actions sidebar --}}
            <div class="space-y-3">
                {{-- Status --}}
                @if($canActOnStatus)
                    <div style="background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 14px;">
                        <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Status</p>
                        <form method="POST" action="{{ route('support.status', $ticket) }}" x-data="{ s: '{{ $ticket->status }}' }">
                            @csrf @method('PATCH')
                            <select name="status" x-model="s" @change="$el.form.submit()"
                                    style="width: 100%; padding: 8px 10px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #f1f5f9; font-size: 13px;"
                                    class="focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                                <option value="open">Open</option>
                                <option value="in_progress">In Progress</option>
                                <option value="waiting_user">Waiting on user</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                            </select>
                        </form>
                    </div>
                @endif

                {{-- Priority (admin only) --}}
                @if($isAdmin)
                    <div style="background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 14px;">
                        <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Priority</p>
                        <form method="POST" action="{{ route('support.priority', $ticket) }}" x-data="{ p: '{{ $ticket->priority }}' }">
                            @csrf @method('PATCH')
                            <select name="priority" x-model="p" @change="$el.form.submit()"
                                    style="width: 100%; padding: 8px 10px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #f1f5f9; font-size: 13px;"
                                    class="focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                                <option value="low">Low</option>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </form>
                    </div>
                @endif

                {{-- Assignee (admin only) --}}
                @if($isAdmin)
                    <div style="background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 14px;">
                        <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Assigned to</p>
                        <form method="POST" action="{{ route('support.assign', $ticket) }}" x-data="{ a: '{{ $ticket->assignee_id ?? '' }}' }">
                            @csrf @method('PATCH')
                            <select name="assignee_id" x-model="a" @change="$el.form.submit()"
                                    style="width: 100%; padding: 8px 10px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #f1f5f9; font-size: 13px;"
                                    class="focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                                <option value="">— Admin pool (unassigned) —</option>
                                @foreach($assignableUsers as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} — {{ $roleLabel($u->role) }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                @endif

                {{-- Bounce (assignee only, non-admin) --}}
                @if($canBounce)
                    <div style="background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 14px;">
                        <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Wrong person?</p>
                        <form method="POST" action="{{ route('support.bounce', $ticket) }}"
                              onsubmit="return confirm('Bounce this ticket back to the Admin pool? An admin will reassign it.');">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    style="width: 100%; padding: 8px 12px; background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); border-radius: 8px; font-size: 12px; font-weight: 600;"
                                    class="hover:bg-amber-500/25 transition-colors">
                                Bounce to Admin pool
                            </button>
                        </form>
                    </div>
                @endif

                {{-- Meta --}}
                <div style="background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 14px;">
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Details</p>
                    <dl class="space-y-1.5 text-xs">
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-500">Category</dt>
                            <dd class="text-gray-300 font-medium">{{ $ticket->categoryLabel() }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-500">Opened</dt>
                            <dd class="text-gray-300">{{ $ticket->created_at->format('M j, Y g:i A') }}</dd>
                        </div>
                        @if($ticket->resolved_at)
                            <div class="flex justify-between gap-2">
                                <dt class="text-gray-500">Resolved</dt>
                                <dd class="text-gray-300">{{ $ticket->resolved_at->format('M j, Y') }}</dd>
                            </div>
                        @endif
                        @if($ticket->closed_at)
                            <div class="flex justify-between gap-2">
                                <dt class="text-gray-500">Closed</dt>
                                <dd class="text-gray-300">{{ $ticket->closed_at->format('M j, Y') }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
