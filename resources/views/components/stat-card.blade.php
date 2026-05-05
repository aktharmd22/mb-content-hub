@props([
    'label',
    'value',
    'color' => 'indigo',
    'icon'  => null,
    'sub'   => null,
])

@php
    // Values stay white — color lives in the icon chip + subtle background tint
    $iconBg = [
        'indigo'  => 'bg-indigo-500/15 text-indigo-300',
        'emerald' => 'bg-emerald-500/15 text-emerald-300',
        'amber'   => 'bg-amber-500/15 text-amber-300',
        'rose'    => 'bg-rose-500/15 text-rose-300',
        'pink'    => 'bg-pink-500/15 text-pink-300',
        'blue'    => 'bg-blue-500/15 text-blue-300',
        'violet'  => 'bg-violet-500/15 text-violet-300',
        'orange'  => 'bg-orange-500/15 text-orange-300',
        'gray'    => 'bg-ink-700 text-gray-400',
    ][$color] ?? 'bg-indigo-500/15 text-indigo-300';
@endphp

<div class="card p-4 transition-colors hover:border-ink-600">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs text-gray-500">{{ $label }}</p>
            <p class="text-2xl font-semibold text-gray-100 mt-1">{{ $value }}</p>
            @if($sub)
                <p class="text-xs text-gray-500 mt-1">{{ $sub }}</p>
            @endif
        </div>
        @if($icon)
            <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 {{ $iconBg }}">
                {!! $icon !!}
            </div>
        @endif
    </div>
</div>
