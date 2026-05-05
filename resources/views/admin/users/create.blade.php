<x-app-layout>
    <x-slot name="header">New user</x-slot>
    <x-slot name="title">New user</x-slot>

    <div class="p-6 max-w-2xl">

        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 mb-3 transition-colors">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to users
        </a>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">Create user</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Add a new team member with a role.</p>
            </div>

            <form method="POST" action="{{ route('admin.users.store') }}" class="px-6 py-5 space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="username" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Username <span class="text-rose-500">*</span>
                        </label>
                        <input
                            id="username" type="text" name="username"
                            value="{{ old('username') }}" required
                            class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                            placeholder="john.doe"
                        />
                        @error('username')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-400">Used for login. Cannot be changed later.</p>
                    </div>

                    <div>
                        <label for="name" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Display name <span class="text-rose-500">*</span>
                        </label>
                        <input
                            id="name" type="text" name="name"
                            value="{{ old('name') }}" required
                            class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                            placeholder="John Doe"
                        />
                        @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Email <span class="text-gray-400 font-normal">(for notifications)</span>
                        </label>
                        <input
                            id="email" type="email" name="email"
                            value="{{ old('email') }}"
                            class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                            placeholder="john@example.com"
                        />
                        @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Phone</label>
                        <input
                            id="phone" type="text" name="phone"
                            value="{{ old('phone') }}"
                            class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"
                            placeholder="+60 12 345 6789"
                        />
                        @error('phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="role" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Role <span class="text-rose-500">*</span>
                        </label>
                        <select id="role" name="role" required
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors">
                            <option value="">Select a role</option>
                            <option value="admin"       @selected(old('role') === 'admin')>Admin</option>
                            <option value="sales"       @selected(old('role') === 'sales')>Sales</option>
                            <option value="tech_team"   @selected(old('role') === 'tech_team')>Tech team</option>
                        </select>
                        @error('role')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center pt-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" checked
                                   class="w-3.5 h-3.5 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500"/>
                            <span class="text-xs text-gray-700 dark:text-gray-300">Account is active</span>
                        </label>
                    </div>
                </div>

                <div class="pt-2 border-t border-gray-100 dark:border-gray-800">
                    <h3 class="text-xs font-medium text-gray-700 dark:text-gray-300 mt-3 mb-3">Initial password</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Password <span class="text-rose-500">*</span>
                            </label>
                            <input id="password" type="password" name="password" required minlength="8"
                                   class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"/>
                            @error('password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                            <p class="mt-1 text-xs text-gray-400">Minimum 8 characters.</p>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Confirm password <span class="text-rose-500">*</span>
                            </label>
                            <input id="password_confirmation" type="password" name="password_confirmation" required
                                   class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"/>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <a href="{{ route('admin.users.index') }}" class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        Create user
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
