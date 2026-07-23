<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\SetupHealthService;
use Illuminate\View\View;

final class SetupHealthController extends Controller
{
    public function __construct(private readonly SetupHealthService $health) {}

    public function index(): View
    {
        return view('admin.setup-health.index', $this->health->overview());
    }
}
