@props(['stage'])

@php
    $stage = $stage instanceof \App\Enums\ArticleStage
        ? $stage
        : \App\Enums\ArticleStage::tryFrom((string) $stage);

    if (! $stage) return;

    $classes = match ($stage->color()) {
        'gray'    => 'bg-gray-500/10 text-gray-300 border-gray-500/20',
        'blue'    => 'bg-blue-500/10 text-blue-300 border-blue-500/20',
        'indigo'  => 'bg-indigo-500/10 text-indigo-300 border-indigo-500/20',
        'amber'   => 'bg-amber-500/10 text-amber-300 border-amber-500/20',
        'pink'    => 'bg-pink-500/10 text-pink-300 border-pink-500/20',
        'orange'  => 'bg-orange-500/10 text-orange-300 border-orange-500/20',
        'emerald' => 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20',
        'green'   => 'bg-green-500/10 text-green-300 border-green-500/20',
        default   => 'bg-gray-500/10 text-gray-300 border-gray-500/20',
    };
@endphp

<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border {{ $classes }}">
    {{ $stage->label() }}
</span>
