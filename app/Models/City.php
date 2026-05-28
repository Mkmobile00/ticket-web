<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public function cinemas()
    {
        return $this->hasMany(Cinema::class);
    }

    public function sports()
    {
        return $this->hasMany(Sport::class);
    }
}
