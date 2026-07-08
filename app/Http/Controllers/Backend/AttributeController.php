<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesBulkActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attribute\StoreAttributeRequest;
use App\Http\Requests\Attribute\UpdateAttributeRequest;
use App\Models\Attribute;
use App\Models\Color;
use App\Models\Size;
use App\Services\AttributeService;
use App\Services\BulkActionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AttributeController extends Controller
{
    use HandlesBulkActions;

    public function __construct(private readonly AttributeService $attributes) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Attribute::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);

        return view('admin.attributes.index', [
            'attributes' => $this->attributes->paginate($filters, $perPage),
            'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Attribute::class);

        return view('admin.attributes.create', $this->sourceData());
    }

    public function store(StoreAttributeRequest $request): RedirectResponse
    {
        $this->authorize('create', Attribute::class);

        try {
            $this->attributes->create($request->validated());

            return to_route('admin.attributes.index')
                ->with('success', 'Attribute created successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating attribute: '.$e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all(),
            ]);

            return back()->withInput()
                ->withErrors(['error' => 'An error occurred while creating the attribute.']);
        }
    }

    public function edit(string $id): View
    {
        $this->authorize('update', Attribute::class);

        $attribute = Attribute::with('values')->findOrFail($id);

        return view('admin.attributes.edit', array_merge($this->sourceData(), [
            'attribute' => $attribute,
        ]));
    }

    public function update(UpdateAttributeRequest $request, string $id): RedirectResponse
    {
        $this->authorize('update', Attribute::class);

        try {
            $attribute = Attribute::findOrFail($id);

            $this->attributes->update($attribute, $request->validated());

            return to_route('admin.attributes.index')
                ->with('success', 'Attribute updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating attribute: '.$e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all(),
                'attribute_id' => $id,
            ]);

            return back()->withInput()
                ->withErrors(['error' => 'An error occurred while updating the attribute.']);
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('delete', Attribute::class);

        try {
            Attribute::findOrFail($id)->delete(); // cascades values + pivot
        } catch (\Exception $e) {
            Log::error('Error deleting attribute: '.$e->getMessage(), [
                'exception' => $e,
                'attribute_id' => $id,
            ]);

            return back()->withErrors(['error' => 'An error occurred while deleting the attribute.']);
        }

        return to_route('admin.attributes.index')
            ->with('success', 'Attribute deleted successfully!');
    }

    public function bulkDestroy(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('delete', Attribute::class);

        $ids = $this->validatedIds($request);
        $result = $bulk->destroy(Attribute::class, $ids); // cascades values + pivot

        return back()->with($this->bulkFlash($result, 'attribute', 'in use'));
    }

    public function bulkStatus(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('update', Attribute::class);

        [$ids, $status] = $this->validatedStatus($request);
        $count = $bulk->setStatus(Attribute::class, $ids, $status);

        return back()->with('success', $count.' attribute(s) '.($status ? 'enabled' : 'disabled').'.');
    }

    /**
     * Source master lists for the size/color-typed attribute pickers.
     *
     * @return array<string, mixed>
     */
    private function sourceData(): array
    {
        return [
            'sizes' => Size::orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'code']),
            'colors' => Color::orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'hex_code']),
        ];
    }
}

