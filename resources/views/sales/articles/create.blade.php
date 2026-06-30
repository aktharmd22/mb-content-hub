<x-app-layout>
    <x-slot name="header">Submit new article</x-slot>
    <x-slot name="title">Submit article</x-slot>

    <div class="p-6 max-w-4xl"
         x-data="articleForm()">

        <a href="{{ route('sales.articles.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 mb-3 transition-colors">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to articles
        </a>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">New article</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Upload the source document and the writer team will take it from here.</p>
            </div>

            <form method="POST" action="{{ route('sales.articles.store') }}" enctype="multipart/form-data" class="px-6 py-5 space-y-4">
                @csrf

                <div>
                    <label for="title" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Article title <span class="text-rose-500">*</span>
                    </label>
                    <input id="title" name="title" type="text" required maxlength="255"
                           value="{{ old('title') }}"
                           class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                           placeholder="e.g. How AI is reshaping Malaysian fintech"/>
                    @error('title')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="client_id" class="text-xs font-medium text-gray-700 dark:text-gray-300">
                            Client <span class="text-gray-500 font-normal">(optional)</span>
                        </label>
                        <button type="button" @click="showClientModal = true" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">+ Add new client</button>
                    </div>
                    <select id="client_id" name="client_id" x-ref="clientSelect"
                            class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors">
                        <option value="">— No client —</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}" @selected(old('client_id') == $c->id)>{{ $c->displayName() }}{{ $c->secondaryName() ? " — {$c->secondaryName()}" : '' }}</option>
                        @endforeach
                    </select>
                    @error('client_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                @if($types->isNotEmpty())
                    <div>
                        <label for="article_type_id" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Article type <span class="text-rose-500">*</span>
                        </label>
                        <select id="article_type_id" name="article_type_id" required
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors">
                            <option value="">Select a type</option>
                            @foreach($types as $t)
                                <option value="{{ $t->id }}" @selected(old('article_type_id') == $t->id)>{{ $t->name }}{{ $t->description ? " — {$t->description}" : '' }}</option>
                            @endforeach
                        </select>
                        @error('article_type_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-500">The file uploads to the folder configured for this type.</p>
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="priority" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Priority <span class="text-rose-500">*</span>
                        </label>
                        <select id="priority" name="priority" required
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors">
                            <option value="low"    @selected(old('priority') === 'low')>Low</option>
                            <option value="medium" @selected(old('priority', 'medium') === 'medium')>Medium</option>
                            <option value="high"   @selected(old('priority') === 'high')>High</option>
                        </select>
                    </div>

                    <div>
                        <label for="deadline" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Deadline</label>
                        <input id="deadline" name="deadline" type="date" min="{{ now()->toDateString() }}"
                               value="{{ old('deadline') }}"
                               class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"/>
                        @error('deadline')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="notes" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Notes for the writer</label>
                    <textarea id="notes" name="notes" rows="3" maxlength="2000"
                              class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                              placeholder="Tone, audience, key points to cover...">{{ old('notes') }}</textarea>
                </div>

                <!-- File upload (drag-drop) -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Source file <span class="text-rose-500">*</span>
                    </label>
                    <div
                        @dragover.prevent="dragOver = true"
                        @dragleave.prevent="dragOver = false"
                        @drop.prevent="handleDrop($event)"
                        @click="$refs.fileInput.click()"
                        :class="{
                            'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/40': dragOver,
                            'border-gray-300 dark:border-gray-700': !dragOver && !file,
                            'border-emerald-300 dark:border-emerald-700 bg-emerald-50/50 dark:bg-emerald-950/20': file
                        }"
                        class="border-2 border-dashed rounded-lg px-4 py-8 text-center cursor-pointer transition-colors"
                    >
                        <template x-if="!file">
                            <div>
                                <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    <span class="font-medium text-indigo-600 dark:text-indigo-400">Click to upload</span> or drag and drop
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Documents, images, audio or video — up to 200 MB</p>
                            </div>
                        </template>
                        <template x-if="file">
                            <div class="flex items-center justify-center gap-3">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div class="text-left min-w-0">
                                    <p class="text-sm text-gray-900 dark:text-gray-100 truncate" x-text="file.name"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="formatBytes(file.size)"></p>
                                </div>
                                <button type="button" @click.stop="clearFile()" class="text-xs text-rose-600 hover:underline">Remove</button>
                            </div>
                        </template>
                    </div>
                    <input type="file" name="file" x-ref="fileInput" @change="handleFile($event.target.files[0])"
                           accept=".doc,.docx,.pdf,.txt,.jpg,.jpeg,.png,.gif,.webp,.mp4,.mov,.avi,.webm,.mp3,.wav,.m4a"
                           class="hidden" required/>
                    @error('file')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <!-- Assets section: optional images/videos/PDFs/links saved in a subfolder -->
                <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h3 class="text-sm font-medium text-gray-100">Assets <span class="text-gray-500 font-normal text-xs">(optional)</span></h3>
                            <p class="text-xs text-gray-500 mt-0.5">Attach images, video, audio, PDFs or links. Saved to a subfolder named after the article title.</p>
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
                                <!-- Type toggle bar -->
                                <div class="flex items-center justify-between px-3 py-2 bg-ink-800/60 border-b border-ink-700">
                                    <div class="inline-flex bg-ink-900 border border-ink-700 rounded-md p-0.5">
                                        <button type="button"
                                                @click="asset.type = 'file'; asset.url = ''"
                                                :class="asset.type === 'file' ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:text-gray-200'"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded transition-colors">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            File
                                        </button>
                                        <button type="button"
                                                @click="asset.type = 'link'; clearAssetFile(i)"
                                                :class="asset.type === 'link' ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:text-gray-200'"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded transition-colors">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                            </svg>
                                            Link
                                        </button>
                                    </div>
                                    <button type="button" @click="removeAsset(i)" title="Remove"
                                            class="inline-flex items-center gap-1 px-2 py-1 text-xs text-gray-500 hover:text-rose-400 hover:bg-rose-500/10 rounded transition-colors">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Remove
                                    </button>
                                </div>

                                <input type="hidden" :name="`assets[${i}][type]`" :value="asset.type"/>

                                <!-- File body -->
                                <template x-if="asset.type === 'file'">
                                    <div class="p-3">
                                        <label
                                            :for="`asset-file-${asset.uid}`"
                                            :class="asset.fileName
                                                ? 'border-emerald-500/40 bg-emerald-500/5'
                                                : 'border-ink-600 hover:border-indigo-500/60 hover:bg-indigo-500/5'"
                                            class="flex items-center gap-3 px-4 py-3 border-2 border-dashed rounded-lg cursor-pointer transition-colors">
                                            <div class="w-9 h-9 rounded-lg bg-ink-800 border border-ink-700 flex items-center justify-center flex-shrink-0">
                                                <svg x-show="!asset.fileName" class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                                </svg>
                                                <svg x-show="asset.fileName" x-cloak class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p x-show="!asset.fileName" class="text-sm text-gray-300">
                                                    <span class="font-medium text-indigo-400">Click to choose a file</span> or drag it here
                                                </p>
                                                <p x-show="asset.fileName" x-cloak class="text-sm text-gray-100 truncate" x-text="asset.fileName"></p>
                                                <p x-show="!asset.fileName" class="text-xs text-gray-500 mt-0.5">Images, video, audio, PDF or document</p>
                                                <p x-show="asset.fileName" x-cloak class="text-xs text-gray-500 mt-0.5" x-text="asset.fileSize"></p>
                                            </div>
                                            <span x-show="asset.fileName" x-cloak class="text-xs text-indigo-400 hover:underline">Replace</span>
                                        </label>
                                        <input type="file"
                                               :id="`asset-file-${asset.uid}`"
                                               :name="`assets[${i}][file]`"
                                               @change="handleAssetFile(i, $event.target.files[0])"
                                               accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.mov,.avi,.webm,.mp3,.wav,.m4a,.pdf,.doc,.docx,.txt"
                                               class="hidden"/>
                                    </div>
                                </template>

                                <!-- Link body -->
                                <template x-if="asset.type === 'link'">
                                    <div class="p-3">
                                        <div class="relative">
                                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                            </svg>
                                            <input type="url" :name="`assets[${i}][url]`" x-model="asset.url"
                                                   placeholder="https://..."
                                                   class="w-full pl-9 pr-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"/>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <a href="{{ route('sales.articles.index') }}" class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Submit article
                    </button>
                </div>
            </form>
        </div>

        <!-- Quick-create client modal -->
        <div x-show="showClientModal" x-cloak
             x-transition.opacity
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
             @click.self="showClientModal = false"
             @keydown.escape.window="showClientModal = false">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg w-full max-w-md p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Add new client</h3>
                    <button type="button" @click="showClientModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="createClient()" class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Client name <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="newClient.name" required maxlength="255"
                               class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"/>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Company</label>
                        <input type="text" x-model="newClient.company" maxlength="255"
                               class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"/>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                            <input type="email" x-model="newClient.contact_email"
                                   class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"/>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Phone</label>
                            <input type="text" x-model="newClient.contact_phone"
                                   class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"/>
                        </div>
                    </div>

                    <p x-show="clientError" x-text="clientError" class="text-xs text-rose-600"></p>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showClientModal = false" class="px-3 py-1.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="creatingClient"
                                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition-colors">
                            <span x-show="!creatingClient">Add client</span>
                            <span x-show="creatingClient">Adding...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function articleForm() {
            return {
                file: null,
                dragOver: false,
                showClientModal: false,
                creatingClient: false,
                clientError: '',
                newClient: { name: '', company: '', contact_email: '', contact_phone: '' },

                // Asset rows — each: { uid, type, url, fileName, fileSize }
                assets: [],
                addAsset() {
                    this.assets.push({
                        uid: Date.now() + Math.random(),
                        type: 'file',
                        url: '',
                        fileName: '',
                        fileSize: '',
                    });
                },
                removeAsset(i) {
                    this.assets.splice(i, 1);
                },
                handleAssetFile(i, f) {
                    if (! f) {
                        this.assets[i].fileName = '';
                        this.assets[i].fileSize = '';
                        return;
                    }
                    if (f.size > 200 * 1024 * 1024) {
                        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Asset file is larger than 200 MB.' } }));
                        return;
                    }
                    this.assets[i].fileName = f.name;
                    this.assets[i].fileSize = this.formatBytes(f.size);
                },
                clearAssetFile(i) {
                    this.assets[i].fileName = '';
                    this.assets[i].fileSize = '';
                },

                handleFile(f) {
                    if (! f) return;
                    if (f.size > 200 * 1024 * 1024) {
                        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'File is larger than 200 MB.' } }));
                        return;
                    }
                    this.file = f;
                    this.$refs.fileInput.files = this.makeFileList(f);
                },
                handleDrop(e) {
                    this.dragOver = false;
                    if (e.dataTransfer.files.length) this.handleFile(e.dataTransfer.files[0]);
                },
                clearFile() {
                    this.file = null;
                    this.$refs.fileInput.value = '';
                },
                makeFileList(file) {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    return dt.files;
                },
                formatBytes(b) {
                    if (b < 1024) return b + ' B';
                    if (b < 1024 * 1024) return (b / 1024).toFixed(1) + ' KB';
                    return (b / 1024 / 1024).toFixed(1) + ' MB';
                },
                async createClient() {
                    this.creatingClient = true;
                    this.clientError = '';
                    try {
                        const res = await fetch('{{ route("sales.clients.quick-create") }}', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify(this.newClient),
                        });
                        if (! res.ok) {
                            const body = await res.json().catch(() => ({}));
                            this.clientError = body.message || 'Could not create client.';
                            return;
                        }
                        const c = await res.json();
                        const opt = new Option(c.company ? `${c.company} — ${c.name}` : c.name, c.id, true, true);
                        this.$refs.clientSelect.add(opt);
                        this.$refs.clientSelect.value = c.id;
                        this.showClientModal = false;
                        this.newClient = { name: '', company: '', contact_email: '', contact_phone: '' };
                        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Client added.' } }));
                    } catch (err) {
                        this.clientError = 'Network error. Try again.';
                    } finally {
                        this.creatingClient = false;
                    }
                },
            }
        }
    </script>
</x-app-layout>
