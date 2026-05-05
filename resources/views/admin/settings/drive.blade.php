<x-app-layout>
    <x-slot name="header">Settings</x-slot>
    <x-slot name="title">Drive settings</x-slot>

    @php
        $stageLabels = [
            'inbox'           => 'Inbox',
            'assigned'        => 'Assigned',
            'in_progress'     => 'In progress',
            'internal_review' => 'Internal review',
            'client_approval' => 'Client approval',
            'revisions'       => 'Revisions',
            'approved'        => 'Approved',
            'published'       => 'Published',
        ];
        $stageFolderKeys = \App\Services\GoogleDriveService::STAGE_FOLDERS;

        $formatBytes = function ($bytes) {
            if (! is_numeric($bytes) || $bytes <= 0) return '—';
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $i = 0;
            $b = (float) $bytes;
            while ($b >= 1024 && $i < count($units) - 1) { $b /= 1024; $i++; }
            return round($b, 1) . ' ' . $units[$i];
        };
    @endphp

    <div class="p-6 max-w-3xl">

        <div class="mb-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Settings</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Configure platform-wide integrations.</p>
        </div>

        @include('admin.settings._nav')

        <!-- Inline flash banner (always visible, complements the toast) -->
        @if(session('success'))
            <div class="mb-4 flex items-start gap-3 px-4 py-3 rounded-lg bg-emerald-500/10 border border-emerald-500/30">
                <svg class="w-4 h-4 text-emerald-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-emerald-200">{{ session('success') }}</p>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 flex items-start gap-3 px-4 py-3 rounded-lg bg-rose-500/10 border border-rose-500/30">
                <svg class="w-4 h-4 text-rose-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p class="text-sm text-rose-200">{{ session('error') }}</p>
            </div>
        @endif

        <!-- Connection status card -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5 mb-6">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    @if($configured && ($connection['ok'] ?? false))
                        <span class="w-2 h-2 rounded-full bg-emerald-500 flex-shrink-0"></span>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Connected</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $connection['account_email'] ?? $serviceAccountEmail }}
                            </p>
                        </div>
                    @elseif($configured)
                        <span class="w-2 h-2 rounded-full bg-rose-500 flex-shrink-0"></span>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Connection failed</p>
                            <p class="text-xs text-rose-600 dark:text-rose-400 mt-0.5">{{ $connection['error'] ?? 'Unable to reach Drive' }}</p>
                        </div>
                    @else
                        <span class="w-2 h-2 rounded-full bg-gray-300 dark:bg-gray-600 flex-shrink-0"></span>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Not configured</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Upload a service-account credentials file below.</p>
                        </div>
                    @endif
                </div>
                @if($configured)
                    <form method="POST" action="{{ route('admin.settings.drive.test') }}">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 text-xs font-medium bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                            Test connection
                        </button>
                    </form>
                @endif
            </div>

            @if($configured && ($connection['ok'] ?? false) && isset($connection['storage_used']))
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500 dark:text-gray-400">Storage</span>
                        <span class="text-gray-700 dark:text-gray-300">
                            {{ $formatBytes($connection['storage_used']) }}
                            @if(! empty($connection['storage_limit']))
                                / {{ $formatBytes($connection['storage_limit']) }}
                            @else
                                used
                            @endif
                        </span>
                    </div>
                    @if(! empty($connection['storage_limit']))
                        @php $pct = min(100, ($connection['storage_used'] / $connection['storage_limit']) * 100); @endphp
                        <div class="mt-2 w-full h-1 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                            <div class="h-full bg-indigo-600 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- OAuth user-account section (recommended for personal Gmail) -->
        <div class="card mb-6 border-indigo-500/30">
            <div class="px-5 py-4 border-b border-ink-700">
                <div class="flex items-center gap-2 mb-1">
                    <h3 class="text-sm font-medium text-gray-100">Connect your Google account</h3>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-500/15 text-indigo-300 border border-indigo-500/30">Recommended</span>
                </div>
                <p class="text-xs text-gray-500">Uses your personal storage. Required when uploading via personal Gmail (service accounts have 0 GB).</p>
            </div>

            <div class="px-5 py-5 space-y-4">
                @if($oauthConnected)
                    <div class="flex items-center justify-between gap-4 p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-lg">
                        <div class="flex items-center gap-3 min-w-0">
                            <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-emerald-200">Connected as {{ $oauthUserEmail ?? 'Google account' }}</p>
                                <p class="text-xs text-emerald-300/80 mt-0.5">Uploads use this account's storage.</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.settings.drive.oauth.disconnect') }}"
                              onsubmit="return confirm('Disconnect this Google account?');">
                            @csrf
                            <button type="submit" class="text-xs text-rose-400 hover:text-rose-300 whitespace-nowrap">Disconnect</button>
                        </form>
                    </div>
                @elseif($oauthConfigured)
                    <a href="{{ route('admin.settings.drive.oauth.start') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-sm font-medium rounded-lg transition-all shadow-lg shadow-indigo-500/20">
                        <svg class="w-4 h-4" viewBox="0 0 48 48"><path fill="#fff" d="M44.5 20H24v8.5h11.8C34.7 33.9 30 37 24 37c-7.2 0-13-5.8-13-13s5.8-13 13-13c3.1 0 5.9 1.1 8.1 2.9l6.4-6.4C34.6 4.1 29.6 2 24 2 11.8 2 2 11.8 2 24s9.8 22 22 22c11 0 21-8 21-22 0-1.3-.2-2.7-.5-4z"/></svg>
                        Connect Google account
                    </a>
                @endif

                <details class="group" {{ ! $oauthConfigured ? 'open' : '' }}>
                    <summary class="cursor-pointer text-xs text-gray-400 hover:text-gray-200 select-none flex items-center gap-1.5">
                        <svg class="w-3 h-3 transition-transform group-open:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        OAuth client setup {{ $oauthConfigured ? '(saved)' : '(required first time)' }}
                    </summary>

                    <div class="mt-3 ml-4 space-y-3">
                        <div class="text-xs text-gray-400 space-y-1.5">
                            <p>1. Go to <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener" class="text-indigo-400 hover:underline">Google Cloud Console → Credentials</a></p>
                            <p>2. Click <strong class="text-gray-200">Create Credentials → OAuth client ID</strong></p>
                            <p>3. Application type: <strong class="text-gray-200">Web application</strong></p>
                            <p>4. Add this Authorized redirect URI:</p>
                            <code class="block px-3 py-2 bg-ink-800 border border-ink-700 rounded text-xs font-mono text-gray-200 break-all select-all">{{ route('admin.settings.drive.oauth.callback') }}</code>
                            <p>5. Click <strong class="text-gray-200">Create</strong>; copy <strong>Client ID</strong> + <strong>Client secret</strong> below.</p>
                            <p class="text-amber-300/80">Make sure the OAuth consent screen has scopes <code class="text-amber-200">.../auth/drive</code> + <code class="text-amber-200">.../auth/userinfo.email</code>, and add your Gmail as a test user if your app is in "Testing" status.</p>
                        </div>

                        <form method="POST" action="{{ route('admin.settings.drive.oauth.credentials') }}" class="space-y-3 pt-2">
                            @csrf
                            <div>
                                <label class="label">OAuth client ID</label>
                                <input type="text" name="oauth_client_id" required
                                       value="{{ old('oauth_client_id', $oauthClientId) }}"
                                       placeholder="xxxxxx.apps.googleusercontent.com"
                                       class="field font-mono"/>
                                @error('oauth_client_id')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="label">OAuth client secret</label>
                                <input type="password" name="oauth_client_secret" required
                                       placeholder="{{ $oauthConfigured ? '•••••••• (saved — re-enter to replace)' : '' }}"
                                       class="field font-mono"/>
                                @error('oauth_client_secret')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                            </div>
                            <button type="submit" class="px-3 py-1.5 bg-ink-800 hover:bg-ink-700 border border-ink-600 text-gray-200 text-xs font-medium rounded-lg transition-colors">
                                Save OAuth client
                            </button>
                        </form>
                    </div>
                </details>
            </div>
        </div>

        <!-- Credentials section -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg mb-6">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Service-account credentials</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Upload the JSON key file from Google Cloud Console.</p>
            </div>

            <div class="px-5 py-5">
                @if($configured)
                    <div class="flex items-center justify-between gap-4 mb-4 p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900 rounded-lg">
                        <div class="flex items-center gap-3 min-w-0">
                            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-emerald-900 dark:text-emerald-200">Credentials uploaded</p>
                                <p class="text-xs text-emerald-700 dark:text-emerald-400 truncate">{{ $serviceAccountEmail }}</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.settings.drive.credentials.clear') }}"
                              onsubmit="return confirm('Remove credentials? Drive operations will stop working.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 whitespace-nowrap">
                                Remove
                            </button>
                        </form>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.settings.drive.credentials') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <label class="block">
                        <span class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ $configured ? 'Replace credentials file' : 'Credentials JSON file' }}
                        </span>
                        <input type="file" name="credentials" accept="application/json,.json" required
                               class="block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-gray-100 dark:file:bg-gray-800 file:text-gray-700 dark:file:text-gray-300 hover:file:bg-gray-200 dark:hover:file:bg-gray-700"/>
                        @error('credentials')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Save credentials
                    </button>
                </form>

                <details class="mt-5 text-xs text-gray-500 dark:text-gray-400">
                    <summary class="cursor-pointer hover:text-gray-700 dark:hover:text-gray-300 select-none">How to get a credentials file</summary>
                    <ol class="mt-3 ml-4 list-decimal space-y-1.5 text-gray-600 dark:text-gray-400">
                        <li>Go to <a href="https://console.cloud.google.com" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline">console.cloud.google.com</a> and create or select a project.</li>
                        <li>Enable the <strong>Google Drive API</strong> for that project.</li>
                        <li>Go to <strong>IAM &amp; Admin → Service Accounts → Create service account</strong>.</li>
                        <li>On the new service account, click <strong>Keys → Add key → Create new key → JSON</strong>. The file downloads automatically.</li>
                        <li>Upload that JSON file here.</li>
                        <li>Copy the service-account email (shown above after upload) and share your Drive folder with it as <strong>Editor</strong>.</li>
                    </ol>
                </details>
            </div>
        </div>

        <!-- Folder structure section -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg mb-6">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Folder structure</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">One folder per workflow stage. Files move between them automatically.</p>
                </div>
                @if($configured)
                    <form method="POST" action="{{ route('admin.settings.drive.setup') }}">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 text-xs font-medium bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg transition-colors whitespace-nowrap">
                            {{ $folders['root'] ? 'Re-create missing' : 'Create folders' }}
                        </button>
                    </form>
                @endif
            </div>

            @php
                // Build a quick lookup so we can detect IDs that don't appear in the dropdown
                $availableIds = collect($availableFolders)->pluck('id')->all();
                $renderFolderField = function (string $name, ?string $currentId, string $placeholder = 'Select a folder') use ($availableFolders, $availableIds) {
                    $isCustomId = $currentId && ! in_array($currentId, $availableIds, true);
                    return [
                        'available'   => $availableFolders,
                        'currentId'   => $currentId,
                        'isCustomId'  => $isCustomId,
                        'placeholder' => $placeholder,
                    ];
                };
            @endphp

            <form method="POST" action="{{ route('admin.settings.drive.folders') }}" class="px-5 py-5 space-y-3">
                @csrf

                @if(empty($availableFolders) && $configured)
                    <div class="px-3 py-2 rounded-md bg-amber-500/10 border border-amber-500/30 text-xs text-amber-200">
                        Could not load the folder list from Drive. You can still paste folder IDs manually below.
                    </div>
                @elseif(! empty($availableFolders))
                    <div class="px-3 py-2 rounded-md bg-ink-800 border border-ink-700 text-xs text-gray-400">
                        {{ count($availableFolders) }} folder{{ count($availableFolders) === 1 ? '' : 's' }} visible to the service account. Pick from the dropdowns or click <strong class="text-gray-200">Re-create missing</strong> above to auto-create the standard structure.
                    </div>
                @endif

                <div>
                    <label class="block text-xs font-medium text-gray-300 mb-1.5">Root folder</label>
                    <div class="flex items-center gap-2">
                        @include('admin.settings._folder-select', [
                            'name'          => 'drive_folder_root',
                            'currentId'     => old('drive_folder_root', $folders['root']),
                            'available'     => $availableFolders,
                            'placeholder'   => 'Auto (create on first save)',
                        ])
                        @if($folders['root'])
                            <a href="https://drive.google.com/drive/folders/{{ $folders['root'] }}" target="_blank" rel="noopener"
                               class="px-2 py-1.5 text-xs text-gray-500 hover:text-indigo-400 transition-colors" title="Open in Drive">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>

                <div class="pt-3 border-t border-ink-700">
                    <p class="text-xs font-medium text-gray-300 mb-3">Stage folders</p>
                    <div class="space-y-2">
                        @foreach($stageLabels as $stage => $label)
                            @php $key = $stageFolderKeys[$stage]; @endphp
                            <div class="flex items-center gap-3">
                                <span class="w-32 text-xs text-gray-400 flex-shrink-0">{{ $label }}</span>
                                <div class="flex-1">
                                    @include('admin.settings._folder-select', [
                                        'name'        => $key,
                                        'currentId'   => old($key, $folders[$stage] ?? ''),
                                        'available'   => $availableFolders,
                                        'placeholder' => 'Not set',
                                    ])
                                </div>
                                @if(! empty($folders[$stage]))
                                    <a href="https://drive.google.com/drive/folders/{{ $folders[$stage] }}" target="_blank" rel="noopener"
                                       class="text-xs text-gray-500 hover:text-indigo-400 transition-colors" title="Open in Drive">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end pt-3">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Save folder IDs
                    </button>
                </div>
            </form>
        </div>

        <!-- Recent uploads -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Recent uploads</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Last 10 files synced through the platform.</p>
            </div>

            @if($recentFiles->isEmpty())
                <div class="px-5 py-12 text-center">
                    <svg class="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m-7 3h14a2 2 0 002-2V8a2 2 0 00-2-2h-5l-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400">No files uploaded yet.</p>
                </div>
            @else
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($recentFiles as $f)
                        <div class="px-5 py-3 flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-sm text-gray-900 dark:text-gray-100 truncate">{{ $f->original_filename }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $f->stage ?? '—' }} · {{ $formatBytes($f->file_size) }} · {{ $f->uploader?->name ?? 'system' }} · {{ $f->uploaded_at?->diffForHumans() ?? $f->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <a href="https://drive.google.com/file/d/{{ $f->drive_file_id }}/view" target="_blank" rel="noopener"
                               class="text-xs text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors flex-shrink-0">
                                Open
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
