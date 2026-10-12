<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A CutLuy API call failed. `errorCode` is CutLuy's own code where it sent one
 * (unauthorized, quota_exceeded, account_suspended, rate_limited, …).
 */
class CutLuyException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status = 0,
        public readonly ?int $retryAfter = null,
    ) {
        parent::__construct($message);
    }

    /** Safe to show a shopper: never leaks account or key details. */
    public function customerMessage(): string
    {
        return match ($this->errorCode) {
            'rate_limited' => __('Too many payment requests right now. Please try again in :seconds seconds.', ['seconds' => $this->retryAfter ?? 30]),
            default => __('QR payment is not available right now. Please choose another payment method or try again later.'),
        };
    }

    /** True when the store owner has to act (bad key, no quota, suspended). */
    public function needsOwnerAction(): bool
    {
        return in_array($this->errorCode, ['unauthorized', 'quota_exceeded', 'account_suspended'], true);
    }
}
