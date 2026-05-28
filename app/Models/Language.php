<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code'];

    public function movies()
    {
        return $this->belongsToMany(Movie::class, 'language_movie');
    }

    public function showtimes()
    {
        return $this->hasMany(Showtime::class);
    }
}
