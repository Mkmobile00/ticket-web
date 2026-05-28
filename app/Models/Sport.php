<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sport extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'description', 'banner_image', 'team_home', 'team_away',
        'sport_date', 'start_time', 'venue', 'city_id', 'status'
    ];

    protected $casts = [
        'sport_date' => 'date',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function categories()
    {
        return $this->belongsToMany(SportCategory::class, 'category_sport');
    }

    public function tickets()
    {
        return $this->hasMany(SportTicket::class);
    }

    public function bookings()
    {
        return $this->morphMany(Booking::class, 'bookable');
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
