<x-app-layout>
    <x-slot name="header">Notification preferences</x-slot>
    <x-slot name="title">Notification preferences</x-slot>

    <div class="p-6 max-w-2xl">

        <a href="{{ route('notifications.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 mb-3 transition-colors">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to notifications
        </a>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">Notification preferences</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Choose how you want to be notified about workflow events.</p>
            </div>

            <form method="POST" action="{{ route('notifications.preferences.save') }}" class="px-6 py-5 space-y-4">
                @csrf

                <div class="flex items-center justify-between gap-4 py-2">
                    <div>
                        <p class="text-sm text-gray-900 dark:text-gray-100">In-app notifications</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Always on. The bell icon shows unread items.</p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">Always on</span>
                </div>

                <div class="flex items-center justify-between gap-4 py-2 border-t border-gray-100 dark:border-gray-800">
                    <div>
                        <p class="text-sm text-gray-900 dark:text-gray-100">Email notifications</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Send to {{ $user->email ?: 'no email set — add one in your profile' }}
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="email_notifications_enabled" value="0">
                        <input type="checkbox" name="email_notifications_enabled" value="1"
                               @checked($user->email_notifications_enabled)
                               class="sr-only peer"/>
                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                    </label>
                </div>

                <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">You'll receive emails when:</p>
                    <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1.5 ml-4 list-disc">
                        <li>You're assigned a new article (writers)</li>
                        <li>An article is ready for your review (tech leads)</li>
                        <li>Your rewrite needs revisions (writers)</li>
                        <li>Your article is ready for the client (sales)</li>
                        <li>An article you're working on has an approaching deadline</li>
                        <li>(Admins) An article is stuck or needs assignment</li>
                    </ul>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Save preferences
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
