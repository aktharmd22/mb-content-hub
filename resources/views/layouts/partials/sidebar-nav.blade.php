@php
    $user = auth()->user();
    $role = $user->role;

    $icons = [
        'dashboard' => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h7v7H3V7zm11-4h7v4h-7V3zm0 8h7v4h-7v-4zM3 18h7v3H3v-3z"/></svg>',
        'articles'  => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
        'clients'   => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
        'users'     => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/></svg>',
        'analytics' => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
        'settings'  => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
    ];
@endphp

{{-- Dashboard --}}
@if($role === 'admin')
    <a href="{{ route('admin.dashboard') }}" title="Dashboard"
       class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <span class="flex-shrink-0">{!! $icons['dashboard'] !!}</span>
        <span x-show="sidebarOpen" class="truncate">Dashboard</span>
    </a>
    <a href="{{ route('admin.articles.index') }}" title="All articles"
       class="sidebar-link {{ request()->routeIs('admin.articles*') ? 'active' : '' }}">
        <span class="flex-shrink-0">{!! $icons['articles'] !!}</span>
        <span x-show="sidebarOpen" class="truncate">All articles</span>
    </a>
    <a href="{{ route('admin.assignments.index') }}" title="Assignment queue"
       class="sidebar-link {{ request()->routeIs('admin.assignments*') ? 'active' : '' }}">
        <span class="flex-shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </span>
        <span x-show="sidebarOpen" class="truncate">Assignments</span>
    </a>
    <a href="{{ route('admin.analytics.index') }}" title="Analytics"
       class="sidebar-link {{ request()->routeIs('admin.analytics*') ? 'active' : '' }}">
        <span class="flex-shrink-0">{!! $icons['analytics'] !!}</span>
        <span x-show="sidebarOpen" class="truncate">Analytics</span>
    </a>
    <a href="{{ route('admin.activity.index') }}" title="Activity log"
       class="sidebar-link {{ request()->routeIs('admin.activity*') ? 'active' : '' }}">
        <span class="flex-shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </span>
        <span x-show="sidebarOpen" class="truncate">Activity</span>
    </a>
    <a href="{{ route('admin.reports.index') }}" title="Reports & history"
       class="sidebar-link {{ request()->routeIs('admin.reports*') ? 'active' : '' }}">
        <span class="flex-shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 014-4h4M9 17h6M9 17H5a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-4M9 17l-4 4m6-4l-4 4"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 13l3-3 2 2 4-4"/>
            </svg>
        </span>
        <span x-show="sidebarOpen" class="truncate">Reports</span>
    </a>
    <a href="{{ route('admin.users.index') }}" title="Users"
       class="sidebar-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
        <span class="flex-shrink-0">{!! $icons['users'] !!}</span>
        <span x-show="sidebarOpen" class="truncate">Users</span>
    </a>
    <a href="{{ route('admin.viral-packages.index') }}" title="Viral package"
       class="sidebar-link {{ request()->routeIs('admin.viral-packages*') ? 'active' : '' }}">
        <span class="flex-shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </span>
        <span x-show="sidebarOpen" class="truncate">Viral package</span>
    </a>
    <a href="{{ route('admin.article-types.index') }}" title="Article types"
       class="sidebar-link {{ request()->routeIs('admin.article-types*') ? 'active' : '' }}">
        <span class="flex-shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
        </span>
        <span x-show="sidebarOpen" class="truncate">Article types</span>
    </a>
    <a href="{{ route('admin.settings.general') }}" title="Settings"
       class="sidebar-link {{ request()->routeIs('admin.settings*') ? 'active' : '' }}">
        <span class="flex-shrink-0">{!! $icons['settings'] !!}</span>
        <span x-show="sidebarOpen" class="truncate">Settings</span>
    </a>
@elseif($role === 'sales')
    <a href="{{ route('sales.dashboard') }}" title="Dashboard"
       class="sidebar-link {{ request()->routeIs('sales.dashboard') ? 'active' : '' }}">
        <span class="flex-shrink-0">{!! $icons['dashboard'] !!}</span>
        <span x-show="sidebarOpen" class="truncate">Dashboard</span>
    </a>
    <a href="{{ route('sales.articles.index') }}" title="Articles"
       class="sidebar-link {{ request()->routeIs('sales.articles*') ? 'active' : '' }}">
        <span class="flex-shrink-0">{!! $icons['articles'] !!}</span>
        <span x-show="sidebarOpen" class="truncate">Articles</span>
    </a>
    <a href="{{ route('sales.viral-packages.index') }}" title="Viral package"
       class="sidebar-link {{ request()->routeIs('sales.viral-packages*') ? 'active' : '' }}">
        <span class="flex-shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </span>
        <span x-show="sidebarOpen" class="truncate">Viral package</span>
    </a>
    <a href="{{ route('sales.clients.index') }}" title="Clients"
       class="sidebar-link {{ request()->routeIs('sales.clients*') ? 'active' : '' }}">
        <span class="flex-shrink-0">{!! $icons['clients'] !!}</span>
        <span x-show="sidebarOpen" class="truncate">Clients</span>
    </a>
