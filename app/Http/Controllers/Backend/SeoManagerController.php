<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\SeoManagerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as ResponseFactory;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SeoManagerController extends Controller
{
    public function __construct(
        private readonly SeoManagerService $seo,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermissionTo('view seo'), 403);

        $filters = $this->validatedFilters($request);
        $perPage = (int) ($filters['per_page'] ?? 25);

        return view('admin.seo.index', [
            'items' => $this->seo->paginate($filters, $perPage),
            'stats' => $this->seo->stats(),
            'types' => SeoManagerService::TYPES,
            'issues' => SeoManagerService::ISSUES,
            'perPage' => $perPage,
        ]);
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('edit seo'), 403);
        abort_unless(array_key_exists($type, SeoManagerService::TYPES), 404);

        $data = $request->validate([
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ]);

        $this->seo->update($type, $id, $data);

        return back()->with('success', 'SEO metadata updated.');
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->hasPermissionTo('view seo'), 403);

        $filters = $this->validatedFilters($request);
        $filename = 'seo-audit-'.now()->format('Y-m-d-His').'.csv';

        return ResponseFactory::streamDownload(function () use ($filters): void {
            $handle = fopen('php://output', 'w');
            $this->seo->writeCsv($filters, $handle);
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
            'type' => ['nullable', Rule::in(array_keys(SeoManagerService::TYPES))],
            'issue' => ['nullable', Rule::in(array_keys(SeoManagerService::ISSUES))],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);
    }
}
