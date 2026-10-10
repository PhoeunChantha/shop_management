<?php

namespace App\Http\Controllers\Backend;

use App\Exceptions\WalletException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\AdjustWalletRequest;
use App\Models\WalletTopup;
use App\Services\Admin\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $wallet,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->can('view wallets'), 403);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
            'tx_search' => ['nullable', 'string', 'max:255'],
            'tx_per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
            'tx_type' => ['nullable', 'string', 'in:'.implode(',', array_keys(WalletService::TYPES))],
        ]);
        $perPage = (int) ($filters['per_page'] ?? 25);

        return view('admin.wallets.index', [
            'customers' => $this->wallet->customers($filters, $perPage),
            'customerOptions' => $this->wallet->customerOptions(),
            'totalBalance' => $this->wallet->totalCustomerBalance(),
            'perPage' => $perPage,
            'pendingCount' => $this->wallet->pendingTopupCount(),
            'transactions' => $this->wallet->transactions($filters, (int) ($filters['tx_per_page'] ?? 10)),
            'txPerPage' => (int) ($filters['tx_per_page'] ?? 10),
            'txTypes' => WalletService::TYPES,
        ]);
    }

    /**
     * Manual top-up requests: pending (approve / reject) and reviewed history.
     */
    public function topups(Request $request): View
    {
        abort_unless($request->user()->can('view wallets'), 403);

        $tab = $request->validate(['tab' => ['nullable', 'in:pending,reviewed']])['tab'] ?? 'pending';

        return view('admin.wallets.topups', [
            'tab' => $tab,
            'pendingTopups' => $this->wallet->pendingTopups(),
            'reviewedTopups' => $tab === 'reviewed' ? $this->wallet->reviewedTopups() : null,
        ]);
    }

    public function approveTopup(Request $request, WalletTopup $topup): RedirectResponse
    {
        abort_unless($request->user()->can('edit wallets'), 403);

        DB::transaction(function () use ($request, $topup): void {
            $locked = WalletTopup::whereKey($topup->id)->lockForUpdate()->first();

            // Only a still-pending manual request can be approved (idempotent).
            if (! $locked || $locked->status !== 'pending' || $locked->method_type !== 'manual') {
                return;
            }

            $this->wallet->credit(
                $locked->user,
                (float) $locked->amount,
                'topup',
                'Manual top-up '.$locked->tran_id.' (approved)',
            );

            $locked->update([
                'status' => 'completed',
                'approved_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
        });

        return back()->with('success', __('Top-up approved and balance credited.'));
    }

    public function rejectTopup(Request $request, WalletTopup $topup): RedirectResponse
    {
        abort_unless($request->user()->can('edit wallets'), 403);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($topup->status === 'pending' && $topup->method_type === 'manual') {
            $topup->update([
                'status' => 'failed',
                'admin_note' => $data['note'] ?? null,
                'approved_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
        }

        return back()->with('success', __('Top-up request rejected.'));
    }

    public function adjust(AdjustWalletRequest $request): RedirectResponse
    {
        abort_unless($request->user()->can('edit wallets'), 403);

        $data = $request->validated();
        $customer = $request->customer();

        try {
            $balance = $this->wallet->adjust($customer, $data['direction'], (float) $data['amount'], $data['note'] ?? null);
        } catch (WalletException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', __(':name\'s wallet is now :balance.', [
            'name' => $customer->name,
            'balance' => '$'.number_format($balance, 2),
        ]));
    }
}
