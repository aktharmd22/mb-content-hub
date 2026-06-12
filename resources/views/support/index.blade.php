<x-app-layout>
    <x-slot name="header">Support</x-slot>
    <x-slot name="title">Support Tickets</x-slot>

    @php
        $statusStyle = [
            'open'         => ['bg-blue-500/15 text-blue-300 border-blue-500/30',     'Open'],
            'in_progress'  => ['bg-amber-500/15 text-amber-300 border-amber-500/30',  'In Progress'],
            'waiting_user' => ['bg-violet-500/15 text-violet-300 border-violet-500/30', 'Waiting'],
            'resolved'     => ['bg-emerald-500/15 text-emerald-300 border-emerald-500/30', 'Resolved'],
            'closed'       => ['bg-gray-500/15 text-gray-400 border-gray-500/30',     'Closed'],
        ];
        $priorityStyle = [
            'urgent' => ['bg-rose-500/15 text-rose-300 border-rose-500/30',     '● Urgent'],
            'high'   => ['bg-orange-500/15 text-orange-300 border-orange-500/30','● High'],
            'normal' => ['bg-slate-500/15 text-slate-300 border-slate-500/30',  '● Normal'],
            'low'    => ['bg-gray-500/15 text-gray-400 border-gray-500/30',     '● Low'],
        ];
        $roleStyle = [
            'admin'        => 'bg-rose-500/15 text-rose-300 border-rose-500/30',
            'sales'        => 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30',
            'tech_team'    => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
            'content_team' => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
        ];
        $roleLabel = fn($r) => match($r) {
            'admin' => 'Admin', 'sales' => 'Sales',
            'tech_team' => 'Tech', 'content_team' => 'Content',
            default => strtoupper(str_replace('_', ' ', (string) $r)),
        };
    @endphp

    <div class="p-6 max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="flex items-start justify-between gap-4 mb-6 flex-wrap">
            <div>
                <h1 class="text-2xl font-bold text-gray-100">Support Tickets</h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $isAdmin ? 'All tickets across the platform' : 'Tickets you raised or are assigned to you' }}
                </p>
            </div>
            <a href="{{ route('support.create') }}"
               style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border-radius: 10px; font-weight: 600; font-size: 14px; box-shadow: 0 4px 12px rgba(99,102,241,0.3);"
               class="hover:opacity-90 transition-opacity">
                <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                New Ticket
            </a>
        </div>

        {{-- Filter tabs --}}
        <div class="flex flex-wrap items-center gap-2 mb-4">
            @php
                $tabs = $isAdmin
                    ? [
                        ['all', 'All', $counts['all']],
                        ['unassigned', 'Unassigned', $counts['unassigned']],
                        ['open', 'Open', $counts['open']],
                        ['in_progress', 'In Progress', $counts['in_progress']],
                        ['resolved', 'Resolved', $counts['resolved']],
                        ['closed', 'Closed', $counts['closed']],
                      ]
                    : [
                        ['mine', 'All Mine', $counts['all']],
                        ['raised_by_me', 'Raised by me', $counts['raised_by_me']],
                        ['assigned_to_me', 'Assigned to me', $counts['assigned_to_me']],
                        ['open', 'Open', $counts['open']],
                        ['resolved', 'Resolved', $counts['resolved']],
                        ['closed', 'Closed', $counts['closed']],
                      ];
            @endphp
            @foreach($tabs as [$key, $label, $count])
                <a href="{{ route('support.index', ['filter' => $key]) }}"
                   style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; font-size: 13px; font-weight: 500; transition: all 0.15s;"
                   class="{{ $filter === $key
                       ? 'bg-indigo-500/20 text-indigo-300 ring-1 ring-indigo-500/40'
                       : 'bg-ink-800 text-gray-400 hover:text-gray-200 hover:bg-ink-700' }}">
                    {{ $label }}
                    <span style="font-size: 11px; padding: 1px 6px; background: rgba(255,255,255,0.08); border-radius: 999px;">{{ $count }}</span>
                </a>
            @endforeach
        </div>

        {{-- Search --}}
        <form method="GET" class="mb-4">
            <input type="hidden" name="filter" value="{{ $filter }}"/>
            <div class="relative max-w-md">
                <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: #6b7280;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="q" value="{{ $q }}" placeholder="Search by code or subject..."
                       style="width: 100%; padding: 8px 12px 8px 36px; background: #1e293b; border: 1px solid #334155; border-radius: 10px; color: #f1f5f9; font-size: 13px;"
                       class="focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500/50"/>
            </div>
        </form>

        {{-- Ticket list --}}
        @if($tickets->isEmpty())
            <div style="padding: 80px 24px; text-align: center; background: #1e293b; border: 1px dashed #334155; border-radius: 16px;">
                <div style="width: 60px; height: 60px; margin: 0 auto 16px; border-radius: 50%; background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(168,85,247,0.15)); display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 28px; height: 28px; color: #818cf8;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-gray-200">No tickets here</p>
                <p class="text-xs text-gray-500 mt-1">When tickets match this filter, they'll show up here.</p>
                <a href="{{ route('support.create') }}" class="inline-block mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-lg transition-colors">Raise a ticket</a>
            </div>
        @else
            <div class="space-y-2">
                @foreach($tickets as $t)
                    @php
                        [$sBg, $sLabel] = $statusStyle[$t->status] ?? ['bg-gray-500/15 text-gray-400 border-gray-500/30', ucfirst($t->status)];
                        [$pBg, $pLabel] = $priorityStyle[$t->priority] ?? ['bg-gray-500/15 text-gray-400 border-gray-500/30', ucfirst($t->priority)];
                    @endphp
                    <a href="{{ route('support.show', $t) }}"
                       style="display: block; padding: 16px 20px; background: #1e293b; border: 1px solid #334155; border-radius: 12px; transition: all 0.15s;"
                       class="hover:bg-ink-800 hover:border-indigo-500/40">
                        <div class="flex items-start justify-between gap-4 flex-wrap">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap mb-1.5">
                                    <span style="font-family: ui-monospace, monospace; font-size: 11px; color: #818cf8; font-weight: 600;">{{ $t->code }}</span>
                                    <span style="font-size: 9px; padding: 2px 6px; border: 1px solid; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;" class="{{ $pBg }}">{{ $pLabel }}</span>
                                    <span style="font-size: 10px; padding: 2px 8px; border: 1px solid; border-radius: 999px; font-weight: 600;" class="{{ $sBg }}">{{ $sLabel }}</span>
                                    <span style="font-size: 10px; padding: 2px 8px; background: rgba(255,255,255,0.05); border-radius: 999px; color: #94a3b8;">{{ $t->categoryLabel() }}</span>
                                </div>
                                <h3 class="text-sm font-semibold text-gray-100 mb-1 truncate">{{ $t->subject }}</h3>
                                <p class="text-xs text-gray-500 line-clamp-1">{{ Str::limit($t->description, 120) }}</p>
                            </div>
                            <div class="flex items-center gap-3 flex-shrink-0">
                                {{-- Reporter → Assignee --}}
                                <div class="flex items-center gap-2 text-xs">
                                    @php $rep = $t->reporter; @endphp
                                    <div title="Reporter: {{ $rep?->name }}" style="display: flex; align-items: center; gap: 6px;">
                                        <div style="width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 11px;"
                                             class="bg-gradient-to-br
                                             @if($rep?->role === 'admin') from-rose-500 to-orange-600
                                             @elseif($rep?->role === 'sales') from-indigo-500 to-violet-600
                                             @elseif($rep?->role === 'tech_team') from-emerald-500 to-teal-600
                                             @else from-slate-500 to-slate-600 @endif">
                                            {{ strtoupper(substr($rep?->name ?? '?', 0, 1)) }}
                                        </div>
                                        <span class="text-gray-400 hidden md:inline">{{ $rep?->name }}</span>
                                    </div>
                                    <svg style="width: 12px; height: 12px; color: #475569;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    @if($t->assignee)
                                        @php $asg = $t->assignee; @endphp
                                        <div title="Assigned: {{ $asg->name }}" style="display: flex; align-items: center; gap: 6px;">
                                            <div style="width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 11px;"
                                                 class="bg-gradient-to-br
                                                 @if($asg->role === 'admin') from-rose-500 to-orange-600
                                                 @elseif($asg->role === 'sales') from-indigo-500 to-violet-600
                                                 @elseif($asg->role === 'tech_team') from-emerald-500 to-teal-600
                                                 @else from-slate-500 to-slate-600 @endif">
                                                {{ strtoupper(substr($asg->name, 0, 1)) }}
                                            </div>
                                            <span class="text-gray-400 hidden md:inline">{{ $asg->name }}</span>
                                        </div>
                                    @else
                                        <span style="padding: 4px 10px; font-size: 11px; background: rgba(245,158,11,0.1); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); border-radius: 999px; font-weight: 600;">Admin pool</span>
                                    @endif
                                </div>
                                <span class="text-[10px] text-gray-500 whitespace-nowrap hidden sm:inline">{{ $t->last_activity_at?->diffForHumans(short: true) }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-6">{{ $tickets->links() }}</div>
        @endif
    </div>
</x-app-layout>
