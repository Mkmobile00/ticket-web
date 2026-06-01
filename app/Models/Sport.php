<?php

namespace App\Models;

use App\Models\Concerns\Seatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sport extends Model
{
    use HasFactory, Seatable;

    protected $fillable = [
        'title', 'slug', 'description', 'banner_image', 'team_home', 'team_away',
        'sport_date', 'start_time', 'venue', 'city_id', 'status', 'seat_layout'
    ];

    protected $casts = [
        'sport_date' => 'date',
        'seat_layout' => 'array',
    ];

    public function seatLayoutArray(): array
    {
        $l = $this->seat_layout ?: [];
        return ['rows' => $l['rows'] ?? [], 'seats_per_row' => $l['seats_per_row'] ?? [], 'grid' => $l['grid'] ?? null];
    }

    public function seatTiers()
    {
        return $this->tickets->map(fn ($t) => [
            'id' => $t->id, 'name' => $t->type, 'price' => (float) $t->price, 'rows' => (array) $t->seat_rows,
        ]);
    }

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
