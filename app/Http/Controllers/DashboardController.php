<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function index(): RedirectResponse
    {
        $user = auth()->user();

        return match ($user->role) {
            'admin'     => redirect()->route('admin.dashboard'),
            'sales'     => redirect()->route('sales.dashboard'),
            'tech_team' => redirect()->route('writer.dashboard'),
            default     => redirect()->route('profile.edit'),
        };
    }
}
