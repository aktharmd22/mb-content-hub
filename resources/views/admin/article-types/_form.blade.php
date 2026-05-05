@php $type = $type ?? null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="sm:col-span-2">
        <label for="name" class="label">Name <span class="text-rose-500">*</span></label>
        <input id="name" name="name" type="text" required maxlength="100"
               value="{{ old('name', $type?->name) }}"
               class="field" placeholder="e.g. Interview, Profile, Case study"/>
        @error('name')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="description" class="label">Description <span class="text-gray-500 font-normal">(optional)</span></label>
        <input id="description" name="description" type="text" maxlength="255"
               value="{{ old('description', $type?->description) }}"
               class="field" placeholder="Short description shown next to the dropdown option"/>
        @error('description')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label class="label">Upload folder</label>
        @include('admin.settings._folder-select', [
            'name'        => 'drive_folder_id',
            'currentId'   => old('drive_folder_id', $type?->drive_folder_id),
            'available'   => $folders,
            'placeholder' => 'Use Inbox stage folder',
        ])
        <p class="mt-1 text-xs text-gray-500">Articles of this type will upload directly to the selected folder. Leave blank to use the default Inbox stage folder.</p>
    </div>

    <div>
        <label for="sort_order" class="label">Sort order</label>
        <input id="sort_order" name="sort_order" type="number" min="0" max="1000"
               value="{{ old('sort_order', $type?->sort_order ?? 0) }}"
               class="field"/>
        <p class="mt-1 text-xs text-gray-500">Lower numbers appear first in dropdowns.</p>
    </div>

    <div class="flex items-center pt-6">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1"
                   @checked(old('is_active', $type?->is_active ?? true))
                   class="w-3.5 h-3.5 rounded border-ink-500 bg-ink-800 text-indigo-500 focus:ring-indigo-500/50"/>
            <span class="text-xs text-gray-300">Active (sales can pick this type)</span>
        </label>
    </div>
</div>
