<x-app-layout>
    <x-slot name="header">Viral package — {{ $package->client?->name }}</x-slot>
    <x-slot name="title">{{ $package->client?->name }} package</x-slot>

    @php
        $deliverables = $package->deliverables;
        $article = $deliverables->firstWhere('kind', 'article');
        $posts   = $deliverables->where('kind', 'social_post')->values();
        $reel    = $deliverables->firstWhere('kind', 'reel');

        $approved = $package->approvedCount();
        $total    = $package->totalDeliverables();
        $percent  = $package->progressPercent();
    @endphp

    <div class="p-6 max-w-7xl">

        <a href="{{ route('writer.viral-packages.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-300 mb-4 transition-colors">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to packages
        </a>

        {{-- Hero header with progress --}}
        <div class="relative bg-gradient-to-br from-indigo-500/10 via-ink-850 to-violet-500/10 border border-ink-700 rounded-xl p-6 mb-6 overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none -translate-y-1/2 translate-x-1/4"></div>
            <div class="relative flex items-start justify-between gap-6 flex-wrap">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase tracking-wider bg-indigo-500/15 text-indigo-300 border border-indigo-500/30">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Viral package
                        </span>
                        <span class="text-xs text-gray-500">·</span>
                        <span class="text-xs text-gray-500">{{ $package->created_at->diffForHumans() }}</span>
                    </div>
                    <h1 class="text-2xl font-semibold text-gray-50 mb-1">{{ $package->client?->name }}</h1>
                    @if($package->client?->company)
                        <p class="text-sm text-gray-400">{{ $package->client->company }}</p>
                    @endif
                    <p class="text-xs text-gray-500 mt-2 flex items-center gap-1.5">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Submitted by <span class="text-gray-400">{{ $package->salesRep?->name ?? 'sales' }}</span>
                    </p>
                </div>

                {{-- Progress block --}}
                <div class="w-full sm:w-auto sm:min-w-[260px]">
                    <div class="flex items-center justify-between mb-1.5">
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Progress</p>
                        <p class="text-sm font-semibold text-gray-100">{{ $approved }}<span class="text-gray-500">/{{ $total }}</span></p>
                    </div>
                    <div class="h-2 bg-ink-900 border border-ink-700 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-indigo-500 to-violet-500 rounded-full transition-all duration-500"
                             style="width: {{ $percent }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1.5">
                        @if($percent === 100) ✓ All deliverables approved
                        @elseif($percent === 0) Not started
                        @else {{ $percent }}% complete
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- Reference assets banner (compact, prominent) --}}
        @if($package->assets->isNotEmpty())
            <div class="bg-ink-850 border border-ink-700 rounded-xl p-5 mb-6">
                <div class="flex items-start justify-between gap-3 mb-4 flex-wrap">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-indigo-500/15 text-indigo-300 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-100">Reference assets from sales</h3>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $package->assets->count() }} {{ Str::plural('item', $package->assets->count()) }} · grab everything in one go</p>
                        </div>
                    </div>
                    <a href="{{ route('writer.viral-packages.assets.download-all', $package) }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-medium rounded-lg shadow-sm shadow-indigo-500/20 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download all (zip)
                    </a>
                </div>

                <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach($package->assets as $asset)
                        <li class="flex items-center gap-2.5 px-3 py-2 bg-ink-900/50 border border-ink-700 rounded-lg hover:bg-ink-900 transition-colors group">
                            <div class="w-8 h-8 rounded-md flex items-center justify-center flex-shrink-0
                                {{ $asset->type === 'link' ? 'bg-blue-500/10 text-blue-400' : ($asset->isImage() ? 'bg-emerald-500/10 text-emerald-400' : ($asset->isVideo() ? 'bg-violet-500/10 text-violet-400' : 'bg-gray-500/10 text-gray-400')) }}">
                                @if($asset->type === 'link')
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                @elseif($asset->isImage())
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                @elseif($asset->isVideo())
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                @else
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-gray-200 truncate">{{ $asset->name ?: $asset->original_filename ?: 'Untitled' }}</p>
                                <p class="text-[10px] text-gray-500 truncate">
                                    @if($asset->type === 'link') {{ $asset->url }}
                                    @elseif($asset->file_size) {{ number_format($asset->file_size / 1024 / 1024, 1) }} MB
                                    @else File @endif
                                </p>
                            </div>
                            <a href="{{ route('writer.viral-packages.assets.download', ['viralPackage' => $package, 'asset' => $asset]) }}"
                               @if($asset->type === 'link') target="_blank" rel="noopener" @endif
                               class="opacity-60 group-hover:opacity-100 text-gray-400 hover:text-indigo-300 transition-all"
                               title="{{ $asset->type === 'link' ? 'Open link' : 'Download' }}">
                                @if($asset->type === 'link')
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Deliverables, grouped by kind --}}
        @if($article)
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-3">
                    <h3 class="text-xs font-semibold text-gray-300 uppercase tracking-wider">Article</h3>
                    <div class="flex-1 h-px bg-ink-700"></div>
                    <span class="text-xs text-gray-500">1 deliverable</span>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                    @include('writer.viral-packages._deliverable-card', ['package' => $package, 'd' => $article])
                </div>
            </div>
        @endif

        @if($posts->isNotEmpty())
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-3">
                    <h3 class="text-xs font-semibold text-gray-300 uppercase tracking-wider">Social posts</h3>
                    <div class="flex-1 h-px bg-ink-700"></div>
                    <span class="text-xs text-gray-500">{{ $posts->count() }} deliverables</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($posts as $d)
                        @include('writer.viral-packages._deliverable-card', ['package' => $package, 'd' => $d])
                    @endforeach
                </div>
            </div>
        @endif

        @if($reel)
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-3">
                    <h3 class="text-xs font-semibold text-gray-300 uppercase tracking-wider">Reel</h3>
                    <div class="flex-1 h-px bg-ink-700"></div>
                    <span class="text-xs text-gray-500">1 deliverable</span>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                    @include('writer.viral-packages._deliverable-card', ['package' => $package, 'd' => $reel])
                </div>
            </div>
        @endif

        @if($package->assets->isEmpty())
            <div class="bg-ink-850 border border-ink-700 rounded-xl p-8 text-center">
                <svg class="w-10 h-10 text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <p class="text-sm text-gray-400">No reference assets yet.</p>
                <p class="text-xs text-gray-600 mt-1">Sales hasn't attached any source material.</p>
            </div>
        @endif
    </div>
</x-app-layout>
