@props(['role'])

@php
    $config = [
        'admin'     => ['label' => 'Admin',     'class' => 'bg-rose-500/10 text-rose-300 border-rose-500/20'],
        'sales'     => ['label' => 'Sales',     'class' => 'bg-blue-500/10 text-blue-300 border-blue-500/20'],
        'tech_team' => ['label' => 'Tech team', 'class' => 'bg-indigo-500/10 text-indigo-300 border-indigo-500/20'],
    ][$role] ?? ['label' => $role, 'class' => 'bg-gray-500/10 text-gray-300 border-gray-500/20'];
@endphp

<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border {{ $config['class'] }}">
    {{ $config['label'] }}
</span>
