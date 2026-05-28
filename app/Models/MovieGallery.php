<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovieGallery extends Model
{
    use HasFactory;

    protected $table = 'movie_gallery';

    protected $fillable = ['movie_id', 'image', 'order'];

    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }
}
