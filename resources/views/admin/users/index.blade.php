<x-app-layout>
    <x-slot name="header">Users</x-slot>
    <x-slot name="title">Users</x-slot>

    <div class="p-6">

        <!-- Header row -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Users</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Manage team members and their roles.</p>
            </div>
            <a href="{{ route('admin.users.create') }}"
               class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New user
            </a>
        </div>

        <!-- Filters -->
        <form method="GET" class="flex flex-wrap items-center gap-2 mb-4">
            <div class="relative flex-1 min-w-[200px]">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Search by username, name or email"
                    class="w-full pl-9 pr-3 py-1.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                />
            </div>

            <select name="role" class="px-3 py-1.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">All roles</option>
                <option value="admin" @selected(request('role') === 'admin')>Admin</option>
                <option value="sales" @selected(request('role') === 'sales')>Sales</option>
                <option value="tech_team" @selected(request('role') === 'tech_team')>Tech team</option>
            </select>

            <select name="status" class="px-3 py-1.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">All statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>

            <button type="submit" class="px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                Filter
            </button>
            @if(request()->hasAny(['q', 'role', 'status']))
                <a href="{{ route('admin.users.index') }}" class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">Clear</a>
            @endif
        </form>

        <!-- Table -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
            @if($users->count() === 0)
                <div class="p-12 text-center">
                    <svg class="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/>
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400">No users found.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">User</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Role</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</th>
                            <th class="text-left px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Last login</th>
                            <th class="text-right px-4 py-2.5 font-medium text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($users as $u)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-950/50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-7 h-7 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center flex-shrink-0">
                                            <span class="text-xs font-medium text-indigo-700 dark:text-indigo-300">
                                                {{ strtoupper(substr($u->name, 0, 1)) }}
                                            </span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $u->name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">@{{ $u->username }}{{ $u->email ? ' · ' . $u->email : '' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <x-role-badge :role="$u->role" />
                                </td>
                                <td class="px-4 py-3">
                                    <x-status-dot :active="$u->is_active" />
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $u->last_login_at ? $u->last_login_at->diffForHumans() : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.users.edit', $u) }}"
                                           class="px-2 py-1 text-xs text-gray-600 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 rounded transition-colors">
                                            Edit
                                        </a>
                                        @if($u->id !== auth()->id())
                                            <form method="POST" action="{{ route('admin.users.destroy', $u) }}"
                                                  onsubmit="return confirm('Delete {{ $u->name }}? This cannot be undone.');"
                                                  class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-2 py-1 text-xs text-gray-600 hover:text-rose-600 dark:text-gray-400 dark:hover:text-rose-400 rounded transition-colors">
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
                <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
