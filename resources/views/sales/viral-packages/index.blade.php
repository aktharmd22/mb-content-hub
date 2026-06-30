<x-app-layout>
    <x-slot name="header">Viral package</x-slot>
    <x-slot name="title">Viral package</x-slot>

    <div class="p-6">

        <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
            <div>
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Viral package</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">One package per client. 1 Article · 5 Posts · 1 Reel.</p>
            </div>
            <a href="{{ route('sales.viral-packages.create') }}"
               class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add client
            </a>
        </div>

        <form method="GET" class="flex flex-wrap items-center gap-2 mb-4">
            <div class="relative flex-1 min-w-[200px]">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Search by client name"
                       class="w-full pl-9 pr-3 py-1.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"/>
            </div>
            <select name="status" class="px-3 py-1.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All statuses</option>
                <option value="active"    @selected(request('status') === 'active')>Active</option>
                <option value="completed" @selected(request('status') === 'completed')>Completed</option>
            </select>
            <button type="submit" class="px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">Filter</button>
            @if(request()->hasAny(['q', 'status']))
                <a href="{{ route('sales.viral-packages.index') }}" class="text-xs text-gray-500 hover:text-gray-300">Clear</a>
            @endif
        </form>

        <div data-live="sales-viral-list" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
            @if($packages->count() === 0)
                <div class="p-12 text-center">
                    <svg class="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">No clients added yet.</p>
                    <a href="{{ route('sales.viral-packages.create') }}" class="text-xs text-indigo-400 hover:underline">Add your first client</a>
                </div>
            @else
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($packages as $p)
                        <li class="group flex items-center justify-between gap-4 px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-950/50 transition-colors">
                            <a href="{{ route('sales.viral-packages.show', $p) }}" class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                    <p class="text-sm font-medium text-gray-100">{{ $p->client?->displayName() ?? '(client missing)' }}</p>
                                    @if($p->isCompleted())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">Completed</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-500/15 text-indigo-300 border border-indigo-500/30">Active</span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500">Created {{ $p->created_at->diffForHumans() }}@if($p->isCompleted()) · Delivered {{ $p->completed_at?->diffForHumans() }}@endif</p>
                            </a>
                            <div class="flex-shrink-0">
                                @include('partials.viral-package-progress', ['package' => $p])
                            </div>
                            <form method="POST" action="{{ route('sales.viral-packages.destroy', $p) }}"
                                  onsubmit="return confirm('Delete the viral package for {{ $p->client?->displayName() }}? The Drive folder will also be removed. This cannot be undone.');"
                                  class="flex-shrink-0">
                                @csrf @method('DELETE')
                                <button type="submit" title="Delete package"
                                        class="inline-flex items-center justify-center w-8 h-8 text-gray-400 hover:text-rose-400 hover:bg-rose-500/10 border border-ink-700 hover:border-rose-500/30 rounded-md transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
                <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800">
                    {{ $packages->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
