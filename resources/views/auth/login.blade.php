<x-guest-layout>
    <div class="bg-ink-850 border border-ink-700 rounded-xl p-6 shadow-2xl shadow-black/50">
        <h2 class="text-base font-semibold text-gray-100 mb-1">Welcome back</h2>
        <p class="text-sm text-gray-500 mb-6">Sign in to continue to your workspace</p>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="username" class="label">Username</label>
                <input
                    id="username"
                    type="text"
                    name="username"
                    value="{{ old('username') }}"
                    required
                    autofocus
                    autocomplete="username"
                    class="field"
                    placeholder="your username"
                />
                @error('username')
                    <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="label">Password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    class="field"
                    placeholder="••••••••"
                />
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
