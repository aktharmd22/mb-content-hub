@php
    /** @var \App\Models\Article $article */
    /** @var string $routeName */
    $assets = $article->assets;
@endphp

@if($assets->isNotEmpty())
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Assets</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ $assets->count() }} {{ Str::plural('item', $assets->count()) }} attached to this article
                    @if($article->assets_folder_name)
                        — folder: <span class="text-gray-700 dark:text-gray-300">{{ $article->assets_folder_name }}</span>
                    @endif
                </p>
            </div>
        </div>

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

                    <a href="{{ route($routeName, ['article' => $article, 'asset' => $asset]) }}"
                       @if($asset->type === 'link') target="_blank" rel="noopener" @endif
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-lg transition-colors whitespace-nowrap flex-shrink-0">
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
                </li>
            @endforeach
        </ul>
    </div>
@endif
