<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $fillable = ['location', 'parent_id', 'label', 'url', 'position', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** Site destinations offered in the admin "Url" dropdown (no free typing). */
    public const LINK_OPTIONS = [
        '/'         => 'Home',
        '/movies'   => 'Movies',
        '/events'   => 'Events',
        '/sports'   => 'Sports',
        '/blog'     => 'Blog',
        '/about'    => 'About Us',
        '/contact'  => 'Contact',
        '/account'  => 'My Bookings',
        '#'         => 'No link (dropdown heading)',
    ];

    public function parent()
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('position');
    }
}
