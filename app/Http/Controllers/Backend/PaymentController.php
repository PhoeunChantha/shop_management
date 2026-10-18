<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Admin\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as ResponseFactory;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);

        $filters = $this->validatedFilters($request);
        $perPage = (int) ($filters['per_page'] ?? 25);

        return view('admin.payments.index', [
            'payments' => $this->payments->paginate($filters, $perPage),
            'stats' => $this->payments->stats($filters),
            'filterOptions' => $this->payments->filterOptions(),
            'statuses' => PaymentService::STATUSES,
            'amountRanges' => PaymentService::amountRanges(),
            'perPage' => $perPage,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Payment::class);

        $filters = $this->validatedFilters($request);
        $filename = 'payments-'.now()->format('Y-m-d-His').'.csv';

        return ResponseFactory::streamDownload(function () use ($filters): void {
            $handle = fopen('php://output', 'w');
            $this->payments->writeCsv($filters, $handle);
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
            'status' => ['nullable', 'string', Rule::in(array_keys(PaymentService::STATUSES))],
            'gateway' => ['nullable', 'string', 'max:50'],
            'payment_option' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'max:10'],
            'amount' => ['nullable', 'string', Rule::in(array_keys(PaymentService::amountRanges()))],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50,100'],
        ]);
    }
}
