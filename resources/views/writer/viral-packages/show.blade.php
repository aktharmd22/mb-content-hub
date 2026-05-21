<x-app-layout>
    <x-slot name="header">Viral package — {{ $package->client?->name }}</x-slot>
    <x-slot name="title">{{ $package->client?->name }} package</x-slot>

    <div class="p-6 max-w-6xl">

        <a href="{{ route('writer.viral-packages.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-300 mb-3 transition-colors">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to packages
        </a>

        {{-- Header card --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-6 mb-6">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="min-w-0">
                    <h1 class="text-xl font-medium text-gray-100">{{ $package->client?->name }}</h1>
                    @if($package->client?->company)
                        <p class="text-sm text-gray-500">{{ $package->client->company }}</p>
                    @endif
                    <p class="text-xs text-gray-500 mt-1">
                        Submitted by {{ $package->salesRep?->name ?? 'sales' }} · {{ $package->created_at->diffForHumans() }}
                    </p>
                </div>
                @include('partials.viral-package-progress', ['package' => $package])
            </div>
        </div>

        {{-- Deliverables --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5 mb-6">
            <h3 class="text-sm font-medium text-gray-100 mb-4">Your 7 deliverables</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($package->deliverables as $d)
                    @include('writer.viral-packages._deliverable-card', ['package' => $package, 'd' => $d])
                @endforeach
            </div>
        </div>

        {{-- Reference assets (download only) --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5">
            <div class="mb-4">
                <h3 class="text-sm font-medium text-gray-100">Reference assets from sales</h3>
                <p class="text-xs text-gray-500 mt-0.5">{{ $package->assets->count() }} {{ Str::plural('item', $package->assets->count()) }} to work with</p>
            </div>

            @if($package->assets->isEmpty())
                <p class="text-xs text-gray-500">No reference assets yet.</p>
            @else
                <ul class="divide-y divide-gray-100 dark:divide-gray-800 -mx-5">
                    @foreach($package->assets as $asset)
                        <li class="flex items-center gap-3 px-5 py-2.5">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0
                                {{ $asset->type === 'link' ? 'bg-blue-500/10 text-blue-400' : ($asset->isImage() ? 'bg-emerald-500/10 text-emerald-400' : ($asset->isVideo() ? 'bg-violet-500/10 text-violet-400' : 'bg-gray-500/10 text-gray-400')) }}">
                                @if($asset->type === 'link')
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-100 truncate">{{ $asset->name ?: $asset->original_filename ?: 'Untitled' }}</p>
                                <p class="text-xs text-gray-500 truncate">
                                    @if($asset->type === 'link') {{ $asset->url }} @else {{ $asset->original_filename }} @if($asset->file_size) · {{ number_format($asset->file_size / 1024 / 1024, 1) }} MB @endif @endif
                                </p>
                            </div>
                            <a href="{{ route('writer.viral-packages.assets.download', ['viralPackage' => $package, 'asset' => $asset]) }}"
                               @if($asset->type === 'link') target="_blank" rel="noopener" @endif
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-ink-800 hover:bg-ink-700 text-gray-300 text-xs font-medium rounded-lg transition-colors">
                                {{ $asset->type === 'link' ? 'Open' : 'Download' }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-app-layout>
