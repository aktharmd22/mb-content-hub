<x-app-layout>
    <x-slot name="header">Team performance</x-slot>
    <x-slot name="title">Team</x-slot>

    <div class="p-6">

        <div class="mb-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Team performance</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Last 30 days. Helps spot bottlenecks and uneven load.</p>
        </div>

        <!-- Per-writer table -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden mb-6">
            @if($performance->isEmpty())
                <div class="p-12 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">No tech writers yet.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Writer</th>
                            <th class="text-right px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Active</th>
                            <th class="text-right px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Completed (30d)</th>
                            <th class="text-right px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Avg. days</th>
                            <th class="text-right px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Revisions</th>
                            <th class="text-right px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Revision rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($performance as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-950/50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-7 h-7 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center flex-shrink-0">
                                            <span class="text-xs font-medium text-indigo-700 dark:text-indigo-300">{{ strtoupper(substr($row['writer']->name, 0, 1)) }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $row['writer']->name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ '@' . $row['writer']->username }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-gray-900 dark:text-gray-100">{{ $row['active'] }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-900 dark:text-gray-100">{{ $row['completed'] }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">{{ $row['avg_days'] !== null ? $row['avg_days'] . 'd' : '—' }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">{{ $row['revisions'] }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    @if($row['revision_rate'] === null)
                                        <span class="text-gray-400">—</span>
                                    @elseif($row['revision_rate'] >= 50)
                                        <span class="text-rose-600 dark:text-rose-400">{{ $row['revision_rate'] }}%</span>
                                    @elseif($row['revision_rate'] >= 25)
                                        <span class="text-amber-600 dark:text-amber-400">{{ $row['revision_rate'] }}%</span>
                                    @else
                                        <span class="text-emerald-600 dark:text-emerald-400">{{ $row['revision_rate'] }}%</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            @endif
        </div>

        <!-- Bottlenecks -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg">
            <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Bottlenecks</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Articles in the same stage for more than 3 days.</p>
            </div>
            @if($bottlenecks->isEmpty())
                <div class="px-5 py-12 text-center">
                    <svg class="w-8 h-8 text-emerald-300 dark:text-emerald-700 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Pipeline is healthy. Nothing stuck.</p>
                </div>
            @else
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($bottlenecks as $a)
                        <li>
                            <a href="{{ route('lead.articles.show', $a) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-950/50 transition-colors">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <span class="text-xs font-mono text-gray-400">{{ $a->article_code }}</span>
                                        <x-stage-badge :stage="$a->current_stage" />
                                    </div>
                                    <p class="text-sm text-gray-900 dark:text-gray-100 truncate">{{ $a->title }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ $a->client?->name ?? '—' }} · {{ $a->techWriter?->name ?? 'unassigned' }}
                                    </p>
                                </div>
                                @php $waitDays = (int) ($a->stage_entered_at?->diffInDays(now()) ?? 0); @endphp
                                <span class="text-xs font-medium text-rose-600 dark:text-rose-400 whitespace-nowrap">{{ $waitDays }}d in stage</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-app-layout>
