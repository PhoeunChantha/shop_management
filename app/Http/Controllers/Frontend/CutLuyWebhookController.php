<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessCutLuyWebhook;
use App\Models\CutluyWebhookEvent;
use App\Services\Frontend\CutLuyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CutLuyWebhookController extends Controller
{
    private const EVENTS = ['payment.completed', 'payment.scanned', 'payment.expired', 'payment.failed'];

    public function __construct(private readonly CutLuyService $cutluy) {}

    /**
     * CutLuy server-to-server delivery (CSRF-exempt, signature-checked).
     */
    public function handle(Request $request): JsonResponse
    {
        // The raw bytes exactly as sent: the signature covers these, so verify
        // before anything decodes or re-encodes the body.
        $raw = $request->getContent();

        if (! $this->cutluy->verifySignature($raw, $request->header('X-CutLuy-Signature'))) {
            Log::warning('CutLuy webhook rejected: bad or stale signature.', ['ip' => $request->ip()]);

            return response()->json(['error' => 'invalid_signature'], 401);
        }

        $payload = json_decode($raw, true);
        $event = (string) ($request->header('X-CutLuy-Event') ?: ($payload['event'] ?? $payload['type'] ?? ''));
        $paymentId = $this->paymentId(is_array($payload) ? $payload : []);

        // Signed but nothing to act on: acknowledge so CutLuy stops retrying.
        if (! in_array($event, self::EVENTS, true) || $paymentId === '') {
            return response()->json(['received' => true]);
        }

        // The same event may legitimately arrive twice: one row per payment + event.
        $record = CutluyWebhookEvent::firstOrCreate(
            ['payment_id' => $paymentId, 'event' => $event],
            ['payload' => $payload],
        );

        if (! $record->processed_at) {
            ProcessCutLuyWebhook::dispatch($record->id);
        }

        return response()->json(['received' => true]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function paymentId(array $payload): string
    {
        foreach (['data.id', 'data.payment.id', 'payment.id', 'payment_id', 'data.payment_id', 'id'] as $path) {
            $value = data_get($payload, $path);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '';
    }
}
