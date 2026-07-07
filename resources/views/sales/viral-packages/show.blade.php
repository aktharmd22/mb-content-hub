<x-app-layout>
    <x-slot name="header">Viral package — {{ $package->client?->displayName() }}</x-slot>
    <x-slot name="title">{{ $package->client?->displayName() }} package</x-slot>

    <div class="p-6 max-w-6xl">

        <a href="{{ route('sales.viral-packages.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-300 mb-3 transition-colors">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to packages
        </a>

        {{-- Header card --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-6 mb-6">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        @if($package->isCompleted())
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">✓ Completed</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-500/15 text-indigo-300 border border-indigo-500/30">Active</span>
                        @endif
                        <span class="text-xs text-gray-500">Created {{ $package->created_at->diffForHumans() }}</span>
                    </div>
                    <h1 class="text-xl font-medium text-gray-100">{{ $package->client?->displayName() }}</h1>
                    @if($package->client?->secondaryName())
                        <p class="text-sm text-gray-500">{{ $package->client->secondaryName() }}</p>
                    @endif
                </div>
                <div class="flex-shrink-0">
                    @include('partials.viral-package-progress', ['package' => $package])
                </div>
            </div>

            {{-- Tech team assignment row --}}
            @if(! $package->isCompleted())
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3 flex-wrap"
                     x-data="{ reassignOpen: false }">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 bg-indigo-500/15 text-indigo-300 rounded-full flex items-center justify-center text-xs font-semibold flex-shrink-0">
                            {{ strtoupper(substr($package->techTeam?->name ?? '?', 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Assigned tech team</p>
                            <p class="text-sm font-medium text-gray-100">{{ $package->techTeam?->name ?? 'Unassigned' }}</p>
                        </div>
                    </div>
                    <button type="button" @click="reassignOpen = !reassignOpen"
                            class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors">Reassign</button>

                    <form x-show="reassignOpen" x-cloak method="POST" action="{{ route('sales.viral-packages.reassign', $package) }}"
                          class="w-full flex items-center gap-2 mt-2">
                        @csrf
                        <select name="tech_team_id" required
                                class="flex-1 px-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100">
                            <option value="">— Pick a team member —</option>
                            @foreach($techTeam as $t)
                                <option value="{{ $t->id }}" @selected($package->tech_team_id === $t->id)>{{ $t->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-3 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-lg">Save</button>
                        <button type="button" @click="reassignOpen = false" class="px-3 py-2 text-sm text-gray-400 hover:text-gray-200">Cancel</button>
                    </form>
                </div>
            @else
                @if($package->techTeam)
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center gap-2">
                        <div class="w-7 h-7 bg-indigo-500/15 text-indigo-300 rounded-full flex items-center justify-center text-xs font-semibold">
                            {{ strtoupper(substr($package->techTeam->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Delivered by</p>
                            <p class="text-sm font-medium text-gray-100">{{ $package->techTeam->name }}</p>
                        </div>
                    </div>
                @endif
            @endif

            @if($package->canBeMarkedDelivered())
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3 flex-wrap">
                    <div class="flex items-center gap-2 text-sm text-emerald-300">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        All deliverables approved — ready to deliver.
                    </div>
                    <form method="POST" action="{{ route('sales.viral-packages.mark-delivered', $package) }}"
                          onsubmit="return confirm('Mark this package as delivered? This closes the package.');">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white text-sm font-medium rounded-lg shadow-lg shadow-emerald-500/20">
                            Mark delivered
                        </button>
                    </form>
                </div>
            @endif

            @if(! $package->isCompleted())
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                    <form method="POST" action="{{ route('sales.viral-packages.destroy', $package) }}"
                          onsubmit="return confirm('Delete this package? All deliverables and Drive folder will be removed. This cannot be undone.');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-rose-400 hover:text-rose-300">Delete package</button>
                    </form>
                </div>
            @endif
        </div>

        @if(! $package->drive_folder_id && ! $package->isCompleted())
            <div class="bg-amber-500/10 border border-amber-500/30 rounded-lg p-4 mb-6 flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-amber-200">No Drive folder created yet</p>
                    <p class="text-xs text-amber-100/80 mt-0.5">This package has no Google Drive folder. Uploads will fail until one is created. This usually means admin hasn't configured the "Viral Packages Drive folder ID" in Settings → General.</p>
                </div>
                <form method="POST" action="{{ route('sales.viral-packages.retry-drive-setup', $package) }}" class="flex-shrink-0">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-500 text-white text-xs font-medium rounded-md transition-colors whitespace-nowrap">
                        Retry Drive setup
                    </button>
                </form>
            </div>
        @endif

        {{-- Deliverables grid (live-updates when tech submits / picks up) --}}
        <div data-live="sales-viral-deliverables-{{ $package->id }}" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-100">{{ $package->deliverables->count() }} deliverables</h3>
                @unless($package->isCompleted())
                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('sales.viral-packages.posts.add', $package) }}">
                            @csrf
                            <input type="hidden" name="kind" value="article"/>
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-indigo-500/15 hover:bg-indigo-500/25 text-indigo-300 border border-indigo-500/30 rounded-lg transition-colors">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add article
                            </button>
                        </form>
                        <form method="POST" action="{{ route('sales.viral-packages.posts.add', $package) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-indigo-500/15 hover:bg-indigo-500/25 text-indigo-300 border border-indigo-500/30 rounded-lg transition-colors">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add post
                            </button>
                        </form>
                        <form method="POST" action="{{ route('sales.viral-packages.posts.add', $package) }}">
                            @csrf
                            <input type="hidden" name="kind" value="reel"/>
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-indigo-500/15 hover:bg-indigo-500/25 text-indigo-300 border border-indigo-500/30 rounded-lg transition-colors">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add reel
                            </button>
                        </form>
                    </div>
                @endunless
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($package->deliverables as $d)
                    @include('sales.viral-packages._deliverable-card', ['package' => $package, 'd' => $d])
                @endforeach
            </div>
        </div>

        {{-- Reference assets --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5"
             x-data="{ addOpen: false, assets: [] }"
             x-init="
                addAsset = () => assets.push({ uid: Date.now() + Math.random(), type: 'file', url: '', fileName: '', fileSize: '' });
                removeAsset = (i) => assets.splice(i, 1);
                handleAssetFile = (i, f) => { if (!f) return; assets[i].fileName = f.name; assets[i].fileSize = (f.size/1024/1024).toFixed(2) + ' MB'; };
                clearAssetFile = (i) => { assets[i].fileName = ''; assets[i].fileSize = ''; };
             ">
            <div class="flex items-center justify-between mb-4 gap-2 flex-wrap">
                <div>
                    <h3 class="text-sm font-medium text-gray-100">Reference assets</h3>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $package->assets->count() }} {{ Str::plural('item', $package->assets->count()) }} attached</p>
                </div>
                <div class="flex items-center gap-2">
                    @if($package->assets->isNotEmpty())
                        <a href="{{ route('sales.viral-packages.assets.download-all', $package) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-ink-800 hover:bg-ink-700 text-gray-300 text-xs font-medium rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download all (zip)
                        </a>
                    @endif
                    @if(! $package->isCompleted())
                        <button type="button" @click="addOpen = !addOpen; if (addOpen && assets.length === 0) addAsset();"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-indigo-500/15 hover:bg-indigo-500/25 text-indigo-300 border border-indigo-500/30 rounded-lg transition-colors">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add more
                        </button>
                    @endif
                </div>
            </div>

            {{-- Add-more form --}}
            <div x-show="addOpen" x-cloak class="mb-4">
                <form method="POST" action="{{ route('sales.viral-packages.assets.store', $package) }}" enctype="multipart/form-data" class="space-y-2">
                    @csrf
                    <template x-for="(asset, i) in assets" :key="asset.uid">
                        <div class="bg-ink-800/40 border border-ink-700 rounded-lg overflow-hidden">
                            <div class="flex items-center justify-between px-3 py-2 bg-ink-800/60 border-b border-ink-700">
                                <div class="inline-flex bg-ink-900 border border-ink-700 rounded-md p-0.5">
                                    <button type="button" @click="asset.type = 'file'; asset.url = ''"
                                            :class="asset.type === 'file' ? 'bg-indigo-600 text-white' : 'text-gray-400'"
                                            class="px-2 py-0.5 text-xs rounded">File</button>
                                    <button type="button" @click="asset.type = 'link'; clearAssetFile(i)"
                                            :class="asset.type === 'link' ? 'bg-indigo-600 text-white' : 'text-gray-400'"
                                            class="px-2 py-0.5 text-xs rounded">Link</button>
                                </div>
                                <button type="button" @click="removeAsset(i)" class="text-xs text-gray-500 hover:text-rose-400">Remove</button>
                            </div>
                            <input type="hidden" :name="`assets[${i}][type]`" :value="asset.type"/>
                            <template x-if="asset.type === 'file'">
                                <div class="p-2">
                                    <label :for="`extra-asset-${asset.uid}`" class="flex items-center gap-2 px-3 py-2 border-2 border-dashed border-ink-600 rounded-md cursor-pointer hover:border-indigo-500/60">
                                        <span x-show="!asset.fileName" class="text-xs text-indigo-400">Click to choose a file</span>
                                        <span x-show="asset.fileName" x-cloak class="text-xs text-gray-100 truncate" x-text="asset.fileName"></span>
                                    </label>
                                    <input type="file" :id="`extra-asset-${asset.uid}`" :name="`assets[${i}][file]`"
                                           @change="handleAssetFile(i, $event.target.files[0])" class="hidden"/>
                                </div>
                            </template>
                            <template x-if="asset.type === 'link'">
                                <div class="p-2">
                                    <input type="url" :name="`assets[${i}][url]`" x-model="asset.url" placeholder="https://..."
                                           class="w-full px-3 py-1.5 text-xs bg-ink-800 border border-ink-600 rounded text-gray-100"/>
                                </div>
                            </template>
                        </div>
                    </template>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="addAsset()" class="text-xs text-indigo-400 hover:underline">+ Add another</button>
                        <div class="flex-1"></div>
                        <button type="button" @click="addOpen = false; assets = [];" class="text-xs text-gray-500 hover:text-gray-300">Cancel</button>
                        <button type="submit" :disabled="assets.length === 0" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-xs font-medium rounded-md">Upload</button>
                    </div>
                </form>
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
                            <a href="{{ route('sales.viral-packages.assets.download', ['viralPackage' => $package, 'asset' => $asset]) }}"
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
