<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\ImageManager;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared bulk operations for the admin tables (bulk delete / enable / disable).
 *
 * Delete respects an optional per-model `isInUse()` guard: records still
 * referenced elsewhere are skipped and reported back instead of being removed.
 */
final class BulkActionService
{
    /**
     * Delete the given IDs, skipping any record whose `isInUse()` returns true.
     *
     * @param  class-string<Model>  $modelClass
     * @param  array<int, int|string>  $ids
     * @return array{deleted: int, blocked: array<int, string>}
     */
    public function destroy(string $modelClass, array $ids, ?string $imageFolder = null): array
    {
        $deleted = 0;
        $blocked = [];

        $records = $modelClass::query()->whereKey($ids)->get();

        foreach ($records as $record) {
            if (method_exists($record, 'isInUse') && $record->isInUse()) {
                $blocked[] = $this->labelFor($record);

                continue;
            }

            if ($imageFolder && ! empty($record->image)) {
                ImageManager::delete($record->image, $imageFolder);
            }

            $record->delete();
            $deleted++;
        }

        return ['deleted' => $deleted, 'blocked' => $blocked];
    }

    /**
     * Enable/disable the given IDs in a single query.
     *
     * @param  class-string<Model>  $modelClass
     * @param  array<int, int|string>  $ids
     */
    public function setStatus(string $modelClass, array $ids, bool $status): int
    {
        return $modelClass::query()->whereKey($ids)->update(['status' => $status]);
    }

    /**
     * A human-friendly label for a blocked record (name → code → #id).
     */
    private function labelFor(Model $record): string
    {
        return $record->getAttribute('name')
            ?? $record->getAttribute('code')
            ?? ('#'.$record->getKey());
    }
}
