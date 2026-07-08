<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend\Concerns;

use Illuminate\Http\Request;

/**
 * Shared validation + flash helpers for the admin bulk table actions.
 * Paired with App\Services\BulkActionService.
 */
trait HandlesBulkActions
{
    /**
     * Validate and return the selected IDs for a bulk operation.
     *
     * @return array<int, int>
     */
    protected function validatedIds(Request $request): array
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        return $data['ids'];
    }

    /**
     * Validate the selected IDs plus a boolean status for enable/disable.
     *
     * @return array{0: array<int, int>, 1: bool}
     */
    protected function validatedStatus(Request $request): array
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'status' => ['required', 'boolean'],
        ]);

        return [$data['ids'], (bool) $data['status']];
    }

    /**
     * Build a flash payload from a bulk-delete result, wording blocked records.
     *
     * @param  array{deleted: int, blocked: array<int, string>}  $result
     * @return array<string, string>
     */
    protected function bulkFlash(array $result, string $noun, string $reason): array
    {
        $messages = [];

        if ($result['deleted'] > 0) {
            $messages['success'] = $result['deleted'].' '.$noun.'(s) deleted successfully!';
        }

        if (! empty($result['blocked'])) {
            $messages['warning'] = 'Skipped '.count($result['blocked']).' '.$noun.'(s) still '.$reason.': '
                .implode(', ', $result['blocked']).'.';
        }

        if (empty($messages)) {
            $messages['info'] = 'No '.$noun.'s were deleted.';
        }

        return $messages;
    }
}
