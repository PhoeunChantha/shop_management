<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Services\Admin\NewsletterSubscriberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as ResponseFactory;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterSubscriberController extends Controller
{
    public function __construct(
        private readonly NewsletterSubscriberService $subscribers,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', NewsletterSubscriber::class);

        $filters = $this->validatedFilters($request);
        $perPage = (int) ($filters['per_page'] ?? 25);

        return view('admin.subscribers.index', [
            'subscribers' => $this->subscribers->paginate($filters, $perPage),
            'stats' => $this->subscribers->stats(),
            'perPage' => $perPage,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', NewsletterSubscriber::class);

        $filters = $this->validatedFilters($request);
        $filename = 'newsletter-subscribers-'.now()->format('Y-m-d-His').'.csv';

        return ResponseFactory::streamDownload(function () use ($filters): void {
            $handle = fopen('php://output', 'w');
            $this->subscribers->writeCsv($filters, $handle);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function destroy(NewsletterSubscriber $subscriber): RedirectResponse
    {
        $this->authorize('delete', NewsletterSubscriber::class);

        $subscriber->delete();

        return back()->with('success', __('Subscriber removed.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);
    }
}
