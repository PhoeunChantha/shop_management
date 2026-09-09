<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->can('view dashboard'), 403);

        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        return view('dashboard', $this->dashboard->overview(
            $validated['date_from'] ?? null,
            $validated['date_to'] ?? null,
        ));
    }
}
