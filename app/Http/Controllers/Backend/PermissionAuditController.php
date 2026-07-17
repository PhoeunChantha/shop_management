<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\PermissionAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PermissionAuditController extends Controller
{
    public function __construct(
        private readonly PermissionAuditService $permissionAudit,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('view permission');

        $filters = $request->validate([
            'role_a' => ['nullable', 'integer', 'exists:roles,id'],
            'role_b' => ['nullable', 'integer', 'exists:roles,id'],
        ]);

        return view('admin.permission-audit.index', $this->permissionAudit->overview($filters));
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('view permission');

        $filters = $request->validate([
            'role_a' => ['nullable', 'integer', 'exists:roles,id'],
            'role_b' => ['nullable', 'integer', 'exists:roles,id'],
        ]);

        $rows = $this->permissionAudit->exportRows($filters);

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 'permission-audit-'.now()->format('Y-m-d-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
