<x-app-layout>
    <x-slot name="header">Admin dashboard</x-slot>
    <x-slot name="title">Dashboard</x-slot>

    <div class="p-6">

        <div class="mb-6">
            <h2 class="text-lg font-medium text-gray-100">Welcome back, {{ auth()->user()->name }}</h2>
            <p class="text-sm text-gray-500 mt-1">Pipeline at a glance.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <x-stat-card label="Total active" :value="$stats['total_active']" color="indigo"
                icon='<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>' />
            <x-stat-card label="Due this week" :value="$stats['due_this_week']" :color="$stats['due_this_week'] > 0 ? 'amber' : 'gray'"
                icon='<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' />
            <x-stat-card label="Stuck > 3 days" :value="$stats['stuck']" :color="$stats['stuck'] > 0 ? 'rose' : 'gray'"
                icon='<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>' />
            <x-stat-card label="Published this month" :value="$stats['published_this_month']" color="emerald"
                icon='<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' />
        </div>

        <!-- Pipeline grid -->
        <div class="card p-5 mb-6">
            <h3 class="text-sm font-medium text-gray-100 mb-4">Pipeline</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach($pipeline as $row)
                    @php
                        $color = $row['stage']->color();
                        $accentClass = match ($color) {
                            'gray'    => 'text-gray-400',
                            'blue'    => 'text-blue-300',
                            'indigo'  => 'text-indigo-300',
                            'amber'   => 'text-amber-300',
                            'pink'    => 'text-pink-300',
                            'orange'  => 'text-orange-300',
                            'emerald' => 'text-emerald-300',
                            'green'   => 'text-green-300',
                            default   => 'text-gray-400',
                        };
                        $borderClass = match ($color) {
                            'gray'    => 'hover:border-gray-500/40',
                            'blue'    => 'hover:border-blue-500/40',
                            'indigo'  => 'hover:border-indigo-500/40',
                            'amber'   => 'hover:border-amber-500/40',
                            'pink'    => 'hover:border-pink-500/40',
                            'orange'  => 'hover:border-orange-500/40',
                            'emerald' => 'hover:border-emerald-500/40',
                            'green'   => 'hover:border-green-500/40',
                            default   => 'hover:border-gray-500/40',
                        };
                    @endphp
                    <a href="{{ route('admin.articles.index', ['stage' => $row['stage']->value]) }}"
                       class="block p-3 bg-ink-800/40 border border-ink-700 {{ $borderClass }} rounded-lg transition-colors group">
                        <p class="text-xs text-gray-500 group-hover:text-gray-300 transition-colors">{{ $row['stage']->label() }}</p>
                        <p class="text-2xl font-semibold mt-1 {{ $row['count'] > 0 ? $accentClass : 'text-gray-600' }}">{{ $row['count'] }}</p>
                    </a>
                @endforeach
            </div>
        </div>

        @if($stuckArticles->isNotEmpty())
            <div class="bg-rose-500/10 border border-rose-500/30 rounded-lg p-4 mb-6">
                <div class="flex items-start gap-3">
                    <svg class="w-4 h-4 text-rose-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-rose-200">{{ $stuckArticles->count() }} article{{ $stuckArticles->count() > 1 ? 's' : '' }} stuck &gt; 3 days</p>
                        <ul class="mt-2 space-y-1">
                            @foreach($stuckArticles as $a)
                                <li class="text-xs">
                                    <a href="{{ route('admin.articles.index', ['q' => $a->article_code]) }}" class="text-rose-300 hover:underline">
                                        {{ $a->article_code }} · {{ $a->title }}
                                    </a>
                                    <span class="text-rose-400">— {{ $a->current_stage->label() }} for {{ $a->stage_entered_at->diffInDays(now()) }}d</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="px-5 py-3 border-b border-ink-700 flex items-center justify-between">
                <h3 class="text-sm font-medium text-gray-100">Recent activity</h3>
                <a href="{{ route('admin.activity.index') }}" class="text-xs text-indigo-400 hover:underline">See all</a>
            </div>
            @if($recentActivity->isEmpty())
                <div class="px-5 py-12 text-center">
                    <p class="text-sm text-gray-500">No activity yet.</p>
                </div>
            @else
                <ul class="divide-y divide-ink-700">
                    @foreach($recentActivity as $h)
                        <li class="px-5 py-3 flex items-center justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-mono text-gray-500">{{ $h->article->article_code }}</span>
                                    @if($h->from_stage)
                                        <x-stage-badge :stage="$h->from_stage" />
                                        <svg class="w-3 h-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                        </svg>
                                    @endif
                                    <x-stage-badge :stage="$h->to_stage" />
                                </div>
                                <p class="text-xs text-gray-500 mt-1 truncate">
                                    {{ $h->article->title }} — {{ $h->changedBy?->name ?? 'system' }}
                                </p>
                            </div>
                            <span class="text-xs text-gray-500 whitespace-nowrap">{{ $h->changed_at->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE, short: true) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-app-layout>
