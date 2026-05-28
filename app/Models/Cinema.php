<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cinema extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'city_id', 'address', 'latitude', 'longitude', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function screens()
    {
        return $this->hasMany(Screen::class);
    }
}
