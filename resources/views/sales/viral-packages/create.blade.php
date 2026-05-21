<x-app-layout>
    <x-slot name="header">Add client to viral package</x-slot>
    <x-slot name="title">Add client</x-slot>

    <div class="p-6 max-w-4xl" x-data="viralPackageForm()">

        <a href="{{ route('sales.viral-packages.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-300 mb-3 transition-colors">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to viral package
        </a>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                <h2 class="text-sm font-medium text-gray-100">Add client to viral package</h2>
                <p class="text-xs text-gray-500 mt-0.5">Pick a client. We'll auto-create 7 deliverable slots: 1 Article, 5 Social posts, 1 Reel.</p>
            </div>

            <form method="POST" action="{{ route('sales.viral-packages.store') }}" enctype="multipart/form-data" class="px-6 py-5 space-y-5">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="client_id" class="block text-xs font-medium text-gray-300 mb-1.5">
                            Client <span class="text-rose-500">*</span>
                        </label>
                        <select id="client_id" name="client_id" required
                                class="w-full px-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">— Select an existing client —</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}" @selected(old('client_id') == $c->id)>{{ $c->name }}{{ $c->company ? " — {$c->company}" : '' }}</option>
                            @endforeach
                        </select>
                        @error('client_id')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-500">Need a new client? <a href="{{ route('sales.clients.create') }}" class="text-indigo-400 hover:underline">Add in Clients</a> first.</p>
                    </div>

                    <div>
                        <label for="tech_team_id" class="block text-xs font-medium text-gray-300 mb-1.5">
                            Assign to tech team member <span class="text-rose-500">*</span>
                        </label>
                        <select id="tech_team_id" name="tech_team_id" required
                                class="w-full px-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">— Select a team member —</option>
                            @foreach($techTeam as $t)
                                <option value="{{ $t->id }}" @selected(old('tech_team_id') == $t->id)>{{ $t->name }}</option>
                            @endforeach
                        </select>
                        @error('tech_team_id')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-500">Only this person will see and work on this package.</p>
                    </div>
                </div>

                <div class="pt-5 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h3 class="text-sm font-medium text-gray-100">Reference assets <span class="text-gray-500 font-normal text-xs">(optional)</span></h3>
                            <p class="text-xs text-gray-500 mt-0.5">Photos, brand guide, brief — anything tech needs. Saved in the package's "Reference Assets" Drive folder.</p>
                        </div>
                        <button type="button" x-show="assets.length > 0" @click="addAsset()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-indigo-500/15 hover:bg-indigo-500/25 text-indigo-300 border border-indigo-500/30 rounded-lg transition-colors">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add asset
                        </button>
                    </div>

                    {{-- Empty state --}}
                    <div x-show="assets.length === 0" x-cloak
                         class="border-2 border-dashed border-ink-700 rounded-xl px-6 py-10 text-center">
                        <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-ink-800 border border-ink-700 flex items-center justify-center">
                            <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-300 mb-1">No reference assets attached yet</p>
                        <p class="text-xs text-gray-500 mb-4">Tech team will work better with photos, brand guides, briefs, or links.</p>
                        <button type="button" @click="addAsset()"
                                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add first asset
                        </button>
                    </div>

                    <div class="space-y-4">
                        <template x-for="(asset, i) in assets" :key="asset.uid">
                            <div class="bg-ink-800/40 border border-ink-700 rounded-xl p-4 transition-all hover:border-ink-600">
                                {{-- Card header: type toggle + remove --}}
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[11px] text-gray-500 font-medium uppercase tracking-wider" x-text="'Asset ' + (i + 1)"></span>
                                        <span class="text-gray-700">·</span>
                                        <div class="inline-flex bg-ink-900 border border-ink-700 rounded-md p-0.5">
                                            <button type="button"
                                                    @click="asset.type = 'file'; asset.url = ''"
                                                    :class="asset.type === 'file' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-400 hover:text-gray-200'"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded transition-all">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                File
                                            </button>
                                            <button type="button"
                                                    @click="asset.type = 'link'; clearAssetFile(i)"
                                                    :class="asset.type === 'link' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-400 hover:text-gray-200'"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded transition-all">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                                </svg>
                                                Link
                                            </button>
                                        </div>
                                    </div>
                                    <button type="button" @click="removeAsset(i)"
                                            class="inline-flex items-center justify-center w-7 h-7 text-gray-500 hover:text-rose-400 hover:bg-rose-500/10 rounded-md transition-colors"
                                            title="Remove">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                                <input type="hidden" :name="`assets[${i}][type]`" :value="asset.type"/>

                                {{-- File mode --}}
                                <template x-if="asset.type === 'file'">
                                    <div>
                                        {{-- Empty drop zone --}}
                                        <template x-if="!asset.fileName">
                                            <label :for="`v-asset-file-${asset.uid}`"
                                                   @dragover.prevent="asset.dragOver = true"
                                                   @dragleave.prevent="asset.dragOver = false"
                                                   @drop.prevent="asset.dragOver = false; if ($event.dataTransfer.files[0]) { handleAssetFile(i, $event.dataTransfer.files[0]); const input = document.getElementById(`v-asset-file-${asset.uid}`); const dt = new DataTransfer(); dt.items.add($event.dataTransfer.files[0]); input.files = dt.files; }"
                                                   :class="asset.dragOver ? 'border-indigo-500 bg-indigo-500/10' : 'border-ink-600 hover:border-indigo-500/60 hover:bg-indigo-500/5'"
                                                   class="flex flex-col items-center justify-center gap-2 px-6 py-8 border-2 border-dashed rounded-xl cursor-pointer transition-all">
                                                <div class="w-12 h-12 rounded-full bg-indigo-500/15 text-indigo-400 flex items-center justify-center">
                                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                                    </svg>
                                                </div>
                                                <div class="text-center">
                                                    <p class="text-sm font-medium text-gray-200">
                                                        <span class="text-indigo-400">Click to browse</span> or drag a file here
                                                    </p>
                                                    <p class="text-xs text-gray-500 mt-0.5">Images, video, audio, PDF or documents</p>
                                                </div>
                                            </label>
                                        </template>

                                        {{-- Selected file preview --}}
                                        <template x-if="asset.fileName">
                                            <div class="flex items-center gap-3 px-4 py-3 bg-emerald-500/5 border-2 border-emerald-500/30 rounded-xl">
                                                <div class="w-11 h-11 rounded-lg flex items-center justify-center flex-shrink-0"
                                                     :class="{
                                                        'bg-emerald-500/15 text-emerald-400': asset.fileKind === 'image',
                                                        'bg-violet-500/15 text-violet-400': asset.fileKind === 'video',
                                                        'bg-amber-500/15 text-amber-400': asset.fileKind === 'audio',
                                                        'bg-rose-500/15 text-rose-400': asset.fileKind === 'pdf',
                                                        'bg-gray-500/15 text-gray-300': asset.fileKind === 'other'
                                                     }">
                                                    <svg x-show="asset.fileKind === 'image'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    <svg x-show="asset.fileKind === 'video'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                    <svg x-show="asset.fileKind === 'audio'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.657-1.343 3-3 3s-3-1.343-3-3 1.343-3 3-3 3 1.343 3 3zm12-3c0 1.657-1.343 3-3 3s-3-1.343-3-3 1.343-3 3-3 3 1.343 3 3z"/></svg>
                                                    <svg x-show="asset.fileKind === 'pdf' || asset.fileKind === 'other'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-100 truncate" x-text="asset.fileName"></p>
                                                    <p class="text-xs text-gray-500 mt-0.5">
                                                        <span x-text="asset.fileSize"></span>
                                                        <span class="text-gray-600"> · </span>
                                                        <span class="text-emerald-400">Ready to upload</span>
                                                    </p>
                                                </div>
                                                <label :for="`v-asset-file-${asset.uid}`"
                                                       class="text-xs text-indigo-400 hover:text-indigo-300 cursor-pointer font-medium whitespace-nowrap px-2 py-1 hover:bg-indigo-500/10 rounded transition-colors">
                                                    Replace
                                                </label>
                                            </div>
                                        </template>

                                        <input type="file" :id="`v-asset-file-${asset.uid}`" :name="`assets[${i}][file]`"
                                               @change="handleAssetFile(i, $event.target.files[0])"
                                               class="hidden"/>
                                    </div>
                                </template>

                                {{-- Link mode --}}
                                <template x-if="asset.type === 'link'">
                                    <div class="relative">
                                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                        </svg>
                                        <input type="url" :name="`assets[${i}][url]`" x-model="asset.url"
                                               placeholder="https://example.com/reference"
                                               class="w-full pl-10 pr-3 py-2.5 text-sm bg-ink-900 border border-ink-700 rounded-lg text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50"/>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- "Add another" inline button (only shown when at least 1 asset exists) --}}
                        <button type="button" x-show="assets.length > 0" @click="addAsset()"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 text-sm font-medium text-indigo-300 bg-indigo-500/5 hover:bg-indigo-500/10 border-2 border-dashed border-indigo-500/30 hover:border-indigo-500/50 rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add another asset
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <a href="{{ route('sales.viral-packages.index') }}" class="px-4 py-2 text-sm text-gray-300 hover:bg-ink-700 rounded-lg transition-colors">Cancel</a>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">Add client</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function viralPackageForm() {
            return {
                assets: [],
                addAsset() {
                    this.assets.push({ uid: Date.now() + Math.random(), type: 'file', url: '', fileName: '', fileSize: '', fileKind: 'other', dragOver: false });
                },
                removeAsset(i) { this.assets.splice(i, 1); },
                handleAssetFile(i, f) {
                    if (! f) return;
                    if (f.size > 200 * 1024 * 1024) {
                        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'File is larger than 200 MB.' } }));
                        return;
                    }
                    this.assets[i].fileName = f.name;
                    this.assets[i].fileSize = this.formatBytes(f.size);
                    this.assets[i].fileKind = this.detectKind(f);
                },
                detectKind(f) {
                    const m = (f.type || '').toLowerCase();
                    if (m.startsWith('image/')) return 'image';
                    if (m.startsWith('video/')) return 'video';
                    if (m.startsWith('audio/')) return 'audio';
                    if (m === 'application/pdf' || /\.pdf$/i.test(f.name)) return 'pdf';
                    return 'other';
                },
                clearAssetFile(i) { this.assets[i].fileName = ''; this.assets[i].fileSize = ''; this.assets[i].fileKind = 'other'; },
                formatBytes(b) {
                    if (b < 1024) return b + ' B';
                    if (b < 1024 * 1024) return (b / 1024).toFixed(1) + ' KB';
                    return (b / 1024 / 1024).toFixed(1) + ' MB';
                },
            }
        }
    </script>
</x-app-layout>
