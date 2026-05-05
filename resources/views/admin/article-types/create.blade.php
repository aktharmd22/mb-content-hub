<x-app-layout>
    <x-slot name="header">New article type</x-slot>
    <x-slot name="title">New article type</x-slot>

    <div class="p-6 max-w-2xl">

        <a href="{{ route('admin.article-types.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-300 mb-3 transition-colors">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to article types
        </a>

        <div class="card">
            <div class="px-6 py-4 border-b border-ink-700">
                <h2 class="text-sm font-medium text-gray-100">New article type</h2>
            </div>

            <form method="POST" action="{{ route('admin.article-types.store') }}" class="px-6 py-5 space-y-4">
                @csrf
                @include('admin.article-types._form')

                <div class="flex justify-end gap-2 pt-2 border-t border-ink-700">
                    <a href="{{ route('admin.article-types.index') }}" class="px-4 py-2 text-sm text-gray-300 hover:bg-ink-700 rounded-lg transition-colors">Cancel</a>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">Create type</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
