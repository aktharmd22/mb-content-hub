@props(['active' => true, 'label' => null])

<span class="inline-flex items-center gap-1.5">
    <span class="relative flex w-1.5 h-1.5">
        @if($active)
            <span class="absolute inline-flex w-full h-full rounded-full bg-emerald-400 opacity-75 animate-ping"></span>
            <span class="relative inline-flex w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
        @else
            <span class="relative inline-flex w-1.5 h-1.5 rounded-full bg-gray-600"></span>
        @endif
    </span>
    <span class="text-xs {{ $active ? 'text-emerald-400' : 'text-gray-500' }}">
        {{ $label ?? ($active ? 'Active' : 'Inactive') }}
    </span>
</span>
