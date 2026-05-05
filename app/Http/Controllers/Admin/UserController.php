<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim($request->get('q'));
                $q->where(function ($w) use ($term) {
                    $w->where('username', 'like', "%{$term}%")
                      ->orWhere('name', 'like', "%{$term}%")
                      ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->get('role')))
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('is_active', $request->get('status') === 'active');
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        User::create($data);

        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot delete your own account.');
        }

        // Free username/email so admins can recreate accounts with the same identifiers.
        // The unique indexes on `users` would otherwise block reuse since soft-deleted
        // rows keep their original values.
        $stamp = now()->format('YmdHis');
        $user->update([
            'username'  => "deleted_{$stamp}_{$user->username}",
            'email'     => $user->email ? "deleted_{$stamp}_{$user->email}" : null,
            'is_active' => false,
        ]);

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }
}
