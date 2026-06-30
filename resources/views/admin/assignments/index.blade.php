<x-app-layout>
    <x-slot name="header">Assignment queue</x-slot>
    <x-slot name="title">Assignment queue</x-slot>

    <div class="p-6">

        <div class="mb-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Assignment queue</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Articles waiting to be assigned to a writer.</p>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
            @if($articles->count() === 0)
                <div class="p-12 text-center">
                    <svg class="w-8 h-8 text-emerald-300 dark:text-emerald-700 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Inbox is empty. All articles are assigned.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Code</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Title</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Client</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Sales rep</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Submitted</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Deadline</th>
                            <th class="text-right px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Assign to</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($articles as $a)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-950/50 transition-colors">
                                <td class="px-4 py-3 text-xs font-mono text-gray-500 dark:text-gray-400">{{ $a->article_code }}</td>
                                <td class="px-4 py-3">
                                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ $a->title }}</p>
                                    @if($a->priority === 'high')
                                        <p class="text-xs text-rose-600 dark:text-rose-400 mt-0.5">High priority</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $a->client?->displayName() ?? '—' }}</td>
                                <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">{{ $a->salesRep?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $a->submitted_at?->diffForHumans() ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if($a->deadline)
                                        @php $days = $a->days_until_deadline; @endphp
                                        @if($days < 0)
                                            <span class="text-rose-600 dark:text-rose-400">{{ abs($days) }}d late</span>
                                        @elseif($days <= 2)
                                            <span class="text-amber-600 dark:text-amber-400">{{ $a->deadline->format('M j') }}</span>
                                        @else
                                            <span class="text-gray-700 dark:text-gray-300">{{ $a->deadline->format('M j') }}</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <form method="POST" action="{{ route('admin.assignments.assign', $a) }}" class="flex items-center justify-end gap-2">
                                        @csrf
                                        <select name="tech_writer_id" required
                                                class="px-2 py-1 text-xs bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <option value="">Pick a writer</option>
                                            @foreach($writers as $w)
                                                <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->active_count }} active)</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-md transition-colors">
                                            Assign
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
                <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800">
                    {{ $articles->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
