<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SidebarBanner extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'kicker', 'subtitle', 'cta_text', 'image', 'link', 'placement', 'position', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
