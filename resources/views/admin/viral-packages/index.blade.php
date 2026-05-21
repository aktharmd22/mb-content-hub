<x-app-layout>
    <x-slot name="header">Viral packages</x-slot>
    <x-slot name="title">Viral packages</x-slot>

    <div class="p-6">

        <div class="mb-6">
            <h2 class="text-lg font-medium text-gray-100">Viral packages</h2>
            <p class="text-sm text-gray-500 mt-0.5">Read-only overview of every package across the business.</p>
        </div>

        {{-- KPI strip --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-gradient-to-br from-indigo-500/10 to-indigo-500/5 border border-indigo-500/20 rounded-lg p-4">
                <p class="text-xs font-medium text-indigo-300 uppercase tracking-wide">Active</p>
                <p class="text-2xl font-medium text-gray-100 mt-1">{{ $stats['active'] }}</p>
            </div>
            <div class="bg-gradient-to-br from-emerald-500/10 to-emerald-500/5 border border-emerald-500/20 rounded-lg p-4">
                <p class="text-xs font-medium text-emerald-300 uppercase tracking-wide">Completed this month</p>
                <p class="text-2xl font-medium text-gray-100 mt-1">{{ $stats['completed_month'] }}</p>
            </div>
            <div class="bg-ink-850 border border-ink-700 rounded-lg p-4">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Total completed</p>
                <p class="text-2xl font-medium text-gray-100 mt-1">{{ $stats['total_completed'] }}</p>
            </div>
        </div>

        <form method="GET" class="flex flex-wrap items-center gap-2 mb-4">
            <div class="relative flex-1 min-w-[200px]">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Client name"
                       class="w-full pl-9 pr-3 py-1.5 text-sm bg-ink-900 border border-ink-700 rounded-lg text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"/>
            </div>
            <select name="status" class="px-3 py-1.5 text-sm bg-ink-900 border border-ink-700 rounded-lg text-gray-100">
                <option value="">All statuses</option>
                <option value="active"    @selected(request('status') === 'active')>Active</option>
                <option value="completed" @selected(request('status') === 'completed')>Completed</option>
            </select>
            <select name="sales_rep_id" class="px-3 py-1.5 text-sm bg-ink-900 border border-ink-700 rounded-lg text-gray-100">
                <option value="">All sales reps</option>
                @foreach($salesReps as $r)
                    <option value="{{ $r->id }}" @selected(request('sales_rep_id') == $r->id)>{{ $r->name }}</option>
                @endforeach
            </select>
            <select name="client_id" class="px-3 py-1.5 text-sm bg-ink-900 border border-ink-700 rounded-lg text-gray-100">
                <option value="">All clients</option>
                @foreach($clients as $c)
                    <option value="{{ $c->id }}" @selected(request('client_id') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-3 py-1.5 text-sm bg-ink-800 hover:bg-ink-700 text-gray-300 rounded-lg transition-colors">Filter</button>
            @if(request()->hasAny(['q', 'status', 'sales_rep_id', 'client_id']))
                <a href="{{ route('admin.viral-packages.index') }}" class="text-xs text-gray-500 hover:text-gray-300">Clear</a>
            @endif
        </form>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
            @if($packages->count() === 0)
                <div class="p-12 text-center">
                    <p class="text-sm text-gray-500">No packages match your filters.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[720px]">
                    <thead class="bg-ink-900/60 border-b border-ink-700">
                        <tr>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-400 uppercase tracking-wide">Client</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-400 uppercase tracking-wide">Sales rep</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-400 uppercase tracking-wide">Created</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-400 uppercase tracking-wide">Progress</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-400 uppercase tracking-wide">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-700">
                        @foreach($packages as $p)
                            <tr class="hover:bg-ink-800/30 transition-colors cursor-pointer"
                                onclick="window.location='{{ route('admin.viral-packages.show', $p) }}'">
                                <td class="px-4 py-3 text-sm text-gray-100">{{ $p->client?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-xs text-gray-300">{{ $p->salesRep?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-xs text-gray-400">{{ $p->created_at->diffForHumans() }}</td>
                                <td class="px-4 py-3">@include('partials.viral-package-progress', ['package' => $p])</td>
                                <td class="px-4 py-3">
                                    @if($p->isCompleted())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">Completed</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-500/15 text-indigo-300 border border-indigo-500/30">Active</span>
                                    @endif
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
