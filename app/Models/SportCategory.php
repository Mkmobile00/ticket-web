<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SportCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public function sports()
    {
        return $this->belongsToMany(Sport::class, 'category_sport');
    }
}
