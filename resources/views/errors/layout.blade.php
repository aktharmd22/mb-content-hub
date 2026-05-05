<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Error' }} — {{ \App\Support\Branding::name() }}</title>
    @if($favicon = \App\Support\Branding::faviconUrl())
        <link rel="icon" href="{{ $favicon }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ink-900 text-gray-100 flex items-center justify-center p-4 relative overflow-hidden">

    <div class="absolute top-1/4 -left-32 w-96 h-96 rounded-full opacity-20 blur-3xl pointer-events-none"
         style="background: radial-gradient(circle, rgb(99, 102, 241) 0%, transparent 70%);"></div>
    <div class="absolute bottom-1/4 -right-32 w-96 h-96 rounded-full opacity-15 blur-3xl pointer-events-none"
         style="background: radial-gradient(circle, rgb(244, 63, 94) 0%, transparent 70%);"></div>

    <div class="relative max-w-md w-full text-center">
        <div class="inline-flex items-center gap-2.5 mb-8">
            @if($brandLogo = \App\Support\Branding::logoUrl())
                <div class="w-12 h-12 rounded-xl flex items-center justify-center overflow-hidden bg-ink-850 border border-ink-700">
                    <img src="{{ $brandLogo }}" alt="{{ \App\Support\Branding::name() }}" class="max-w-full max-h-full object-contain"/>
                </div>
            @else
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/30">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            @endif
            <span class="text-xl font-semibold bg-gradient-to-r from-indigo-300 via-violet-300 to-pink-300 bg-clip-text text-transparent">{{ \App\Support\Branding::name() }}</span>
        </div>

        <p class="text-7xl font-bold {{ $codeColor ?? 'text-indigo-400' }} mb-3">{{ $code }}</p>
        <h1 class="text-xl font-semibold text-gray-100 mb-2">{{ $heading }}</h1>
        <p class="text-sm text-gray-500 mb-8">{{ $description }}</p>

        <div class="flex items-center justify-center gap-3">
            <a href="{{ url()->previous() }}"
               class="px-4 py-2 bg-ink-800 hover:bg-ink-700 border border-ink-600 text-gray-200 text-sm font-medium rounded-lg transition-colors">
                Go back
            </a>
            <a href="{{ route('dashboard') }}"
               class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-sm font-medium rounded-lg transition-all shadow-lg shadow-indigo-500/20">
                Go to dashboard
            </a>
        </div>
    </div>
</body>
</html>
