<x-app-layout>
    <x-slot name="header">Viral package — {{ $package->client?->name }}</x-slot>
    <x-slot name="title">{{ $package->client?->name }} package</x-slot>

    <div class="p-6 max-w-6xl">

        <a href="{{ route('admin.viral-packages.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-300 mb-3 transition-colors">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to packages
        </a>

        {{-- Header --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-6 mb-6">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        @if($package->isCompleted())
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">Completed</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-500/15 text-indigo-300 border border-indigo-500/30">Active</span>
                        @endif
                    </div>
                    <h1 class="text-xl font-medium text-gray-100">{{ $package->client?->name }}</h1>
                    @if($package->client?->company)
                        <p class="text-sm text-gray-500">{{ $package->client->company }}</p>
                    @endif
                </div>
                @include('partials.viral-package-progress', ['package' => $package])
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 mt-4 border-t border-gray-100 dark:border-gray-800">
                <div>
                    <p class="text-xs text-gray-500">Sales rep</p>
                    <p class="text-sm text-gray-100 mt-0.5">{{ $package->salesRep?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Created</p>
                    <p class="text-sm text-gray-100 mt-0.5">{{ $package->created_at->format('M j, Y') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Delivered</p>
                    <p class="text-sm text-gray-100 mt-0.5">{{ $package->completed_at?->format('M j, Y') ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Assets</p>
                    <p class="text-sm text-gray-100 mt-0.5">{{ $package->assets->count() }} items</p>
                </div>
            </div>
        </div>

        {{-- Deliverable summary --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5 mb-6">
            <h3 class="text-sm font-medium text-gray-100 mb-4">Deliverables — read-only</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @php
                    $stageColors = [
                        'pending'     => ['bg' => 'bg-gray-500/10',    'text' => 'text-gray-400',    'border' => 'border-gray-500/30'],
                        'in_progress' => ['bg' => 'bg-indigo-500/10',  'text' => 'text-indigo-300',  'border' => 'border-indigo-500/30'],
                        'review'      => ['bg' => 'bg-amber-500/10',   'text' => 'text-amber-300',   'border' => 'border-amber-500/30'],
                        'approved'    => ['bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-300', 'border' => 'border-emerald-500/30'],
                    ];
                @endphp
                @foreach($package->deliverables as $d)
                    @php $c = $stageColors[$d->stage] ?? $stageColors['pending']; @endphp
                    <div class="border border-ink-700 rounded-lg p-4 bg-ink-800/30">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-100 truncate">{{ $d->title }}</p>
                                <p class="text-xs text-gray-500">{{ $d->kindLabel() }}</p>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium {{ $c['bg'] }} {{ $c['text'] }} border {{ $c['border'] }}">
                                {{ $d->stageLabel() }}
                            </span>
                        </div>
                        @if($d->assignee)
                            <p class="text-xs text-gray-500">Assigned: <span class="text-gray-300">{{ $d->assignee->name }}</span></p>
                        @endif
                        @if($d->approved_at)
                            <p class="text-xs text-emerald-400 mt-1">Approved {{ $d->approved_at->diffForHumans() }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Full activity timeline --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5">
            <h3 class="text-sm font-medium text-gray-100 mb-4">Activity timeline</h3>
            @php
                $events = $package->deliverables->flatMap(fn ($d) =>
                    $d->history->map(fn ($h) => ['deliverable' => $d, 'history' => $h])
                )->sortByDesc(fn ($e) => $e['history']->changed_at);
            @endphp
            @if($events->isEmpty())
                <p class="text-xs text-gray-500">No activity yet.</p>
            @else
                <ol class="space-y-3">
                    @foreach($events as $e)
                        <li class="flex gap-3">
                            <div class="w-2 h-2 rounded-full bg-indigo-500 mt-1.5 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-100">
                                    <span class="font-medium">{{ $e['deliverable']->title }}</span>
                                    <span class="text-gray-500">→</span>
                                    <span class="text-gray-300">{{ ucfirst(str_replace('_', ' ', $e['history']->to_stage)) }}</span>
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ $e['history']->changedBy?->name ?? 'system' }} · {{ $e['history']->changed_at->diffForHumans() }}
                                </p>
                                @if($e['history']->notes)
                                    <p class="text-xs text-gray-400 mt-1 italic">"{{ $e['history']->notes }}"</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </div>
</x-app-layout>