@elseif($role === 'tech_team')
    <a href="{{ route('writer.dashboard') }}" title="Dashboard"
       class="sidebar-link {{ request()->routeIs('writer.dashboard') ? 'active' : '' }}">
        <span class="flex-shrink-0">{!! $icons['dashboard'] !!}</span>
        <span x-show="sidebarOpen" class="truncate">Dashboard</span>
    </a>
    <a href="{{ route('writer.articles.index') }}" title="Assignments"
       class="sidebar-link {{ request()->routeIs('writer.articles*') && ! request()->filled('stage') ? 'active' : '' }}">
        <span class="flex-shrink-0">{!! $icons['articles'] !!}</span>
        <span x-show="sidebarOpen" class="truncate">My assignments</span>
    </a>
    <a href="{{ route('writer.articles.index', ['stage' => 'revisions']) }}" title="Revisions"
       class="sidebar-link {{ request()->is('writer/articles*') && request('stage') === 'revisions' ? 'active' : '' }}">
        <span class="flex-shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </span>
        <span x-show="sidebarOpen" class="truncate">Revisions</span>
    </a>
    <a href="{{ route('lead.articles.index', ['stage' => 'inbox']) }}" title="New uploads"
       class="sidebar-link {{ request()->is('lead/articles*') && request('stage') === 'inbox' ? 'active' : '' }}">
        <span class="flex-shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </span>
        <span x-show="sidebarOpen" class="truncate">New uploads</span>
    </a>

    <div x-show="sidebarOpen" class="px-3 pt-3 pb-1 text-[10px] font-medium text-gray-500 uppercase tracking-wider">Review</div>
    <a href="{{ route('lead.articles.index') }}" title="With sales"
       class="sidebar-link {{ request()->routeIs('lead.articles*') ? 'active' : '' }}">
        <span class="flex-shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
        </span>
        <span x-show="sidebarOpen" class="truncate">With sales</span>
    </a>
    <a href="{{ route('lead.team.index') }}" title="Team"
       class="sidebar-link {{ request()->routeIs('lead.team*') ? 'active' : '' }}">
        <span class="flex-shrink-0">{!! $icons['analytics'] !!}</span>
        <span x-show="sidebarOpen" class="truncate">Team performance</span>
    </a>

    <div x-show="sidebarOpen" class="px-3 pt-3 pb-1 text-[10px] font-medium text-gray-500 uppercase tracking-wider">Viral</div>
    <a href="{{ route('writer.viral-packages.index') }}" title="Viral package"
       class="sidebar-link {{ request()->routeIs('writer.viral-packages*') ? 'active' : '' }}">
        <span class="flex-shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </span>
        <span x-show="sidebarOpen" class="truncate">Viral package</span>
    </a>
@endif

{{-- Inbox (all roles) --}}
@php
    try {
        $inboxUnread = app(\App\Services\InboxService::class)->totalUnreadFor(auth()->user());
    } catch (\Throwable $e) {
        $inboxUnread = 0;
        report($e);
    }
@endphp
<a href="{{ route('inbox.index') }}" title="Inbox"
   class="sidebar-link {{ request()->routeIs('inbox*') ? 'active' : '' }} relative">
    <span class="flex-shrink-0">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
        </svg>
    </span>
    <span x-show="sidebarOpen" class="truncate flex-1">Inbox</span>
    @if($inboxUnread > 0)
        <span x-show="sidebarOpen" class="ml-auto inline-flex items-center justify-center px-1.5 min-w-[18px] h-[18px] text-[10px] font-semibold text-white bg-rose-500 rounded-full">{{ $inboxUnread > 9 ? '9+' : $inboxUnread }}</span>
        <span x-show="!sidebarOpen" class="absolute top-1 right-1 w-2 h-2 bg-rose-500 rounded-full"></span>
    @endif
</a>

{{-- Profile (all roles) --}}
<div class="pt-2 mt-2 border-t border-gray-100 dark:border-gray-800">
    <a href="{{ route('profile.edit') }}" title="Profile"
       class="sidebar-link {{ request()->routeIs('profile*') ? 'active' : '' }}">
        <span class="flex-shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </span>
        <span x-show="sidebarOpen" class="truncate">Profile</span>
    </a>
</div>
