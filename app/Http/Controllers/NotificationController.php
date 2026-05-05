<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $notifications = auth()->user()
            ->notifications()
            ->paginate(30);

        return view('notifications.index', compact('notifications'));
    }

    public function dropdown(): JsonResponse
    {
        $user = auth()->user();

        $unread = $user->unreadNotifications->take(8)->map(fn ($n) => [
            'id'         => $n->id,
            'message'    => $n->data['message'] ?? 'Notification',
            'url'        => $n->data['url'] ?? '#',
            'created_at' => $n->created_at->diffForHumans(),
            'type'       => $n->data['type'] ?? 'generic',
        ]);

        return response()->json([
            'unread_count' => $user->unreadNotifications->count(),
            'items'        => $unread,
        ]);
    }

    public function markRead(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $notification = auth()->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->to($notification->data['url'] ?? route('notifications.index'));
    }

    public function markAllRead(): RedirectResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }

    public function preferences(): View
    {
        return view('notifications.preferences', ['user' => auth()->user()]);
    }

    public function savePreferences(Request $request): RedirectResponse
    {
        $request->validate([
            'email_notifications_enabled' => ['nullable', 'boolean'],
        ]);

        auth()->user()->update([
            'email_notifications_enabled' => $request->boolean('email_notifications_enabled'),
        ]);

        return redirect()->route('notifications.preferences')->with('success', 'Preferences saved.');
    }
}
