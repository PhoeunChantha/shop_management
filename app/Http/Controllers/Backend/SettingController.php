<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class SettingController extends Controller
{
    public function __construct(
        private readonly SettingService $settings,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Setting::class);

        // Re-populate from old input on validation failure, else from saved value.
        $rows = old('social_links', $this->settings->socialLinks());

        if (empty($rows)) {
            $rows = [['icon' => '', 'title' => '', 'url' => '']];
        }

        $paymentRows = old('payment_methods', $this->settings->paymentMethods());

        return view('admin.settings.index', [
            'schema' => $this->settings->schema(),
            'values' => $this->settings->values(),
            'iconChoices' => $this->settings->iconChoices(),
            'socialRows' => array_values($rows),
            'paymentRows' => array_values($paymentRows),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $this->authorize('update', Setting::class);

        $this->settings->save($request->validated());

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Settings updated successfully!');
    }
}
