<?php

namespace App\Models;

use App\Helpers\ImageManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaAsset extends Model
{
    protected $fillable = [
        'user_id',
        'folder',
        'disk',
        'filename',
        'thumbnail_filename',
        'object_key',
        'thumbnail_object_key',
        'original_name',
        'mime_type',
        'size',
        'original_size',
        'optimized_size',
        'width',
        'height',
        'optimization_status',
        'optimization_notes',
        'alt_text',
        'title',
        'tags',
        'checksum',
        'usage_count',
        'usage_synced_at',
    ];

    protected $casts = [
        'size' => 'integer',
        'original_size' => 'integer',
        'optimized_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'usage_count' => 'integer',
        'usage_synced_at' => 'datetime',
        'tags' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * True when the file lives on a filesystem disk (R2 / S3) rather than in
     * public/uploads. Remote assets store their public URL as the filename, so
     * ImageManager passes it through untouched for every consumer.
     */
    public function isRemote(): bool
    {
        return ($this->disk ?: 'local') !== 'local';
    }

    public function getUrlAttribute(): ?string
    {
        return ImageManager::url($this->filename, $this->folder);
    }

    public function getPathAttribute(): ?string
    {
        return ImageManager::path($this->filename, $this->folder);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return ImageManager::url($this->thumbnail_filename ?: $this->filename, $this->folder);
    }

    /**
     * The value a consumer (product, banner, brand, category…) stores when this
     * asset is picked. It is deliberately folder-independent so ONE image can
     * be reused anywhere: remote assets already carry an absolute URL, local
     * ones use their public path under uploads/. ImageManager resolves both
     * without knowing which folder the consuming field belongs to.
     */
    public function getReferenceAttribute(): ?string
    {
        return $this->isRemote() ? $this->filename : $this->path;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->title ?: ($this->original_name ?: $this->filename);
    }

    public function getStorageLabelAttribute(): string
    {
        return $this->isRemote() ? strtoupper($this->disk) : 'Local';
    }

    public function getOriginalSizeForHumansAttribute(): string
    {
        return $this->formatBytes($this->original_size ?: $this->size);
    }

    public function getOptimizedSizeForHumansAttribute(): string
    {
        return $this->formatBytes($this->optimized_size ?: $this->size);
    }

    public function getSizeForHumansAttribute(): string
    {
        return $this->formatBytes($this->size);
    }

    public function getCompressionSavingsAttribute(): int
    {
        $original = (int) ($this->original_size ?: $this->size);
        $optimized = (int) ($this->optimized_size ?: $this->size);

        if ($original <= 0 || $optimized >= $original) {
            return 0;
        }

        return (int) round((1 - ($optimized / $original)) * 100);
    }

    public function getOptimizationLabelAttribute(): string
    {
        return match ($this->optimization_status) {
            'optimized' => $this->compression_savings > 0 ? $this->compression_savings.'% saved' : 'Optimized',
            'kept_original' => 'Already optimized',
            'skipped' => 'Original kept',
            'failed' => 'Needs review',
            default => 'Pending',
        };
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when(
            filled($term),
            fn (Builder $query) => $query->where(function (Builder $query) use ($term) {
                $query->where('filename', 'like', "%{$term}%")
                    ->orWhere('original_name', 'like', "%{$term}%")
                    ->orWhere('title', 'like', "%{$term}%")
                    ->orWhere('tags', 'like', "%{$term}%")
                    ->orWhere('alt_text', 'like', "%{$term}%");
            })
        );
    }

    /**
     * Narrow to a media kind — "image", "vector", "animated" or "other" — using
     * the stored mime type, which is what the library filters on.
     */
    public function scopeOfKind(Builder $query, ?string $kind): Builder
    {
        return match ($kind) {
            'vector' => $query->where('mime_type', 'like', '%svg%'),
            'animated' => $query->where('mime_type', 'like', '%gif%'),
            'image' => $query->where('mime_type', 'like', 'image/%')
                ->where('mime_type', 'not like', '%svg%')
                ->where('mime_type', 'not like', '%gif%'),
            'other' => $query->where(function (Builder $query) {
                $query->whereNull('mime_type')->orWhere('mime_type', 'not like', 'image/%');
            }),
            default => $query,
        };
    }

    private function formatBytes(?int $bytes): string
    {
        $bytes = max(1, (int) $bytes);

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        return number_format($bytes / 1024, 0).' KB';
    }
}
