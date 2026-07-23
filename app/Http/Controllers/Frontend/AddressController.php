<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Services\AddressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function __construct(
        private readonly AddressService $addresses,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $this->addresses->create($request->user(), $this->validated($request));

        return back()->with('success', 'Address added.');
    }

    public function update(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeOwner($request, $address);
        $this->addresses->update($address, $this->validated($request));

        return back()->with('success', 'Address updated.');
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeOwner($request, $address);
        $this->addresses->delete($address);

        return back()->with('success', 'Address removed.');
    }

    public function makeDefault(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeOwner($request, $address);
        $this->addresses->makeDefault($request->user(), $address);

        return back()->with('success', 'Default address updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'label' => ['nullable', 'string', 'max:60'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'street' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'zip' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:120'],
            'is_default' => ['nullable', 'boolean'],
        ]);
    }

    private function authorizeOwner(Request $request, Address $address): void
    {
        abort_unless($address->user_id === $request->user()->id, 403);
    }
}
