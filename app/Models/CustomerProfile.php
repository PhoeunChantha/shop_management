<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'email',
        'name',
        'phone',
        'status',
        'notes',
        'marketing_opt_out',
        'marketing_opt_out_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'marketing_opt_out' => 'boolean',
            'marketing_opt_out_at' => 'datetime',
        ];
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(CustomerTag::class, 'customer_profile_tag')
            ->withTimestamps()
            ->orderBy('customer_tags.name');
    }
}
