<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AdminSavedView;
use App\Services\AdminSavedViewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSavedViewController extends Controller
{
    public function __construct(private readonly AdminSavedViewService $savedViews) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('view saved views'), 403);

        return view('admin.saved-views.index', [
            'groups' => $this->savedViews->grouped($request->user()?->id),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('create saved views'), 403);

        $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'scope' => ['required', 'string', 'max:80'],
            'route_name' => ['required', 'string', 'max:120'],
            'query_json' => ['nullable', 'json'],
            'icon' => ['nullable', 'string', 'max:80'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_global' => ['nullable', 'boolean'],
            'return_url' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->savedViews->create($request->all(), $request->user()?->id);

        return redirect($request->input('return_url') ?: route('admin.saved-views.index'))
            ->with('success', 'Saved view created.');
    }

    public function destroy(Request $request, AdminSavedView $savedView): RedirectResponse
    {
        abort_if($savedView->is_global && ! $request->user()?->can('delete saved views'), 403);
        abort_if(! $savedView->is_global && $savedView->user_id !== $request->user()?->id, 403);

        $savedView->delete();

        return back()->with('success', 'Saved view deleted.');
    }
}
