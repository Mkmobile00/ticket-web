<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'bookable_type', 'bookable_id', 'showtime_id', 'total_amount',
        'status', 'promo_code_id', 'discount_amount', 'payment_method',
        'transaction_id', 'qr_code', 'booked_at'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'booked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function showtime()
    {
        return $this->belongsTo(Showtime::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function bookable()
    {
        return $this->morphTo();
    }

    public function seats()
    {
        return $this->hasMany(BookingSeat::class);
    }

    public function addons()
    {
        return $this->hasMany(BookingAddon::class);
    }
}
