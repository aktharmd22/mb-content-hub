<x-app-layout>
    <x-slot name="header">Settings</x-slot>
    <x-slot name="title">General settings</x-slot>

    <div class="p-6 max-w-3xl">

        <div class="mb-6">
            <h2 class="text-lg font-medium text-gray-100">Settings</h2>
            <p class="text-sm text-gray-500 mt-0.5">Configure platform-wide integrations.</p>
        </div>

        @include('admin.settings._nav')

        <!-- Branding -->
        <div class="card mb-6">
            <div class="px-5 py-4 border-b border-ink-700">
                <h3 class="text-sm font-medium text-gray-100">Branding</h3>
                <p class="text-xs text-gray-500 mt-0.5">Customize the brand name and logo shown on the login page, sidebar, and emails.</p>
            </div>

            <div class="px-5 py-5 space-y-5">

                <!-- Logo upload -->
                <div>
                    <label class="block text-xs font-medium text-gray-300 mb-2">Logo <span class="text-gray-500 font-normal">— shown in sidebar, login page, and emails</span></label>

                    <div class="flex items-center gap-4 mb-3 p-4 bg-ink-800/50 border border-ink-700 rounded-lg">
                        <div class="w-16 h-16 bg-ink-900 border border-ink-600 rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="Logo" class="max-w-full max-h-full object-contain"/>
                            @else
                                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-200">{{ $logoUrl ? 'Custom logo uploaded' : 'Default logo (no upload)' }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">PNG, JPG, SVG or WEBP — up to 2 MB. Square or wide images work best.</p>
                        </div>
                        @if($logoUrl)
                            <form method="POST" action="{{ route('admin.settings.general.logo.remove') }}"
                                  onsubmit="return confirm('Remove the custom logo? The default will be used.');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-rose-400 hover:text-rose-300">Remove</button>
                            </form>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('admin.settings.general.logo') }}" enctype="multipart/form-data" class="flex items-center gap-2">
                        @csrf
                        <input type="file" name="logo" required accept="image/png,image/jpeg,image/svg+xml,image/webp"
                               class="block flex-1 text-sm text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-ink-700 file:text-gray-200 hover:file:bg-ink-600"/>
                        <button type="submit" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg transition-colors whitespace-nowrap">
                            {{ $logoUrl ? 'Replace logo' : 'Upload logo' }}
                        </button>
                    </form>
                    @error('logo')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                </div>

                <!-- Favicon upload -->
                <div class="pt-5 border-t border-ink-700">
                    <label class="block text-xs font-medium text-gray-300 mb-2">Favicon <span class="text-gray-500 font-normal">— shown in the browser tab</span></label>

                    <div class="flex items-center gap-4 mb-3 p-4 bg-ink-800/50 border border-ink-700 rounded-lg">
                        <div class="w-12 h-12 bg-ink-900 border border-ink-600 rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0">
                            @if($faviconUrl)
                                <img src="{{ $faviconUrl }}" alt="Favicon" class="max-w-full max-h-full object-contain"/>
                            @else
                                <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                </svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-200">{{ $faviconUrl ? 'Custom favicon uploaded' : 'Default favicon' }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">PNG, JPG, ICO, SVG or WEBP — up to 512 KB. Use a square image (32x32 or 64x64).</p>
                        </div>
                        @if($faviconUrl)
                            <form method="POST" action="{{ route('admin.settings.general.favicon.remove') }}"
                                  onsubmit="return confirm('Remove the favicon?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-rose-400 hover:text-rose-300">Remove</button>
                            </form>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('admin.settings.general.favicon') }}" enctype="multipart/form-data" class="flex items-center gap-2">
                        @csrf
                        <input type="file" name="favicon" required accept="image/png,image/jpeg,image/x-icon,image/vnd.microsoft.icon,image/svg+xml,image/webp,.ico"
                               class="block flex-1 text-sm text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-ink-700 file:text-gray-200 hover:file:bg-ink-600"/>
                        <button type="submit" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg transition-colors whitespace-nowrap">
                            {{ $faviconUrl ? 'Replace favicon' : 'Upload favicon' }}
                        </button>
                    </form>
                    @error('favicon')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <!-- System defaults + brand name -->
        <div class="card">
            <div class="px-5 py-4 border-b border-ink-700">
                <h3 class="text-sm font-medium text-gray-100">General</h3>
                <p class="text-xs text-gray-500 mt-0.5">Brand name and pipeline defaults.</p>
            </div>

            <form method="POST" action="{{ route('admin.settings.general.save') }}" class="px-5 py-5 space-y-5">
                @csrf

                <div>
                    <label for="app_brand_name" class="block text-xs font-medium text-gray-300 mb-1.5">Brand name</label>
                    <input id="app_brand_name" name="app_brand_name" type="text" maxlength="50" required
                           value="{{ old('app_brand_name', $appName) }}"
                           class="w-full px-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"/>
                    @error('app_brand_name')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                    <p class="mt-1 text-xs text-gray-500">Shown next to the logo and in the browser tab title.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="default_deadline_days" class="block text-xs font-medium text-gray-300 mb-1.5">Default deadline (days from submission)</label>
                        <input id="default_deadline_days" name="default_deadline_days" type="number" min="1" max="90" required
                               value="{{ old('default_deadline_days', $defaultDeadlineDays) }}"
                               class="w-full px-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"/>
                        @error('default_deadline_days')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-500">When sales submits without a deadline, this many days from now is used.</p>
                    </div>

                    <div>
                        <label for="stuck_threshold_days" class="block text-xs font-medium text-gray-300 mb-1.5">"Stuck" threshold (days)</label>
                        <input id="stuck_threshold_days" name="stuck_threshold_days" type="number" min="1" max="30" required
                               value="{{ old('stuck_threshold_days', $stuckThresholdDays) }}"
                               class="w-full px-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"/>
                        @error('stuck_threshold_days')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-500">Articles in the same stage longer than this trigger "stuck" alerts.</p>
                    </div>
                </div>

                <div class="flex justify-end pt-2 border-t border-ink-700">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Save changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
