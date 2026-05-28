<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingSeat extends Model
{
    use HasFactory;

    protected $fillable = ['booking_id', 'ticket_class_id', 'showtime_id', 'seat_row', 'seat_number'];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function showtime()
    {
        return $this->belongsTo(Showtime::class);
    }

    public function ticketClass()
    {
        return $this->belongsTo(TicketClass::class);
    }
}
