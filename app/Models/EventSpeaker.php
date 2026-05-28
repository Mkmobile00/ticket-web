<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventSpeaker extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'photo', 'about', 'designation', 'facebook_url', 'twitter_url', 'linkedin_url'];

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_speaker')->withPivot('order');
    }
}
