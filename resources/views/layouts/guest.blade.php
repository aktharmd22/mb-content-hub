<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \App\Support\Branding::name() }}</title>
    @if($favicon = \App\Support\Branding::faviconUrl())
        <link rel="icon" href="{{ $favicon }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-ink-900 text-gray-100 flex items-center justify-center p-4 relative overflow-hidden">

    <!-- Decorative gradient orbs -->
    <div class="absolute top-1/4 -left-32 w-96 h-96 rounded-full opacity-20 blur-3xl pointer-events-none"
         style="background: radial-gradient(circle, rgb(99, 102, 241) 0%, transparent 70%);"></div>
    <div class="absolute bottom-1/4 -right-32 w-96 h-96 rounded-full opacity-20 blur-3xl pointer-events-none"
         style="background: radial-gradient(circle, rgb(236, 72, 153) 0%, transparent 70%);"></div>

    <div class="w-full max-w-sm relative">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center mb-3">
                @if($brandLogo = \App\Support\Branding::logoUrl())
                    <div class="h-16 flex items-center justify-center">
                        <img src="{{ $brandLogo }}" alt="{{ \App\Support\Branding::name() }}" class="max-h-full max-w-[14rem] object-contain"/>
                    </div>
                @else
                    <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/30">
                        <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                @endif
            </div>
            <p class="text-sm text-gray-500">Content workflow hub</p>
        </div>

        {{ $slot }}

        <p class="text-center text-xs text-gray-600 mt-6">
            &copy; {{ date('Y') }} {{ \App\Support\Branding::name() }}
        </p>
    </div>

    @livewireScripts
</body>
</html>
