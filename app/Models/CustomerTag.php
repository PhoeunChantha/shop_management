<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CustomerTag extends Model
{
    protected $fillable = [
        'name',
        'color',
    ];

    public function profiles(): BelongsToMany
    {
        return $this->belongsToMany(CustomerProfile::class, 'customer_profile_tag')
            ->withTimestamps();
    }
}
