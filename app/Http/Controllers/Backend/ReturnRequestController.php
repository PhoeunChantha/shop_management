<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReturnRequest\StoreReturnRequestRequest;
use App\Http\Requests\ReturnRequest\UpdateReturnRequestRequest;
use App\Models\ReturnRequest;
use App\Services\ReturnRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReturnRequestController extends Controller
{
    public function __construct(
        private readonly ReturnRequestService $returns,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ReturnRequest::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_keys(ReturnRequest::STATUSES))],
            'refund_status' => ['nullable', Rule::in(array_keys(ReturnRequest::REFUND_STATUSES))],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);

        return view('admin.returns.index', [
            'returns' => $this->returns->paginate($filters, $perPage),
            'perPage' => $perPage,
            'stats' => $this->returns->stats(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', ReturnRequest::class);

        $order = $request->integer('order_id')
            ? $this->returns->orderForCreate($request->integer('order_id'))
            : null;

        return view('admin.returns.create', [
            'orders' => $this->returns->orderOptions(),
            'order' => $order,
        ]);
    }

    public function store(StoreReturnRequestRequest $request): RedirectResponse
    {
        $this->authorize('create', ReturnRequest::class);

        $return = $this->returns->create($request->validated());

        return to_route('admin.returns.show', $return)->with('success', 'Return request created.');
    }

    public function show(ReturnRequest $return): View
    {
        $this->authorize('view', $return);

        return view('admin.returns.show', [
            'return' => $this->returns->findForShow($return),
        ]);
    }

    public function update(UpdateReturnRequestRequest $request, ReturnRequest $return): RedirectResponse
    {
        $this->authorize('update', $return);

        $this->returns->update($return, $request->validated());

        return back()->with('success', 'Return request updated.');
    }
}
