<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];

    public static function getValue($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function setValue($key, $value)
    {
        return self::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /** Resolve an image setting (stored as a path or URL) to a usable URL. */
    public static function image($key, $default = null)
    {
        $p = self::getValue($key, $default);
        if (! $p) {
            return $default ? asset($default) : null;
        }
        if (str_starts_with($p, 'http')) {
            return $p;
        }
        return str_starts_with($p, 'assets/') ? asset($p) : asset('storage/' . ltrim($p, '/'));
    }
}
