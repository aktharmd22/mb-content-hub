<x-guest-layout>
    <div class="bg-ink-850 border border-ink-700 rounded-xl p-6 shadow-2xl shadow-black/50">
        <h2 class="text-base font-semibold text-gray-100 mb-1">Welcome back</h2>
        <p class="text-sm text-gray-500 mb-6">Sign in to continue to your workspace</p>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="username" class="label">Username or email</label>
                <input
                    id="username"
                    type="text"
                    name="username"
                    value="{{ old('username') }}"
                    required
                    autofocus
                    autocomplete="username"
                    class="field"
                    placeholder="username or email"
                />
                @error('username')
                    <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div x-data="{ show: false }">
                <label for="password" class="label">Password</label>
                <div class="relative">
                    <input
                        id="password"
                        :type="show ? 'text' : 'password'"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="field pr-10"
                        placeholder="••••••••"
                    />
                    <button
                        type="button"
                        @click="show = !show"
                        :title="show ? 'Hide password' : 'Show password'"
                        class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-300 focus:outline-none"
                    >
                        <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="show" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center pt-1">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        name="remember"
                        class="w-3.5 h-3.5 rounded border-ink-500 bg-ink-800 text-indigo-500 focus:ring-indigo-500/50"
                    />
                    <span class="text-xs text-gray-400">Remember me</span>
                </label>
            </div>

            <button
                type="submit"
                class="w-full py-2.5 px-4 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-sm font-medium rounded-lg transition-all shadow-lg shadow-indigo-500/20 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-2 focus:ring-offset-ink-850"
            >
                Sign in
            </button>
        </form>
    </div>
</x-guest-layout>
