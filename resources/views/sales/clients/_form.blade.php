@php $client = $client ?? null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label for="name" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
            Client name <span class="text-rose-500">*</span>
        </label>
        <input id="name" name="name" type="text" required maxlength="255"
               value="{{ old('name', $client?->name) }}"
               class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"/>
        @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="company" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Company</label>
        <input id="company" name="company" type="text" maxlength="255"
               value="{{ old('company', $client?->company) }}"
               class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"/>
        @error('company')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="contact_email" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Contact email</label>
        <input id="contact_email" name="contact_email" type="email" maxlength="255"
               value="{{ old('contact_email', $client?->contact_email) }}"
               class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"/>
        @error('contact_email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="contact_phone" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Contact phone</label>
        <input id="contact_phone" name="contact_phone" type="text" maxlength="30"
               value="{{ old('contact_phone', $client?->contact_phone) }}"
               class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"/>
        @error('contact_phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label for="notes" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Notes</label>
    <textarea id="notes" name="notes" rows="3" maxlength="2000"
              class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('notes', $client?->notes) }}</textarea>
    @error('notes')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
</div>
