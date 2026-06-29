<x-app-layout>
    <x-slot name="header">Viral package</x-slot>
    <x-slot name="title">Viral package</x-slot>

    <div class="p-6">

        <div class="mb-6">
            <h2 class="text-lg font-medium text-gray-100">Viral package</h2>
            <p class="text-sm text-gray-500 mt-0.5">Read-only overview of every package across the business.</p>
        </div>

        {{-- Compact KPI strip --}}
        <div class="grid grid-cols-3 gap-3 mb-6">
            <div class="bg-ink-850 border border-ink-700 rounded-lg px-4 py-3">
                <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">Active</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <p class="text-xl font-semibold text-indigo-300">{{ $stats['active'] }}</p>
                    <p class="text-xs text-gray-500">in progress</p>
                </div>
            </div>
            <div class="bg-ink-850 border border-ink-700 rounded-lg px-4 py-3">
                <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">Completed this month</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <p class="text-xl font-semibold text-emerald-300">{{ $stats['completed_month'] }}</p>
                    <p class="text-xs text-gray-500">delivered</p>
                </div>
            </div>
            <div class="bg-ink-850 border border-ink-700 rounded-lg px-4 py-3">
                <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">Total completed</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <p class="text-xl font-semibold text-gray-100">{{ $stats['total_completed'] }}</p>
                    <p class="text-xs text-gray-500">all-time</p>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" class="bg-ink-850 border border-ink-700 rounded-lg p-3 mb-4 grid grid-cols-2 md:grid-cols-4 gap-2">
            <div class="col-span-2 md:col-span-2 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by client name"
                       class="w-full pl-9 pr-3 py-1.5 text-sm bg-ink-800 border border-ink-700 rounded-md text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"/>
            </div>
            <select name="status" class="px-3 py-1.5 text-sm bg-ink-800 border border-ink-700 rounded-md text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All statuses</option>
                <option value="active"    @selected(request('status') === 'active')>Active</option>
                <option value="completed" @selected(request('status') === 'completed')>Completed</option>
            </select>
            <select name="sales_rep_id" class="px-3 py-1.5 text-sm bg-ink-800 border border-ink-700 rounded-md text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All sales reps</option>
                @foreach($salesReps as $r)
                    <option value="{{ $r->id }}" @selected(request('sales_rep_id') == $r->id)>{{ $r->name }}</option>
                @endforeach
            </select>
            <select name="tech_team_id" class="px-3 py-1.5 text-sm bg-ink-800 border border-ink-700 rounded-md text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All tech team</option>
                @foreach($techTeam as $t)
                    <option value="{{ $t->id }}" @selected(request('tech_team_id') == $t->id)>{{ $t->name }}</option>
                @endforeach
            </select>
            <select name="client_id" class="px-3 py-1.5 text-sm bg-ink-800 border border-ink-700 rounded-md text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All clients</option>
                @foreach($clients as $c)
                    <option value="{{ $c->id }}" @selected(request('client_id') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
            <div class="col-span-2 md:col-span-4 flex items-center gap-2">
                <button type="submit" class="px-3 py-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md transition-colors">Filter</button>
                @if(request()->hasAny(['q', 'status', 'sales_rep_id', 'tech_team_id', 'client_id']))
                    <a href="{{ route('admin.viral-packages.index') }}" class="text-xs text-gray-500 hover:text-gray-300">Clear</a>
                @endif
            </div>
        </form>

        {{-- Packages list --}}
        <div data-live="admin-viral-list" class="bg-ink-850 border border-ink-700 rounded-lg overflow-hidden">
            @if($packages->count() === 0)
                <div class="px-6 py-16 text-center">
                    <svg class="w-10 h-10 text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <p class="text-sm text-gray-400">No packages match your filters.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[720px]">
                    <thead class="bg-ink-900/60 border-b border-ink-700">
                        <tr>
                            <th class="text-left px-4 py-2.5 font-medium text-[11px] text-gray-400 uppercase tracking-wide">Client</th>
                            <th class="text-left px-4 py-2.5 font-medium text-[11px] text-gray-400 uppercase tracking-wide">Sales rep</th>
                            <th class="text-left px-4 py-2.5 font-medium text-[11px] text-gray-400 uppercase tracking-wide">Tech team</th>
                            <th class="text-left px-4 py-2.5 font-medium text-[11px] text-gray-400 uppercase tracking-wide">Created</th>
                            <th class="text-left px-4 py-2.5 font-medium text-[11px] text-gray-400 uppercase tracking-wide">Progress</th>
                            <th class="text-left px-4 py-2.5 font-medium text-[11px] text-gray-400 uppercase tracking-wide">Status</th>
                            <th class="text-right px-4 py-2.5 font-medium text-[11px] text-gray-400 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-700">
                        @foreach($packages as $p)
                            <tr class="hover:bg-ink-800/40 transition-colors cursor-pointer"
                                onclick="window.location='{{ route('admin.viral-packages.show', $p) }}'">
                                <td class="px-4 py-3 text-sm text-gray-100 font-medium">{{ $p->client?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-xs text-gray-300">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-5 h-5 rounded-full bg-indigo-500/15 text-indigo-300 flex items-center justify-center text-[9px] font-semibold flex-shrink-0">
                                            {{ $p->salesRep ? strtoupper(substr($p->salesRep->name, 0, 1)) : '—' }}
                                        </span>
                                        <span class="truncate">{{ $p->salesRep?->name ?? '—' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-300">
                                    @if($p->techTeam)
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-5 h-5 rounded-full bg-violet-500/15 text-violet-300 flex items-center justify-center text-[9px] font-semibold flex-shrink-0">
                                                {{ strtoupper(substr($p->techTeam->name, 0, 1)) }}
                                            </span>
                                            <span class="truncate">{{ $p->techTeam->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-gray-500 italic">Unassigned</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-400">{{ $p->created_at->diffForHumans() }}</td>
                                <td class="px-4 py-3">@include('partials.viral-package-progress', ['package' => $p])</td>
                                <td class="px-4 py-3">
                                    @if($p->isCompleted())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">Delivered</span>
                                        @if($p->completed_at)
                                            <p class="text-[10px] text-gray-500 mt-1">{{ $p->completed_at->format('M j, Y') }}</p>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-500/15 text-indigo-300 border border-indigo-500/30">Active</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right" onclick="event.stopPropagation();">
                                    <form method="POST" action="{{ route('admin.viral-packages.destroy', $p) }}"
                                          onsubmit="return confirm('Delete the viral package for {{ $p->client?->name }}? The Drive folder will also be removed. This cannot be undone.');"
                                          class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Delete package"
                                                class="inline-flex items-center justify-center w-7 h-7 text-gray-500 hover:text-rose-400 hover:bg-rose-500/10 rounded-md transition-colors">
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
                <div class="px-4 py-3 border-t border-ink-700">
                    {{ $packages->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
