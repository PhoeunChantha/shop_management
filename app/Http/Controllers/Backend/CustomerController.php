<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as ResponseFactory;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerService $customers,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'spend' => ['nullable', 'in:all,new,repeat,vip'],
            'tag_id' => ['nullable', 'integer', 'exists:customer_tags,id'],
            'sort' => ['nullable', 'in:last_order,lifetime_spend,orders_count,customer_name'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 25);
        $customers = $this->customers->paginate($filters, $perPage);

        return view('admin.customers.index', [
            'customers' => $customers,
            'perPage' => $perPage,
            'stats' => $this->customers->stats(),
            'tags' => $this->customers->tags(),
        ]);
    }

    public function show(string $email): View
    {
        $this->authorize('viewAny', Order::class);

        $email = rawurldecode($email);
        $emailKey = mb_strtolower($email);

        return view('admin.customers.show', [
            'profile' => $this->customers->profile($emailKey),
            'crmProfile' => $this->customers->crmProfile($emailKey),
            'orders' => $this->customers->orders($emailKey),
            'topProducts' => $this->customers->topProducts($emailKey),
            'tags' => $this->customers->tags(),
        ]);
    }

    public function updateCrm(Request $request, string $email): RedirectResponse
    {
        $this->authorize('viewAny', Order::class);

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:customer_tags,id'],
        ]);

        $this->customers->updateCrm(mb_strtolower(rawurldecode($email)), $data);

        return back()->with('success', 'Customer CRM details updated.');
    }

    public function bulkExport(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Order::class);

        $data = $request->validate([
            'emails' => ['required', 'array', 'min:1'],
            'emails.*' => ['required', 'email', 'max:255'],
        ]);

        $emailKeys = $this->customers->normalizeEmails($data['emails']);

        $filename = 'selected-customers-'.now()->format('Y-m-d-His').'.csv';

        return ResponseFactory::streamDownload(function () use ($emailKeys): void {
            $handle = fopen('php://output', 'w');
            $this->customers->writeCsv($emailKeys, $handle);

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function bulkStatus(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Order::class);

        $data = $request->validate([
            'emails' => ['required', 'array', 'min:1'],
            'emails.*' => ['required', 'email', 'max:255'],
            'status' => ['required', 'boolean'],
        ]);

        $status = (bool) $data['status'];
        $count = $this->customers->setStatus($data['emails'], $status);

        return back()->with('success', $count.' customer(s) '.($status ? 'enabled' : 'disabled').'.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Order::class);

        $data = $request->validate([
            'emails' => ['required', 'array', 'min:1'],
            'emails.*' => ['required', 'email', 'max:255'],
        ]);

        $count = $this->customers->delete($data['emails']);

        return back()->with('success', $count.' customer(s) removed from the admin customer list.');
    }
}
