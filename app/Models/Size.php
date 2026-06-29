<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    protected $fillable = [
        'name',
        'code',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'sort_order' => 'integer',
    ];
}
