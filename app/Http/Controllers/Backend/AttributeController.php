<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attribute\StoreAttributeRequest;
use App\Http\Requests\Attribute\UpdateAttributeRequest;
use App\Models\Attribute;
use App\Services\AttributeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AttributeController extends Controller
{
    public function __construct(private readonly AttributeService $attributes) {}

    public function index(Request $request): View
    {
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
        return view('admin.attributes.create');
    }

    public function store(StoreAttributeRequest $request): RedirectResponse
    {
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
        $attribute = Attribute::with('values')->findOrFail($id);

        return view('admin.attributes.edit', [
            'attribute' => $attribute,
        ]);
    }

    public function update(UpdateAttributeRequest $request, string $id): RedirectResponse
    {
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
}
