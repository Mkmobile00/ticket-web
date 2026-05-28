<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketClass extends Model
{
    use HasFactory;

    protected $fillable = ['showtime_id', 'name', 'price', 'seat_rows'];

    protected $casts = [
        'seat_rows' => 'array',
        'price' => 'decimal:2',
    ];

    public function showtime()
    {
        return $this->belongsTo(Showtime::class);
    }

    public function bookingSeats()
    {
        return $this->hasMany(BookingSeat::class);
    }
}
