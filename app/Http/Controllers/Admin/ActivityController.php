<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('activity.view'), 403);

        return view('admin.activity', [
            'entries' => ActivityLog::with('user')
                ->when($request->filled('user'), fn ($q) => $q->where('user_id', $request->input('user')))
                ->when($request->filled('q'), function ($q) use ($request) {
                    $like = '%'.$request->input('q').'%';
                    $q->where(fn ($w) => $w->where('action', 'like', $like)->orWhere('detail', 'like', $like));
                })
                ->latestFirst()
                ->paginate(60)
                ->withQueryString(),
            'people' => User::staff()->orderBy('name')->get(),
            'filters' => $request->only(['user', 'q']),
        ]);
    }
}
