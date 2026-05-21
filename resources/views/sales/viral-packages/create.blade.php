<x-app-layout>
    <x-slot name="header">New viral package</x-slot>
    <x-slot name="title">New viral package</x-slot>

    <div class="p-6 max-w-4xl" x-data="viralPackageForm()">

        <a href="{{ route('sales.viral-packages.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-300 mb-3 transition-colors">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to packages
        </a>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                <h2 class="text-sm font-medium text-gray-100">New viral package</h2>
                <p class="text-xs text-gray-500 mt-0.5">Pick a client. We'll auto-create 7 deliverable slots: 1 Article, 5 Social posts, 1 Reel.</p>
            </div>

            <form method="POST" action="{{ route('sales.viral-packages.store') }}" enctype="multipart/form-data" class="px-6 py-5 space-y-5">
                @csrf

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
                    <p class="mt-1 text-xs text-gray-500">Need a new client? Add them in <a href="{{ route('sales.clients.create') }}" class="text-indigo-400 hover:underline">Clients</a> first.</p>
                </div>

                <div class="pt-5 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h3 class="text-sm font-medium text-gray-100">Reference assets <span class="text-gray-500 font-normal text-xs">(optional)</span></h3>
                            <p class="text-xs text-gray-500 mt-0.5">Photos, brand guide, brief — anything tech needs. Saved in the package's "Reference Assets" Drive folder.</p>
                        </div>
                        <button type="button" @click="addAsset()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-indigo-500/15 hover:bg-indigo-500/25 text-indigo-300 border border-indigo-500/30 rounded-lg transition-colors">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add asset
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(asset, i) in assets" :key="asset.uid">
                            <div class="bg-ink-800/40 border border-ink-700 rounded-lg overflow-hidden">
                                <div class="flex items-center justify-between px-3 py-2 bg-ink-800/60 border-b border-ink-700">
                                    <div class="inline-flex bg-ink-900 border border-ink-700 rounded-md p-0.5">
                                        <button type="button"
                                                @click="asset.type = 'file'; asset.url = ''"
                                                :class="asset.type === 'file' ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:text-gray-200'"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded transition-colors">File</button>
                                        <button type="button"
                                                @click="asset.type = 'link'; clearAssetFile(i)"
                                                :class="asset.type === 'link' ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:text-gray-200'"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded transition-colors">Link</button>
                                    </div>
                                    <button type="button" @click="removeAsset(i)" class="text-xs text-gray-500 hover:text-rose-400">Remove</button>
                                </div>
                                <input type="hidden" :name="`assets[${i}][type]`" :value="asset.type"/>

                                <template x-if="asset.type === 'file'">
                                    <div class="p-3">
                                        <label :for="`v-asset-file-${asset.uid}`"
                                               :class="asset.fileName ? 'border-emerald-500/40 bg-emerald-500/5' : 'border-ink-600 hover:border-indigo-500/60 hover:bg-indigo-500/5'"
                                               class="flex items-center gap-3 px-4 py-3 border-2 border-dashed rounded-lg cursor-pointer transition-colors">
                                            <svg x-show="!asset.fileName" class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                            </svg>
                                            <svg x-show="asset.fileName" x-cloak class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <div class="flex-1 min-w-0">
                                                <p x-show="!asset.fileName" class="text-sm text-gray-300"><span class="font-medium text-indigo-400">Click to choose a file</span></p>
                                                <p x-show="asset.fileName" x-cloak class="text-sm text-gray-100 truncate" x-text="asset.fileName"></p>
                                                <p x-show="asset.fileName" x-cloak class="text-xs text-gray-500 mt-0.5" x-text="asset.fileSize"></p>
                                            </div>
                                        </label>
                                        <input type="file" :id="`v-asset-file-${asset.uid}`" :name="`assets[${i}][file]`"
                                               @change="handleAssetFile(i, $event.target.files[0])"
                                               class="hidden"/>
                                    </div>
                                </template>

                                <template x-if="asset.type === 'link'">
                                    <div class="p-3">
                                        <input type="url" :name="`assets[${i}][url]`" x-model="asset.url"
                                               placeholder="https://..."
                                               class="w-full px-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"/>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <a href="{{ route('sales.viral-packages.index') }}" class="px-4 py-2 text-sm text-gray-300 hover:bg-ink-700 rounded-lg transition-colors">Cancel</a>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">Create package</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function viralPackageForm() {
            return {
                assets: [],
                addAsset() {
                    this.assets.push({ uid: Date.now() + Math.random(), type: 'file', url: '', fileName: '', fileSize: '' });
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
                },
                clearAssetFile(i) { this.assets[i].fileName = ''; this.assets[i].fileSize = ''; },
                formatBytes(b) {
                    if (b < 1024) return b + ' B';
                    if (b < 1024 * 1024) return (b / 1024).toFixed(1) + ' KB';
                    return (b / 1024 / 1024).toFixed(1) + ' MB';
                },
            }
        }
    </script>
</x-app-layout>
