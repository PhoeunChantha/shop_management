<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\StreamsReportCsv;
use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Services\Admin\ReturnReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class ReturnReportController extends Controller
{
    use StreamsReportCsv;

    public function __construct(
        private readonly ReturnReportService $reports,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);

        return view('admin.reports.returns', array_merge($this->reports->report($filters), [
            'returnStatuses' => ReturnRequest::STATUSES,
        ]));
    }

    public function export(Request $request): Response
    {
        return $this->streamExport(
            $this->reports->exportRows($this->validatedFilters($request)),
            'Return Report',
            'return-report',
            (string) $request->query('format', 'csv'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(array_keys(ReturnRequest::STATUSES))],
        ]);
    }
}
