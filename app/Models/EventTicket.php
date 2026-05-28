<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventTicket extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'type', 'price', 'quantity_total', 'quantity_sold', 'seat_rows'];

    protected $casts = [
        'price' => 'decimal:2',
        'seat_rows' => 'array',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
