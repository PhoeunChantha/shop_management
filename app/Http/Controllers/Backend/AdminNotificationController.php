<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Services\AdminNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminNotificationController extends Controller
{
    public function __construct(
        private readonly AdminNotificationService $notifications,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AdminNotification::class);
        $this->notifications->refreshGenerated();

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(array_keys(AdminNotification::TYPES))],
            'priority' => ['nullable', Rule::in(array_keys(AdminNotification::PRIORITIES))],
            'state' => ['nullable', Rule::in(['read', 'unread'])],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);

        return view('admin.notifications.index', [
            'notifications' => $this->notifications->paginate($filters, $perPage),
            'stats' => $this->notifications->stats(),
            'perPage' => $perPage,
        ]);
    }

    public function markRead(AdminNotification $notification): RedirectResponse
    {
        $this->authorize('update', $notification);

        $this->notifications->markRead($notification);

        return back()->with('success', 'Notification marked as read.');
    }

    public function markUnread(AdminNotification $notification): RedirectResponse
    {
        $this->authorize('update', $notification);

        $this->notifications->markUnread($notification);

        return back()->with('success', 'Notification marked as unread.');
    }

    public function markAllRead(): RedirectResponse
    {
        $this->authorize('update', AdminNotification::class);

        $count = $this->notifications->markAllRead();

        return back()->with('success', "{$count} notification(s) marked as read.");
    }
}
