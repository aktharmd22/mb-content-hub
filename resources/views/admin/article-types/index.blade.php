<x-app-layout>
    <x-slot name="header">Article types</x-slot>
    <x-slot name="title">Article types</x-slot>

    @php
        $folderById = collect($folders)->keyBy('id');
    @endphp

    <div class="p-6 max-w-4xl">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-medium text-gray-100">Article types</h2>
                <p class="text-sm text-gray-500 mt-0.5">Define what kinds of articles sales can submit and which Drive folder each type uploads to.</p>
            </div>
            <a href="{{ route('admin.article-types.create') }}"
               class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add type
            </a>
        </div>

        <div class="card overflow-hidden">
            @if($types->count() === 0)
                <div class="p-12 text-center">
                    <p class="text-sm text-gray-500 mb-3">No article types yet. Sales submissions go to the default Inbox folder.</p>
                    <a href="{{ route('admin.article-types.create') }}" class="text-xs text-indigo-400 hover:underline">Add your first type</a>
                </div>
            @else
                <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead class="bg-ink-800 border-b border-ink-700">
                        <tr>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-400 uppercase tracking-wide">Name</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-400 uppercase tracking-wide">Upload folder</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-400 uppercase tracking-wide">Articles</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-400 uppercase tracking-wide">Status</th>
                            <th class="text-right px-4 py-2.5 font-medium text-xs text-gray-400 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-700">
                        @foreach($types as $t)
                            <tr class="hover:bg-ink-800/50 transition-colors">
                                <td class="px-4 py-3">
                                    <p class="text-sm text-gray-100">{{ $t->name }}</p>
                                    @if($t->description)
                                        <p class="text-xs text-gray-500">{{ $t->description }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-400">
                                    @if($t->drive_folder_id && isset($folderById[$t->drive_folder_id]))
                                        📁 {{ $folderById[$t->drive_folder_id]['name'] }}
                                    @elseif($t->drive_folder_id)
                                        <span class="font-mono">{{ \Str::limit($t->drive_folder_id, 16) }}</span>
                                        <span class="text-amber-400 ml-1">(not in dropdown)</span>
                                    @else
                                        <span class="text-gray-500">— uses Inbox stage folder</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-300">{{ $t->articles_count }}</td>
                                <td class="px-4 py-3">
                                    @if($t->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-500/10 text-emerald-300 border border-emerald-500/20">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-500/10 text-gray-400 border border-gray-500/20">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.article-types.edit', $t) }}" class="px-2 py-1 text-xs text-gray-400 hover:text-indigo-400 transition-colors">Edit</a>
                                        @if($t->articles_count === 0)
                                            <form method="POST" action="{{ route('admin.article-types.destroy', $t) }}"
                                                  onsubmit="return confirm('Delete {{ $t->name }}?');" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="px-2 py-1 text-xs text-gray-400 hover:text-rose-400 transition-colors">Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
