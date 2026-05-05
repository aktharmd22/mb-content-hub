<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreClientRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $clients = Client::withCount('articles')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->get('q'));
                $q->where(function ($w) use ($term) {
                    $w->where('name', 'like', "%{$term}%")
                      ->orWhere('company', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('sales.clients.index', compact('clients'));
    }

    public function create(): View
    {
        return view('sales.clients.create');
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        Client::create($request->validated() + ['created_by' => auth()->id()]);

        return redirect()->route('sales.clients.index')->with('success', 'Client added.');
    }

    /**
     * Inline create from the article submission form. Returns JSON.
     */
    public function quickCreate(StoreClientRequest $request): JsonResponse
    {
        $client = Client::create($request->validated() + ['created_by' => auth()->id()]);

        return response()->json([
            'id'      => $client->id,
            'name'    => $client->name,
            'company' => $client->company,
        ]);
    }

    public function edit(Client $client): View
    {
        return view('sales.clients.edit', compact('client'));
    }

    public function update(StoreClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        return redirect()->route('sales.clients.index')->with('success', 'Client updated.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        if ($client->articles()->exists()) {
            return back()->with('error', "Cannot delete: {$client->name} has articles attached.");
        }

        $client->delete();

        return back()->with('success', 'Client deleted.');
    }
}
