<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesBulkActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Announcement\StoreAnnouncementRequest;
use App\Http\Requests\Announcement\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Services\BulkActionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    use HandlesBulkActions;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Announcement::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = trim($filters['search'] ?? '');

        $announcements = Announcement::query()
            ->search($search)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.announcements.index', [
            'announcements' => $announcements,
            'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Announcement::class);

        return view('admin.announcements.create');
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $this->authorize('create', Announcement::class);

        try {
            Announcement::create($request->validated());

            return to_route('admin.announcements.index')->with('success', 'Announcement created successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating announcement: '.$e->getMessage(), ['exception' => $e, 'request_data' => $request->all()]);

            return back()->withInput()->withErrors(['error' => 'An error occurred while creating the announcement.']);
        }
    }

    public function edit(string $id): View
    {
        $this->authorize('update', Announcement::class);

        return view('admin.announcements.edit', ['announcement' => Announcement::findOrFail($id)]);
    }

    public function update(UpdateAnnouncementRequest $request, string $id): RedirectResponse
    {
        $this->authorize('update', Announcement::class);

        try {
            Announcement::findOrFail($id)->update($request->validated());

            return to_route('admin.announcements.index')->with('success', 'Announcement updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating announcement: '.$e->getMessage(), ['exception' => $e, 'request_data' => $request->all(), 'announcement_id' => $id]);

            return back()->withInput()->withErrors(['error' => 'An error occurred while updating the announcement.']);
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('delete', Announcement::class);

        try {
            Announcement::findOrFail($id)->delete();
        } catch (\Exception $e) {
            Log::error('Error deleting announcement: '.$e->getMessage(), ['exception' => $e, 'announcement_id' => $id]);

            return back()->withErrors(['error' => 'An error occurred while deleting the announcement.']);
        }

        return to_route('admin.announcements.index')->with('success', 'Announcement deleted successfully!');
    }

    public function bulkDestroy(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('delete', Announcement::class);

        $result = $bulk->destroy(Announcement::class, $this->validatedIds($request));

        return back()->with($this->bulkFlash($result, 'announcement', 'in use'));
    }

    public function bulkStatus(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('update', Announcement::class);

        [$ids, $status] = $this->validatedStatus($request);
        $count = $bulk->setStatus(Announcement::class, $ids, $status);

        return back()->with('success', $count.' announcement(s) '.($status ? 'enabled' : 'disabled').'.');
    }
}
