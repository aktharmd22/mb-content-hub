<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $clients = Client::query()
            ->with('creator:id,name')
            ->withCount(['articles', 'viralPackages'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->get('q'));
                $q->where(function ($w) use ($term) {
                    $w->where('name', 'like', "%{$term}%")
                      ->orWhere('company', 'like', "%{$term}%")
                      ->orWhere('contact_email', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $totals = [
            'clients'  => Client::count(),
            'articles' => \App\Models\Article::count(),
            'packages' => \App\Models\ViralPackage::count(),
        ];

        return view('admin.clients.index', compact('clients', 'totals'));
    }
}
