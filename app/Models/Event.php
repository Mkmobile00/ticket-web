<?php

namespace App\Models;

use App\Models\Concerns\HasYoutubeTrailer;
use App\Models\Concerns\Seatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory, HasYoutubeTrailer, Seatable;

    protected $fillable = [
        'title', 'slug', 'description', 'banner_image', 'detail_image', 'trailer_url', 'event_date',
        'start_time', 'end_time', 'address', 'city_id', 'organizer', 'latitude', 'longitude', 'status', 'seat_layout',
        'meta_title', 'meta_description', 'meta_keywords',
    ];

    protected $casts = [
        'event_date' => 'date',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'seat_layout' => 'array',
    ];

    /** ['rows'=>[], 'seats_per_row'=>[]] for the seat map. */
    public function seatLayoutArray(): array
    {
        $l = $this->seat_layout ?: [];
        return ['rows' => $l['rows'] ?? [], 'seats_per_row' => $l['seats_per_row'] ?? [], 'grid' => $l['grid'] ?? null];
    }

    /** Price tiers (ticket types) mapped to seat rows. */
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
        return $this->belongsToMany(EventCategory::class, 'category_event');
    }

    public function speakers()
    {
        return $this->belongsToMany(EventSpeaker::class, 'event_speaker')
                    ->withPivot('order')
                    ->orderByPivot('order');
    }

    public function tickets()
    {
        return $this->hasMany(EventTicket::class);
    }

    public function stats()
    {
        return $this->hasMany(EventStat::class)->orderBy('order');
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
