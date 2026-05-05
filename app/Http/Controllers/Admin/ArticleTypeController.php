<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreArticleTypeRequest;
use App\Models\ArticleType;
use App\Services\GoogleDriveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ArticleTypeController extends Controller
{
    public function __construct(private readonly GoogleDriveService $drive) {}

    public function index(): View
    {
        $types  = ArticleType::orderBy('sort_order')->orderBy('name')->withCount('articles')->get();
        $folders = $this->safeFolders();

        return view('admin.article-types.index', compact('types', 'folders'));
    }

    public function create(): View
    {
        $folders = $this->safeFolders();
        return view('admin.article-types.create', compact('folders'));
    }

    public function store(StoreArticleTypeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        ArticleType::create($data);

        return redirect()->route('admin.article-types.index')->with('success', 'Article type created.');
    }

    public function edit(ArticleType $articleType): View
    {
        $folders = $this->safeFolders();
        return view('admin.article-types.edit', ['type' => $articleType, 'folders' => $folders]);
    }

    public function update(StoreArticleTypeRequest $request, ArticleType $articleType): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $articleType->update($data);

        return redirect()->route('admin.article-types.index')->with('success', 'Article type updated.');
    }

    public function destroy(ArticleType $articleType): RedirectResponse
    {
        if ($articleType->articles()->exists()) {
            return back()->with('error', "Can't delete: {$articleType->name} has articles attached. Deactivate it instead.");
        }

        $articleType->delete();

        return back()->with('success', 'Article type deleted.');
    }

    /** @return array<int,array{id:string,name:string,parents:array<string>}> */
    private function safeFolders(): array
    {
        if (! $this->drive->isConfigured()) {
            return [];
        }
        try {
            return $this->drive->listFolders();
        } catch (\Throwable) {
            return [];
        }
    }
}
