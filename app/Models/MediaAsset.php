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
        'filename',
        'thumbnail_filename',
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
    ];

    protected $casts = [
        'size' => 'integer',
        'original_size' => 'integer',
        'optimized_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
                    ->orWhere('alt_text', 'like', "%{$term}%");
            })
        );
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
