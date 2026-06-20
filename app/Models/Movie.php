<?php

namespace App\Models;

use App\Models\Concerns\HasYoutubeTrailer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory, HasYoutubeTrailer;

    protected $fillable = [
        'title', 'slug', 'synopsis', 'poster_image', 'banner_image', 
        'trailer_url', 'release_date', 'duration_minutes', 
        'rating_tomato', 'rating_audience', 'user_rating', 'status',
        'meta_title', 'meta_description', 'meta_keywords',
    ];

    protected $casts = [
        'release_date' => 'date',
        'rating_tomato' => 'decimal:2',
        'rating_audience' => 'decimal:2',
        'user_rating' => 'decimal:2',
    ];

    public function languages()
    {
        return $this->belongsToMany(Language::class, 'language_movie');
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'genre_movie');
    }

    public function formats()
    {
        return $this->belongsToMany(Format::class, 'format_movie');
    }

    public function cast()
    {
        return $this->belongsToMany(CastMember::class, 'movie_cast')
                    ->withPivot('character_name', 'role', 'order')
                    ->orderBy('order');
    }

    public function gallery()
    {
        return $this->hasMany(MovieGallery::class)->orderBy('order');
    }

    public function showtimes()
    {
        return $this->hasMany(Showtime::class);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
