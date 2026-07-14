<?php

namespace App\Http\Controllers\Backend;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviews) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Review::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);

        $reviews = Review::query()
            ->with(['product:id,name,thumbnail', 'user:id,name'])
            ->search(trim($filters['search'] ?? ''))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['rating'] ?? null, fn ($q, $v) => $q->where('rating', $v))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.reviews.index', [
            'reviews' => $reviews,
            'perPage' => $perPage,
            'counts' => Review::selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function moderate(Request $request, string $id): RedirectResponse
    {
        $this->authorize('update', Review::class);

        $data = $request->validate([
            'status' => ['required', 'in:pending,approved,rejected'],
        ]);

        try {
            $this->reviews->moderate(Review::findOrFail($id), ReviewStatus::from($data['status']));

            return back()->with('success', 'Review marked as '.$data['status'].'.');
        } catch (\Exception $e) {
            Log::error('Error moderating review: '.$e->getMessage(), ['exception' => $e, 'review_id' => $id]);

            return back()->with('error', 'An error occurred while updating the review.');
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('delete', Review::class);

        try {
            Review::findOrFail($id)->delete(); // observer recomputes the product aggregate
        } catch (\Exception $e) {
            Log::error('Error deleting review: '.$e->getMessage(), ['exception' => $e, 'review_id' => $id]);

            return back()->withErrors(['error' => 'An error occurred while deleting the review.']);
        }

        return back()->with('success', 'Review deleted successfully!');
    }

    public function bulkModerate(Request $request): RedirectResponse
    {
        $this->authorize('update', Review::class);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'status' => ['required', 'in:pending,approved,rejected'],
        ]);

        $productIds = Review::whereKey($data['ids'])->pluck('product_id')->unique();
        Review::whereKey($data['ids'])->update(['status' => $data['status']]);
        $productIds->each(fn ($pid) => $this->reviews->recompute((int) $pid));

        return back()->with('success', count($data['ids']).' review(s) marked as '.$data['status'].'.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $this->authorize('delete', Review::class);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $productIds = Review::whereKey($data['ids'])->pluck('product_id')->unique();
        Review::whereKey($data['ids'])->delete();
        $productIds->each(fn ($pid) => $this->reviews->recompute((int) $pid));

        return back()->with('success', count($data['ids']).' review(s) deleted.');
    }
}
