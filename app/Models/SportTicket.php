<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SportTicket extends Model
{
    use HasFactory;

    protected $fillable = ['sport_id', 'type', 'price', 'quantity_total', 'quantity_sold'];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }
}
