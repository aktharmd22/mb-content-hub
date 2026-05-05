@props(['name', 'currentId' => null, 'available' => [], 'placeholder' => 'Select a folder'])
@php
    $availableIds = collect($available)->pluck('id')->all();
    $isCustomId   = $currentId && ! in_array($currentId, $availableIds, true);
@endphp

<div x-data="{ mode: '{{ $isCustomId ? 'custom' : 'select' }}', value: @js($currentId) }">
    <div class="flex items-center gap-2">
        <select
            x-show="mode === 'select'"
            name="{{ $name }}"
            x-model="value"
            class="flex-1 px-3 py-1.5 text-xs bg-ink-800 border border-ink-600 rounded-lg text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 cursor-pointer"
        >
            <option value="">— {{ $placeholder }} —</option>
            @if(empty($available) && $currentId)
                <option value="{{ $currentId }}" selected>Currently saved (ID: {{ \Str::limit($currentId, 24) }})</option>
            @endif
            @foreach($available as $f)
                <option value="{{ $f['id'] }}" @selected($currentId === $f['id'])>📁 {{ $f['name'] }}</option>
            @endforeach
        </select>

        <input
            x-show="mode === 'custom'"
            type="text"
            name="{{ $name }}"
            x-model="value"
            placeholder="Paste a folder ID"
            class="flex-1 px-3 py-1.5 text-xs font-mono bg-ink-800 border border-ink-600 rounded-lg text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500"
        />

        <button type="button"
                @click="mode = mode === 'select' ? 'custom' : 'select'"
                class="px-2 py-1.5 text-xs text-gray-500 hover:text-indigo-400 whitespace-nowrap transition-colors"
                :title="mode === 'select' ? 'Paste ID manually' : 'Pick from dropdown'">
            <span x-show="mode === 'select'">Paste ID</span>
            <span x-show="mode === 'custom'">Pick</span>
        </button>
    </div>

    @if($isCustomId)
        <p class="mt-1 text-xs text-amber-400">
            This folder isn't in the dropdown. The service account may not have access — verify in Drive.
        </p>
    @endif
</div>
