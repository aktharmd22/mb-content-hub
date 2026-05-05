@php
    $tabs = [
        ['route' => 'admin.settings.general', 'label' => 'General'],
        ['route' => 'admin.settings.drive',   'label' => 'Google Drive'],
    ];
@endphp

<div class="border-b border-gray-200 dark:border-gray-800 mb-6">
    <nav class="flex gap-1 -mb-px" aria-label="Settings tabs">
        @foreach($tabs as $tab)
            @php $active = request()->routeIs($tab['route']); @endphp
            <a href="{{ route($tab['route']) }}"
               class="px-3 py-2 text-sm border-b-2 -mb-px transition-colors
                      {{ $active
                         ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-medium'
                         : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
</div>
