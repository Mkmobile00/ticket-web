<?php

namespace App\Models\Concerns;

/**
 * Shared YouTube trailer helpers for models with a `trailer_url` column
 * (Movie, Event, Sport). Turns any YouTube link into an embeddable URL.
 */
trait HasYoutubeTrailer
{
    /** The 11-char YouTube id from trailer_url (watch / youtu.be / embed / shorts), or null. */
    public function getYoutubeIdAttribute(): ?string
    {
        if (empty($this->trailer_url)) {
            return null;
        }
        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/))([A-Za-z0-9_-]{11})~', $this->trailer_url, $m)) {
            return $m[1];
        }
        return null;
    }

    /** Embeddable trailer URL (iframe/webview), or null if not a recognised YouTube link. */
    public function getTrailerEmbedUrlAttribute(): ?string
    {
        $id = $this->youtube_id;
        return $id ? "https://www.youtube.com/embed/{$id}?rel=0" : null;
    }

    /** YouTube thumbnail for the trailer, or null. */
    public function getTrailerThumbUrlAttribute(): ?string
    {
        $id = $this->youtube_id;
        return $id ? "https://img.youtube.com/vi/{$id}/hqdefault.jpg" : null;
    }
}
