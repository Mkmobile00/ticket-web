<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingSeat extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id', 'ticket_class_id', 'showtime_id',
        'seatable_type', 'seatable_id', 'seat_row', 'seat_number', 'price', 'tier_label',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function showtime()
    {
        return $this->belongsTo(Showtime::class);
    }

    /** What these seats belong to: Showtime (movie), Event, or Sport. */
    public function seatable()
    {
        return $this->morphTo();
    }

    public function ticketClass()
    {
        return $this->belongsTo(TicketClass::class);
    }
}
