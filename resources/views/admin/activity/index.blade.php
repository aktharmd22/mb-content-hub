<x-app-layout>
    <x-slot name="header">Activity log</x-slot>
    <x-slot name="title">Activity</x-slot>

    <div class="p-6">

        <div class="mb-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Activity log</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Audit trail of every workflow transition.</p>
        </div>

        <form method="GET" class="flex flex-wrap items-center gap-2 mb-4">
            <select name="user_id" class="px-3 py-1.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All users</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }} ({{ $u->role }})</option>
                @endforeach
            </select>
            <select name="stage" class="px-3 py-1.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All transitions</option>
                @foreach($stages as $s)
                    <option value="{{ $s->value }}" @selected(request('stage') === $s->value)>→ {{ $s->label() }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}"
                   class="px-3 py-1.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-gray-900 dark:text-gray-100"/>
            <input type="date" name="to" value="{{ request('to') }}"
                   class="px-3 py-1.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-gray-900 dark:text-gray-100"/>
            <button type="submit" class="px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">Filter</button>
            @if(request()->hasAny(['user_id', 'stage', 'from', 'to']))
                <a href="{{ route('admin.activity.index') }}" class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">Clear</a>
            @endif
        </form>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
            @if($entries->count() === 0)
                <div class="p-12 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">No activity matches your filters.</p>
                </div>
            @else
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($entries as $h)
                        <li class="px-5 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap mb-1">
                                        <span class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $h->article->article_code }}</span>
                                        @if($h->from_stage)
                                            <x-stage-badge :stage="$h->from_stage" />
                                            <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                            </svg>
                                        @endif
                                        <x-stage-badge :stage="$h->to_stage" />
                                    </div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ $h->article->title }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $h->changedBy?->name ?? 'system' }}
                                        @if($h->changedBy?->role)<span class="text-gray-400">({{ $h->changedBy->role }})</span>@endif
                                    </p>
                                    @if($h->notes)
                                        <p class="text-xs text-gray-700 dark:text-gray-300 mt-1 italic">"{{ $h->notes }}"</p>
                                    @endif
                                </div>
                                <span class="text-xs text-gray-400 whitespace-nowrap">{{ $h->changed_at->format('M j, H:i') }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800">
                    {{ $entries->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
