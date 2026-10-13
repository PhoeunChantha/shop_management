<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Exceptions\CutLuyException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Currency;
use App\Models\Setting;
use App\Services\Admin\MediaStorageService;
use App\Services\Admin\SettingService;
use App\Services\Frontend\CutLuyService;
use Illuminate\Http\JsonResponse;
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

        // Footer menu rows — saved config, or the default columns as a starting point.
        $footerRows = old('footer_links', $this->settings->footerLinks() ?: $this->settings->defaultFooterLinks());

        if (empty($footerRows)) {
            $footerRows = [['column' => '', 'label' => '', 'url' => '']];
        }

        // Extra display currencies (Currencies tab), in display order.
        $currencyRows = old('currencies', Currency::query()->orderBy('sort_order')->orderBy('code')->get()
            ->map(fn (Currency $c): array => [
                'code' => $c->code,
                'name' => $c->name,
                'symbol' => $c->symbol,
                'symbol_position' => $c->symbol_position,
                'exchange_rate' => rtrim(rtrim(number_format((float) $c->exchange_rate, 6, '.', ''), '0'), '.'),
                'decimals' => (string) $c->decimals,
                'status' => $c->status ? '1' : '0',
            ])->all());

        return view('admin.settings.index', [
            'schema' => $this->settings->schema(),
            'values' => $this->settings->values(),
            'iconChoices' => $this->settings->iconChoices(),
            'socialRows' => array_values($rows),
            'paymentRows' => array_values($paymentRows),
            'footerRows' => array_values($footerRows),
            'currencyRows' => array_values($currencyRows),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('update', Setting::class);

        $this->settings->save($request->validated());

        $message = __('Settings updated successfully!');

        // AJAX save: no page reload — the form is submitted via fetch.
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('success', $message);
    }

    /**
     * Check the saved Cloudflare R2 credentials with a write/read/delete probe.
     */
    /**
     * Check the saved CutLuy key and report the remaining quota.
     */
    public function testCutluy(CutLuyService $cutluy): JsonResponse
    {
        $this->authorize('update', Setting::class);

        try {
            $usage = $cutluy->usage();
        } catch (CutLuyException $e) {
            return response()->json(['ok' => false, 'message' => match ($e->errorCode) {
                'not_configured' => __('Save your CutLuy API key first.'),
                'unauthorized' => __('CutLuy rejected the API key. Check it and save again.'),
                'account_suspended' => __('This CutLuy account is suspended. Contact CutLuy support.'),
                'quota_exceeded' => __('The CutLuy payment quota for this period is used up. Upgrade or renew your plan.'),
                'rate_limited' => __('Too many requests. Try again in :seconds seconds.', ['seconds' => $e->retryAfter ?? 30]),
                default => __('Could not reach CutLuy. Try again in a moment.'),
            }], 422);
        }

        return response()->json(['ok' => true, 'message' => __('Connected. Plan: :plan — :remaining of :limit payments left this period.', [
            'plan' => (string) ($usage['plan'] ?? '—'),
            'remaining' => (string) ($usage['remaining'] ?? '—'),
            'limit' => (string) ($usage['limit'] ?? '—'),
        ])]);
    }

    public function testStorage(MediaStorageService $storage): JsonResponse
    {
        $this->authorize('update', Setting::class);

        $result = $storage->testR2($this->settings->r2());

        return response()->json($result, $result['ok'] ? 200 : 422);
    }
}
