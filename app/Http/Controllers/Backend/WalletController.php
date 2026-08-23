<?php

namespace App\Http\Controllers\Backend;

use App\Exceptions\WalletException;
use App\Http\Controllers\Controller;
use App\Models\User;
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
        ]);
        $perPage = (int) ($filters['per_page'] ?? 25);

        $customers = User::role('customer')
            ->when(
                filled($filters['search'] ?? null),
                function ($q) use ($filters): void {
                    $term = trim((string) $filters['search']);
                    $q->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
                },
            )
            ->orderByDesc('wallet_balance')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.wallets.index', [
            'customers' => $customers,
            'totalBalance' => (float) User::role('customer')->sum('wallet_balance'),
            'perPage' => $perPage,
            'pendingTopups' => WalletTopup::with('user:id,name,email')
                ->where('status', 'pending')
                ->where('method_type', 'manual')
                ->latest()
                ->get(),
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

    public function adjust(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->can('edit wallets'), 403);

        $data = $request->validate([
            'direction' => ['required', 'in:credit,debit'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $amount = (float) $data['amount'];
            $note = ($data['note'] ?? null) ?: ($data['direction'] === 'credit' ? 'Admin credit' : 'Admin debit');

            $data['direction'] === 'credit'
                ? $this->wallet->credit($user, $amount, 'adjustment', $note)
                : $this->wallet->debit($user, $amount, 'adjustment', $note);
        } catch (WalletException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Wallet updated for '.$user->name.'.');
    }
}
