<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CastMember extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'photo', 'bio'];

    public function movies()
    {
        return $this->belongsToMany(Movie::class, 'movie_cast')
                    ->withPivot('character_name', 'role', 'order');
    }
}
