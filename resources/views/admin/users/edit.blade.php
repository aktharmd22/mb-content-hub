<x-app-layout>
    <x-slot name="header">Edit user</x-slot>
    <x-slot name="title">Edit user</x-slot>

    <div class="p-6 max-w-2xl">

        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 mb-3 transition-colors">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to users
        </a>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">Edit user</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">@{{ $user->username }}</p>
                </div>
                <x-status-dot :active="$user->is_active" />
            </div>

            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="px-6 py-5 space-y-4">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Username</label>
                        <input type="text" value="{{ $user->username }}" disabled
                               class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-500 dark:text-gray-400 cursor-not-allowed"/>
                        <p class="mt-1 text-xs text-gray-400">Username cannot be changed.</p>
                    </div>

                    <div>
                        <label for="name" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Display name <span class="text-rose-500">*</span>
                        </label>
                        <input id="name" type="text" name="name" required
                               value="{{ old('name', $user->name) }}"
                               class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"/>
                        @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                        <input id="email" type="email" name="email"
                               value="{{ old('email', $user->email) }}"
                               class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"/>
                        @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Phone</label>
                        <input id="phone" type="text" name="phone"
                               value="{{ old('phone', $user->phone) }}"
                               class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"/>
                        @error('phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="role" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Role <span class="text-rose-500">*</span>
                        </label>
                        <select id="role" name="role" required
                                @disabled($user->id === auth()->id())
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors disabled:bg-gray-50 dark:disabled:bg-gray-800 disabled:cursor-not-allowed">
                            <option value="admin"       @selected(old('role', $user->role) === 'admin')>Admin</option>
                            <option value="sales"       @selected(old('role', $user->role) === 'sales')>Sales</option>
                            <option value="tech_team"   @selected(old('role', $user->role) === 'tech_team')>Tech team</option>
                        </select>
                        @if($user->id === auth()->id())
                            <input type="hidden" name="role" value="{{ $user->role }}">
                            <p class="mt-1 text-xs text-gray-400">You cannot change your own role.</p>
                        @endif
                        @error('role')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center pt-6">
                        <label class="flex items-center gap-2 cursor-pointer {{ $user->id === auth()->id() ? 'cursor-not-allowed opacity-50' : '' }}">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1"
                                   @checked(old('is_active', $user->is_active))
                                   @disabled($user->id === auth()->id())
                                   class="w-3.5 h-3.5 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500"/>
                            <span class="text-xs text-gray-700 dark:text-gray-300">Account is active</span>
                        </label>
                        @if($user->id === auth()->id())
                            <input type="hidden" name="is_active" value="1">
                        @endif
                    </div>
                </div>

                <div class="pt-2 border-t border-gray-100 dark:border-gray-800">
                    <h3 class="text-xs font-medium text-gray-700 dark:text-gray-300 mt-3 mb-1">Reset password</h3>
                    <p class="text-xs text-gray-400 mb-3">Leave blank to keep the current password.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">New password</label>
                            <input id="password" type="password" name="password" minlength="8"
                                   autocomplete="new-password"
                                   class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"/>
                            @error('password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Confirm new password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation"
                                   autocomplete="new-password"
                                   class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"/>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <div>
                        @if($user->id !== auth()->id())
                            <button type="button"
                                    onclick="if(confirm('Delete {{ $user->name }}? This cannot be undone.')) document.getElementById('delete-form').submit();"
                                    class="text-xs text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300">
                                Delete user
                            </button>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                            Cancel
                        </a>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            Save changes
                        </button>
                    </div>
                </div>
            </form>

            @if($user->id !== auth()->id())
                <form id="delete-form" method="POST" action="{{ route('admin.users.destroy', $user) }}" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
