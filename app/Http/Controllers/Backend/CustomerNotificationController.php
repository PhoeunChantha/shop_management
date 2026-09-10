<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CustomerNotificationCampaign;
use App\Services\Admin\CustomerNotificationService;
use App\Services\Admin\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerNotificationController extends Controller
{
    public function __construct(
        private readonly CustomerNotificationService $notifications,
        private readonly CustomerService $customers,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', CustomerNotificationCampaign::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 25);

        return view('admin.customer-notifications.index', [
            'campaigns' => $this->notifications->paginate($filters, $perPage),
            'perPage' => $perPage,
            'stats' => $this->notifications->stats(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', CustomerNotificationCampaign::class);

        return view('admin.customer-notifications.create', [
            'tags' => $this->customers->tags(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CustomerNotificationCampaign::class);

        $data = $this->validatedComposeData($request);

        $campaign = $this->notifications->send($data, $request->user()?->id);

        return to_route('admin.customer-notifications.index')
            ->with('success', __('Notification sent to :count customer(s).', ['count' => $campaign->recipient_count]));
    }

    /**
     * Live recipient-count preview as the admin adjusts the audience filters.
     */
    public function previewCount(Request $request): JsonResponse
    {
        $this->authorize('create', CustomerNotificationCampaign::class);

        $data = $request->validate([
            'audience_type' => ['required', 'in:all,segment'],
            'search' => ['nullable', 'string', 'max:255'],
            'tag_id' => ['nullable', 'integer', 'exists:customer_tags,id'],
            'spend' => ['nullable', 'in:all,new,repeat,vip'],
        ]);

        return response()->json(['count' => count($this->notifications->targetEmails($data))]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedComposeData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
            'url' => ['nullable', 'string', 'max:255'],
            'audience_type' => ['required', 'in:all,segment'],
            'search' => ['nullable', 'string', 'max:255'],
            'tag_id' => ['nullable', 'integer', 'exists:customer_tags,id'],
            'spend' => ['nullable', 'in:all,new,repeat,vip'],
        ]);
    }
}
