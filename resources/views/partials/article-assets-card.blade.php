@php
    /** @var \App\Models\Article $article */
    /** @var string $routeName */
    $assets = $article->assets;
    // Sales (owner) and admin can manage assets — only on sales views where edit routes exist
    $canManageAssets = isset($routeName)
        && str_starts_with($routeName, 'sales.')
        && (auth()->user()?->isAdmin() || $article->sales_rep_id === auth()->id());
@endphp

@if($assets->isNotEmpty() || $canManageAssets)
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5 mb-6"
         x-data="{ addOpen: false, assetType: 'file', fileName: '', fileSize: '' }">
        <div class="flex items-center justify-between gap-3 mb-4">
            <div>
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Assets</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    @if($assets->isEmpty())
                        No assets attached yet.
                    @else
                        {{ $assets->count() }} {{ Str::plural('item', $assets->count()) }} attached
                        @if($article->assets_folder_name)
                            — folder: <span class="text-gray-700 dark:text-gray-300">{{ $article->assets_folder_name }}</span>
                        @endif
                    @endif
                </p>
            </div>
            @if($canManageAssets)
                <button type="button" @click="addOpen = !addOpen"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-medium rounded-lg transition-colors whitespace-nowrap flex-shrink-0">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add asset
                </button>
            @endif
        </div>

        {{-- Add asset form (slides in when user clicks "Add asset") --}}
        @if($canManageAssets)
            <div x-show="addOpen" x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="mb-4 p-4 bg-ink-800/40 border border-ink-700 rounded-lg">
                <form method="POST" enctype="multipart/form-data"
                      action="{{ route('sales.articles.assets.store', $article) }}"
                      class="space-y-3">
                    @csrf

                    {{-- Type toggle --}}
                    <div class="flex items-center gap-2">
                        <p class="text-[11px] text-gray-400 font-medium uppercase tracking-wider">Type:</p>
                        <div class="inline-flex bg-ink-900 border border-ink-700 rounded-md p-0.5">
                            <button type="button" @click="assetType = 'file'"
                                    :class="assetType === 'file' ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:text-gray-200'"
                                    class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded transition-colors">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                File
                            </button>
                            <button type="button" @click="assetType = 'link'"
                                    :class="assetType === 'link' ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:text-gray-200'"
                                    class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded transition-colors">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                Link
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="type" :value="assetType"/>

                    {{-- File picker --}}
                    <template x-if="assetType === 'file'">
                        <div>
                            <label for="new-asset-file-{{ $article->id }}"
                                   :class="fileName ? 'border-emerald-500/40 bg-emerald-500/5' : 'border-ink-600 hover:border-indigo-500/60 hover:bg-indigo-500/5'"
                                   class="flex items-center gap-3 px-4 py-3 border-2 border-dashed rounded-lg cursor-pointer transition-colors">
                                <svg x-show="!fileName" class="w-5 h-5 text-indigo-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <svg x-show="fileName" x-cloak class="w-5 h-5 text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <p x-show="!fileName" class="text-sm text-gray-200 font-medium">Click to choose a file</p>
                                    <p x-show="!fileName" class="text-xs text-gray-500 mt-0.5">Up to 200 MB</p>
                                    <p x-show="fileName" x-cloak class="text-sm text-gray-100 truncate" x-text="fileName"></p>
                                    <p x-show="fileName" x-cloak class="text-xs text-gray-500" x-text="fileSize"></p>
                                </div>
                            </label>
                            <input type="file" id="new-asset-file-{{ $article->id }}" name="file"
                                   @change="if ($event.target.files[0]) { fileName = $event.target.files[0].name; fileSize = ($event.target.files[0].size/1024/1024).toFixed(2) + ' MB'; }"
                                   class="hidden"/>
                        </div>
                    </template>

                    {{-- URL input --}}
                    <template x-if="assetType === 'link'">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            <input type="url" name="url" placeholder="https://..."
                                   class="w-full pl-10 pr-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"/>
                        </div>
                    </template>

                    {{-- Optional label --}}
                    <input type="text" name="name" maxlength="255"
                           placeholder="Label (optional)"
                           class="w-full px-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"/>

                    <div class="flex items-center justify-end gap-2">
                        <button type="button" @click="addOpen = false; fileName = ''; fileSize = '';"
                                class="px-3 py-1.5 text-xs text-gray-400 hover:text-gray-200 transition-colors">Cancel</button>
                        <button type="submit"
                                class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-medium rounded-lg transition-colors">
                            Upload &amp; attach
                        </button>
                    </div>
                </form>
            </div>
        @endif

        @if($assets->isEmpty())
            <div class="text-center py-8 px-4 border-2 border-dashed border-ink-700 rounded-lg">
                <svg class="w-10 h-10 text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <p class="text-sm text-gray-400">No assets attached to this article</p>
                <p class="text-xs text-gray-500 mt-1">Click <span class="text-indigo-400 font-medium">Add asset</span> above to upload a file or paste a link.</p>
            </div>
        @endif

        <ul class="divide-y divide-gray-100 dark:divide-gray-800 -mx-5">
            @foreach($assets as $asset)
                <li class="flex items-center gap-3 px-5 py-3">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0
                        {{ $asset->type === 'link'
                            ? 'bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400'
                            : ($asset->isImage()
                                ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400'
                                : ($asset->isVideo()
                                    ? 'bg-violet-100 dark:bg-violet-950 text-violet-600 dark:text-violet-400'
                                    : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400')) }}">
                        @if($asset->type === 'link')
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                        @elseif($asset->isImage())
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        @elseif($asset->isVideo())
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        @endif
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-900 dark:text-gray-100 truncate">
                            {{ $asset->name ?: $asset->original_filename ?: 'Untitled' }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            @if($asset->type === 'link')
                                {{ $asset->url }}
                            @else
                                @if($asset->original_filename && $asset->original_filename !== $asset->name)
                                    {{ $asset->original_filename }}
                                @endif
                                @if($asset->file_size)
                                    @if($asset->original_filename && $asset->original_filename !== $asset->name) · @endif
                                    @php
                                        $bytes = (int) $asset->file_size;
                                        $sizeLabel = $bytes < 1024 ? $bytes . ' B'
                                            : ($bytes < 1024 * 1024 ? number_format($bytes / 1024, 1) . ' KB'
                                            : number_format($bytes / 1024 / 1024, 1) . ' MB');
                                    @endphp
                                    {{ $sizeLabel }}
                                @endif
                            @endif
                        </p>
                    </div>

                    <div class="flex items-center gap-2 flex-shrink-0">
                        <a href="{{ route($routeName, ['article' => $article, 'asset' => $asset]) }}"
                           @if($asset->type === 'link') target="_blank" rel="noopener" @endif
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-lg transition-colors whitespace-nowrap">
                            @if($asset->type === 'link')
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                                Open
                            @else
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Download
                            @endif
                        </a>

                        @if($canManageAssets && $asset->type === 'file')
                            {{-- Replace file: hidden input triggered by the "Replace" button --}}
                            <form method="POST" enctype="multipart/form-data"
                                  action="{{ route('sales.articles.assets.replace', ['article' => $article, 'asset' => $asset]) }}"
                                  class="inline-flex"
                                  x-data="{ submitting: false }"
                                  x-ref="replaceForm_{{ $asset->id }}">
                                @csrf
                                <input type="file" name="file"
                                       id="replace-asset-{{ $asset->id }}"
                                       class="hidden"
                                       @change="if ($event.target.files[0]) { if (confirm('Replace this file with ' + $event.target.files[0].name + '? The old file will be deleted from Drive.')) { submitting = true; $el.form.submit(); } else { $el.value = ''; } }"/>
                                <label for="replace-asset-{{ $asset->id }}"
                                       title="Replace file"
                                       class="inline-flex items-center justify-center w-8 h-8 bg-indigo-500/15 hover:bg-indigo-500/25 text-indigo-300 border border-indigo-500/30 rounded-lg cursor-pointer transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </label>
                            </form>
                        @endif

                        @if($canManageAssets)
                            <form method="POST"
                                  action="{{ route('sales.articles.assets.destroy', ['article' => $article, 'asset' => $asset]) }}"
                                  onsubmit="return confirm('Delete this asset? The file will also be removed from Drive. This cannot be undone.');"
                                  class="inline-flex">
                                @csrf @method('DELETE')
                                <button type="submit" title="Delete asset"
                                        class="inline-flex items-center justify-center w-8 h-8 text-gray-500 hover:text-rose-400 hover:bg-rose-500/10 border border-transparent hover:border-rose-500/30 rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endif
