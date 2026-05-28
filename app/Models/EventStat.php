<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventStat extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'label', 'value', 'order'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
