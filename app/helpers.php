<?php

if (! function_exists('image_url')) {
    /**
     * Resolve a stored image value to a usable URL.
     * Handles absolute URLs (LFM), template asset paths, /storage/ URLs, and relative storage paths.
     */
    function image_url(?string $value, ?string $fallback = null): ?string
    {
        if (! $value) {
            return $fallback ? asset($fallback) : null;
        }
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }
        if (str_starts_with($value, '/')) {
            return url($value);
        }
        if (str_starts_with($value, 'assets/')) {
            return asset($value);
        }
        return asset('storage/' . $value);
    }
}
