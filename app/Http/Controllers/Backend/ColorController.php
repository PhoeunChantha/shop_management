<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Color\StoreColorRequest;
use App\Http\Requests\Color\UpdateColorRequest;
use App\Models\Color;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ColorController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Color::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = trim($filters['search'] ?? '');

        $colors = Color::query()
            ->search($search)
            ->orderBy('sort_order', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.colors.index', [
            'colors' => $colors,
            'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Color::class);

        return view('admin.colors.create');
    }

    public function store(StoreColorRequest $request): RedirectResponse
    {
        $this->authorize('create', Color::class);

        try {
            $validated = $request->validated();
            $validated['hex_code'] = $validated['code'];
            $validated['sort_order'] ??= 0;

            Color::create($validated);

            return to_route('admin.colors.index')
                ->with('success', 'Color created successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating color: '.$e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while creating the color.']);
        }
    }

    public function edit(string $id): View
    {
        $this->authorize('update', Color::class);

        $color = Color::findOrFail($id);

        return view('admin.colors.edit', [
            'color' => $color,
        ]);
    }

    public function update(UpdateColorRequest $request, string $id): RedirectResponse
    {
        $this->authorize('update', Color::class);

        try {
            $color = Color::findOrFail($id);

            $validated = $request->validated();
            $validated['hex_code'] = $validated['code'];
            $validated['sort_order'] ??= 0;

            $color->update($validated);

            return to_route('admin.colors.index')
                ->with('success', 'Color updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating color: '.$e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all(),
                'color_id' => $id,
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while updating the color.']);
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('delete', Color::class);

        try {
            $color = Color::findOrFail($id);
            $color->delete();
        } catch (\Exception $e) {
            Log::error('Error deleting color: '.$e->getMessage(), [
                'exception' => $e,
                'color_id' => $id,
            ]);

            return back()
                ->withErrors(['error' => 'An error occurred while deleting the color.']);
        }

        return to_route('admin.colors.index')
            ->with('success', 'Color deleted successfully!');
    }
}
