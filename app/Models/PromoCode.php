<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'discount_type', 'discount_value', 'valid_from', 'valid_to', 'usage_limit', 'usage_count', 'is_active'];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'is_active' => 'boolean',
        'discount_value' => 'decimal:2',
    ];
}
