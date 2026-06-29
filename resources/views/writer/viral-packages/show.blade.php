<x-app-layout>
    <x-slot name="header">Viral package — {{ $package->client?->name }}</x-slot>
    <x-slot name="title">{{ $package->client?->name }} package</x-slot>

    @php
        $deliverables = $package->deliverables;
        $article = $deliverables->firstWhere('kind', 'article');
        $posts   = $deliverables->where('kind', 'social_post')->values();
        $reels   = $deliverables->where('kind', 'reel')->values();
        $landing = $deliverables->firstWhere('kind', 'landing_page');

        $approved = $package->approvedCount();
        $total    = $package->totalDeliverables();
        $percent  = $package->progressPercent();
    @endphp

    <div class="p-6 max-w-6xl">

        <a href="{{ route('writer.viral-packages.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-300 mb-4 transition-colors">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to packages
        </a>

        {{-- Header card --}}
        <div class="bg-ink-850 border border-ink-700 rounded-xl p-6 mb-6">
            <div class="flex items-start justify-between gap-6 flex-wrap">
                <div class="min-w-0 flex-1">
                    <h1 class="text-2xl font-semibold text-gray-100">{{ $package->client?->name }}</h1>
                    @if($package->client?->company)
                        <p class="text-sm text-gray-500 mt-1">{{ $package->client->company }}</p>
                    @endif
                    <p class="text-sm text-gray-500 mt-3">
                        Submitted by <span class="text-gray-300">{{ $package->salesRep?->name ?? 'sales' }}</span>
                        · {{ $package->created_at->diffForHumans() }}
                    </p>
                </div>

                <div class="w-full sm:w-auto sm:min-w-[280px]">
                    <div class="flex items-baseline justify-between mb-2">
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Progress</p>
                        <p class="text-2xl font-semibold text-gray-100">{{ $approved }}<span class="text-base text-gray-500 font-normal">/{{ $total }}</span></p>
                    </div>
                    <div class="h-2 bg-ink-900 rounded-full overflow-hidden">
                        <div class="h-full {{ $percent === 100 ? 'bg-emerald-500' : 'bg-indigo-500' }} transition-all duration-500"
                             style="width: {{ $percent }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reference assets --}}
        @if($package->assets->isNotEmpty())
            <div class="bg-ink-850 border border-ink-700 rounded-xl p-6 mb-6">
                <div class="flex items-start justify-between gap-3 mb-5 flex-wrap">
                    <div>
                        <h3 class="text-base font-semibold text-gray-100">Reference assets from sales</h3>
                        <p class="text-sm text-gray-500 mt-0.5">{{ $package->assets->count() }} {{ Str::plural('item', $package->assets->count()) }} to work with</p>
                    </div>
                    <a href="{{ route('writer.viral-packages.assets.download-all', $package) }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download all (zip)
                    </a>
                </div>

                <ul class="space-y-2">
                    @foreach($package->assets as $asset)
                        <li class="flex items-center gap-3 px-4 py-3 bg-ink-900/50 border border-ink-700 rounded-lg hover:bg-ink-900 transition-colors">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0
                                {{ $asset->type === 'link' ? 'bg-blue-500/10 text-blue-400' : ($asset->isImage() ? 'bg-emerald-500/10 text-emerald-400' : ($asset->isVideo() ? 'bg-violet-500/10 text-violet-400' : 'bg-gray-500/10 text-gray-400')) }}">
                                @if($asset->type === 'link')
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                @elseif($asset->isImage())
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                @elseif($asset->isVideo())
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-100 truncate">{{ $asset->name ?: $asset->original_filename ?: 'Untitled' }}</p>
                                <p class="text-xs text-gray-500 truncate">
                                    @if($asset->type === 'link') {{ $asset->url }}
                                    @else {{ $asset->original_filename }}@if($asset->file_size) · {{ number_format($asset->file_size / 1024 / 1024, 1) }} MB @endif
                                    @endif
                                </p>
                            </div>
                            <a href="{{ route('writer.viral-packages.assets.download', ['viralPackage' => $package, 'asset' => $asset]) }}"
                               @if($asset->type === 'link') target="_blank" rel="noopener" @endif
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-ink-800 hover:bg-ink-700 text-gray-300 text-xs font-medium rounded-lg transition-colors whitespace-nowrap">
                                {{ $asset->type === 'link' ? 'Open' : 'Download' }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Deliverables, grouped by kind (live-updates when sales approves / requests changes) --}}
        <div data-live="writer-viral-deliverables-{{ $package->id }}">
        @if($article)
            <section class="mb-8">
                <h3 class="text-sm font-semibold text-gray-300 uppercase tracking-wider mb-4">Article</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @include('writer.viral-packages._deliverable-card', ['package' => $package, 'd' => $article])
                </div>
            </section>
        @endif

        @if($posts->isNotEmpty())
            <section class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-300 uppercase tracking-wider">Social posts <span class="text-gray-500 font-normal normal-case">({{ $posts->count() }})</span></h3>
                    @unless($package->isCompleted())
                        <form method="POST" action="{{ route('writer.viral-packages.posts.add', $package) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-indigo-500/15 hover:bg-indigo-500/25 text-indigo-300 border border-indigo-500/30 rounded-lg transition-colors">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add post
                            </button>
                        </form>
                    @endunless
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($posts as $d)
                        @include('writer.viral-packages._deliverable-card', ['package' => $package, 'd' => $d])
                    @endforeach
                </div>
            </section>
        @endif

        @if($reels->isNotEmpty())
            <section class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-300 uppercase tracking-wider">Reels <span class="text-gray-500 font-normal normal-case">({{ $reels->count() }})</span></h3>
                    @unless($package->isCompleted())
                        <form method="POST" action="{{ route('writer.viral-packages.posts.add', $package) }}">
                            @csrf
                            <input type="hidden" name="kind" value="reel"/>
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-indigo-500/15 hover:bg-indigo-500/25 text-indigo-300 border border-indigo-500/30 rounded-lg transition-colors">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add reel
                            </button>
                        </form>
                    @endunless
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($reels as $d)
                        @include('writer.viral-packages._deliverable-card', ['package' => $package, 'd' => $d])
                    @endforeach
                </div>
            </section>
        @endif

        @if($landing)
            <section class="mb-8">
                <h3 class="text-sm font-semibold text-gray-300 uppercase tracking-wider mb-4">Landing page</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @include('writer.viral-packages._deliverable-card', ['package' => $package, 'd' => $landing])
                </div>
            </section>
        @endif
        </div>

        @if($package->assets->isEmpty())
            <div class="bg-ink-850 border border-ink-700 rounded-xl p-8 text-center">
                <p class="text-sm text-gray-400">No reference assets yet from sales.</p>
            </div>
        @endif
    </div>
</x-app-layout>
