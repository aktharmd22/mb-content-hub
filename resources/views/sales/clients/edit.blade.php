<x-app-layout>
    <x-slot name="header">Edit client</x-slot>
    <x-slot name="title">Edit client</x-slot>

    <div class="p-6 max-w-2xl">

        <a href="{{ route('sales.clients.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 mb-3 transition-colors">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to clients
        </a>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">Edit {{ $client->name }}</h2>
            </div>

            <form method="POST" action="{{ route('sales.clients.update', $client) }}" class="px-6 py-5 space-y-4">
                @csrf
                @method('PATCH')
                @include('sales.clients._form', ['client' => $client])

                <div class="flex justify-end gap-2 pt-2">
                    <a href="{{ route('sales.clients.index') }}" class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">Cancel</a>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
