<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ArticleStage;
use App\Http\Controllers\Controller;
use App\Models\StageHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $entries = StageHistory::with(['article:id,article_code,title', 'changedBy:id,name,role'])
            ->whereHas('article')
            ->when($request->filled('user_id'), fn ($q) => $q->where('changed_by', $request->get('user_id')))
            ->when($request->filled('stage'),   fn ($q) => $q->where('to_stage', $request->get('stage')))
            ->when($request->filled('from'),    fn ($q) => $q->where('changed_at', '>=', $request->get('from')))
            ->when($request->filled('to'),      fn ($q) => $q->where('changed_at', '<=', $request->get('to') . ' 23:59:59'))
            ->orderByDesc('changed_at')
            ->paginate(50)
            ->withQueryString();

        $users  = User::orderBy('name')->get(['id', 'name', 'role']);
        $stages = ArticleStage::cases();

        return view('admin.activity.index', compact('entries', 'users', 'stages'));
    }
}
