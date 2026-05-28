<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'description', 'banner_image', 'event_date',
        'start_time', 'end_time', 'address', 'organizer', 'latitude', 'longitude', 'status'
    ];

    protected $casts = [
        'event_date' => 'date',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

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
