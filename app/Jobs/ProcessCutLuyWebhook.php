<?php

namespace App\Jobs;

use App\Exceptions\CutLuyException;
use App\Models\CutluyPayment;
use App\Models\CutluyWebhookEvent;
use App\Services\Frontend\CutLuyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Handles one verified CutLuy webhook off the request cycle, so the endpoint
 * can answer 2xx at once. The event only says "payment X changed": the job
 * re-reads the payment from CutLuy and settles from that, never from the body.
 */
class ProcessCutLuyWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60, 180, 600];

    public function __construct(public readonly int $eventId) {}

    public function handle(CutLuyService $cutluy): void
    {
        $event = CutluyWebhookEvent::find($this->eventId);

        if (! $event || $event->processed_at) {
            return;
        }

        $payment = CutluyPayment::where('payment_id', $event->payment_id)->first();

        if (! $payment) {
            // Not a payment this store created (another store on the account).
            Log::info('CutLuy webhook for an unknown payment ignored.', ['payment_id' => $event->payment_id]);
            $event->update(['processed_at' => now()]);

            return;
        }

        try {
            $cutluy->sync($payment);
        } catch (CutLuyException $e) {
            if ($e->errorCode === 'rate_limited') {
                $this->release($e->retryAfter ?? 60);

                return;
            }

            throw $e;
        }

        $event->update(['processed_at' => now()]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('CutLuy webhook could not be processed.', ['event' => $this->eventId, 'error' => $e->getMessage()]);
    }
}
