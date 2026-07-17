<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as ResponseFactory;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $perPage = (int) ($filters['per_page'] ?? 25);

        return view('admin.activity.index', [
            'events' => $this->activityLog->paginate($filters, $perPage),
            'stats' => $this->activityLog->stats(),
            'types' => $this->activityLog->types(),
            'actors' => $this->activityLog->actors(),
            'perPage' => $perPage,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $filename = 'activity-log-'.now()->format('Y-m-d-His').'.csv';

        return ResponseFactory::streamDownload(function () use ($filters): void {
            $handle = fopen('php://output', 'w');
            $this->activityLog->writeCsv($filters, $handle);

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:50'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);
    }
}
