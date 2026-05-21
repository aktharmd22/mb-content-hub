<x-app-layout>
    <x-slot name="header">Viral package</x-slot>
    <x-slot name="title">Viral package</x-slot>

    <div class="p-6">

        <div class="mb-6">
            <h2 class="text-lg font-medium text-gray-100">Viral package</h2>
            <p class="text-sm text-gray-500 mt-0.5">Active packages with deliverables to work on.</p>
        </div>

        <form method="GET" class="flex flex-wrap items-center gap-2 mb-4">
            <div class="relative flex-1 min-w-[200px]">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Search by client name"
                       class="w-full pl-9 pr-3 py-1.5 text-sm bg-ink-900 border border-ink-700 rounded-lg text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"/>
            </div>
            <button type="submit" class="px-3 py-1.5 text-sm bg-ink-800 hover:bg-ink-700 text-gray-300 rounded-lg transition-colors">Search</button>
        </form>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
            @if($packages->count() === 0)
                <div class="p-12 text-center">
                    <p class="text-sm text-gray-500">Nothing to work on right now.</p>
                </div>
            @else
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($packages as $p)
                        @php
                            $pending = $p->deliverables->where('stage', 'pending')->count();
                            $inProgress = $p->deliverables->where('stage', 'in_progress')->count();
                        @endphp
                        <li>
                            <a href="{{ route('writer.viral-packages.show', $p) }}" class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-950/50 transition-colors">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-100">{{ $p->client?->name ?? '(client missing)' }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        Submitted by {{ $p->salesRep?->name ?? 'sales' }} · {{ $p->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    @if($pending > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-500/15 text-gray-300 border border-gray-500/30">{{ $pending }} pending</span>
                                    @endif
                                    @if($inProgress > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-500/15 text-indigo-300 border border-indigo-500/30">{{ $inProgress }} in progress</span>
                                    @endif
                                    @include('partials.viral-package-progress', ['package' => $p])
                                </div>
                            </a>
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
