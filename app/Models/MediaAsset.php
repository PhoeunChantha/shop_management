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
        'original_name',
        'mime_type',
        'size',
        'width',
        'height',
        'alt_text',
    ];

    protected $casts = [
        'size' => 'integer',
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

    public function getSizeForHumansAttribute(): string
    {
        if ($this->size >= 1048576) {
            return number_format($this->size / 1048576, 1).' MB';
        }

        return number_format(max(1, $this->size) / 1024, 0).' KB';
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
}
