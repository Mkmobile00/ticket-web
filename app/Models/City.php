<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'icon'];

    /** Resolve the (admin-uploaded) icon path to a usable URL. */
    public function getIconUrlAttribute(): ?string
    {
        $p = $this->icon;
        if (! $p) {
            return null;
        }
        if (str_starts_with($p, 'http')) {
            return $p;
        }
        if (str_starts_with($p, '/')) {
            return url($p); // Laravel Filemanager absolute path
        }
        return str_starts_with($p, 'assets/') ? asset($p) : asset('storage/' . ltrim($p, '/'));
    }

    public function cinemas()
    {
        return $this->hasMany(Cinema::class);
    }

    public function sports()
    {
        return $this->hasMany(Sport::class);
    }
}
