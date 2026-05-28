<?php

namespace App\Console\Commands;

use App\Models\Movie;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Give every movie a poster.
 *
 *  - If TMDB_API_KEY is set, fetches the real official poster by title from
 *    themoviedb.org (free API).
 *  - Otherwise generates a clean, branded poster locally with GD showing the
 *    movie's title, genre and rating — so each poster matches its film.
 *
 *   php artisan movies:posters            (auto: tmdb if key, else generate)
 *   php artisan movies:posters --generate (force local generation)
 */
class GenerateMoviePosters extends Command
{
    protected $signature = 'movies:posters
        {--generate : Force flat gradient generation}
        {--photos : Use free stock-photo backgrounds (Lorem Picsum) with the title composited on top}';

    protected $description = 'Give movies posters: real TMDB art (if key), free stock-photo posters (--photos), or branded gradients.';

    private string $dir;
    private string $font;

    public function handle(): int
    {
        $this->dir = public_path('assets/images/movie/posters');
        if (! is_dir($this->dir)) {
            mkdir($this->dir, 0775, true);
        }
        // Prefer a bold system font for crisp titles.
        $this->font = collect([
            'C:/Windows/Fonts/arialbd.ttf',
            'C:/Windows/Fonts/segoeuib.ttf',
            'C:/Windows/Fonts/arial.ttf',
        ])->first(fn ($p) => is_file($p)) ?? '';

        $photos = $this->option('photos');
        $useTmdb = config('services.tmdb.key') && ! $this->option('generate') && ! $photos;

        $this->info($useTmdb
            ? 'Fetching real posters from TMDB…'
            : ($photos ? 'Building photo posters from free stock images…' : 'Generating branded gradient posters…'));

        $ok = 0;
        foreach (Movie::with('genres')->get() as $movie) {
            $rel = 'assets/images/movie/posters/' . $movie->slug . '.jpg';
            $abs = public_path($rel);

            $done = false;
            if ($useTmdb) {
                $done = $this->fetchTmdb($movie, $abs);
            }
            if (! $done && $photos) {
                $done = $this->generatePhoto($movie, $abs);
            }
            if (! $done) {
                $this->generate($movie, $abs); // gradient fallback (always works)
            }

            $movie->update(['poster_image' => $rel]);
            $ok++;
            $this->line('  ✓ ' . $movie->title);
        }

        $this->info("Done. {$ok} posters written to {$this->dir}");
        return self::SUCCESS;
    }

    private function fetchTmdb(Movie $movie, string $abs): bool
    {
        try {
            $search = Http::get('https://api.themoviedb.org/3/search/movie', [
                'api_key' => config('services.tmdb.key'),
                'query' => $movie->title,
            ]);
            $path = $search->json('results.0.poster_path');
            if (! $path) {
                return false;
            }
            $img = Http::get(rtrim(config('services.tmdb.image_base'), '/') . $path);
            if (! $img->successful()) {
                return false;
            }
            file_put_contents($abs, $img->body());
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    // ---- Local branded poster (GD) -----------------------------------------

    /** Flat branded gradient poster (always works, no network). */
    private function generate(Movie $movie, string $abs): void
    {
        $W = 400; $H = 600;
        $im = imagecreatetruecolor($W, $H);

        [$c1, $c2] = $this->palette($movie->title);
        $this->gradient($im, $W, $H, $c1, $c2);
        $this->cornerGlow($im, $W, $H);

        // Giant faded initial as a backdrop motif (gradient style only).
        if ($this->font) {
            $faint = imagecolorallocatealpha($im, 255, 255, 255, 110);
            imagettftext($im, 240, 0, 60, 360, $faint, $this->font, strtoupper(mb_substr($movie->title, 0, 1)));
        }

        $this->bottomBand($im, $W, $H);
        $this->drawOverlayText($im, $movie, $W, $H);

        imagejpeg($im, $abs, 90);
        imagedestroy($im);
    }

    /** Poster built on a free, license-clear stock photo (Lorem Picsum). */
    private function generatePhoto(Movie $movie, string $abs): bool
    {
        $W = 400; $H = 600;
        try {
            // Deterministic per movie; slight blur reads better behind text.
            $url = "https://picsum.photos/seed/{$movie->slug}/800/1200?blur=1";
            $resp = Http::timeout(25)->get($url);
            if (! $resp->successful()) {
                return false;
            }
            $src = @imagecreatefromstring($resp->body());
            if (! $src) {
                return false;
            }
        } catch (\Throwable $e) {
            return false;
        }

        $im = imagecreatetruecolor($W, $H);
        // Cover-crop the photo to fill the poster.
        $sw = imagesx($src); $sh = imagesy($src);
        $scale = max($W / $sw, $H / $sh);
        $nw = (int) ($sw * $scale); $nh = (int) ($sh * $scale);
        imagecopyresampled($im, $src, (int) (($W - $nw) / 2), (int) (($H - $nh) / 2), 0, 0, $nw, $nh, $sw, $sh);
        imagedestroy($src);

        // Darken overall + brand-tinted top, for contrast and identity.
        imagefilledrectangle($im, 0, 0, $W, $H, imagecolorallocatealpha($im, 0, 0, 0, 70));
        [$c1] = $this->palette($movie->title);
        $tint = imagecolorallocatealpha($im, $c1[0], $c1[1], $c1[2], 95);
        imagefilledrectangle($im, 0, 0, $W, (int) ($H * 0.30), $tint);

        $this->bottomBand($im, $W, $H);
        $this->drawOverlayText($im, $movie, $W, $H);

        imagejpeg($im, $abs, 90);
        imagedestroy($im);
        return true;
    }

    /** Bottom darkening gradient for text legibility. */
    private function bottomBand($im, int $W, int $H): void
    {
        for ($y = (int) ($H * 0.50); $y < $H; $y++) {
            $alpha = (int) (110 * (($y - $H * 0.50) / ($H * 0.50)));
            $col = imagecolorallocatealpha($im, 0, 0, 0, max(0, 127 - $alpha));
            imageline($im, 0, $y, $W, $y, $col);
        }
    }

    /** Brand tag + title + genre + star rating (shared by both styles). */
    private function drawOverlayText($im, Movie $movie, int $W, int $H): void
    {
        if (! $this->font) {
            return;
        }
        $white = imagecolorallocate($im, 255, 255, 255);
        $muted = imagecolorallocate($im, 210, 214, 222);
        $accent = imagecolorallocate($im, 255, 90, 70);

        imagettftext($im, 12, 0, 24, 40, $accent, $this->font, 'BULETO');
        imagettftext($im, 9, 0, 24, 60, $muted, $this->font, 'NOW SHOWING');

        $lines = $this->wrap($movie->title, 26, $W - 48);
        $y = $H - 150 - (count($lines) - 1) * 40;
        foreach ($lines as $line) {
            imagettftext($im, 26, 0, 24, $y, $white, $this->font, $line);
            $y += 40;
        }

        $genre = $movie->genres->pluck('name')->take(3)->implode(' • ');
        imagettftext($im, 11, 0, 24, $H - 96, $muted, $this->font, $genre ?: 'Feature Film');
        $this->star($im, 30, $H - 72, 9, $accent);
        $rating = number_format((float) $movie->user_rating, 1) . ' / 5     ·     ' . $movie->duration_minutes . ' min';
        imagettftext($im, 12, 0, 46, $H - 64, $accent, $this->font, $rating);
    }

    private function gradient($im, int $W, int $H, array $c1, array $c2): void
    {
        for ($y = 0; $y < $H; $y++) {
            $t = $y / $H;
            $r = (int) ($c1[0] + ($c2[0] - $c1[0]) * $t);
            $g = (int) ($c1[1] + ($c2[1] - $c1[1]) * $t);
            $b = (int) ($c1[2] + ($c2[2] - $c1[2]) * $t);
            $col = imagecolorallocate($im, $r, $g, $b);
            imageline($im, 0, $y, $W, $y, $col);
        }
    }

    /** Draw a filled 5-point star centred at ($cx,$cy). */
    private function star($im, int $cx, int $cy, int $r, int $color): void
    {
        $pts = [];
        for ($i = 0; $i < 10; $i++) {
            $rad = ($i % 2 === 0) ? $r : $r * 0.42;
            $ang = deg2rad(-90 + $i * 36);
            $pts[] = $cx + $rad * cos($ang);
            $pts[] = $cy + $rad * sin($ang);
        }
        imagefilledpolygon($im, $pts, $color);
    }

    private function cornerGlow($im, int $W, int $H): void
    {
        $glow = imagecolorallocatealpha($im, 255, 255, 255, 118);
        for ($i = 0; $i < 6; $i++) {
            imagefilledellipse($im, (int) ($W * 0.8), (int) ($H * 0.18), 220 - $i * 20, 220 - $i * 20, $glow);
        }
    }

    /** Deterministic, pleasant gradient pair per title. */
    private function palette(string $title): array
    {
        $pairs = [
            [[123, 31, 75], [255, 80, 70]],   // crimson
            [[26, 32, 64], [58, 123, 213]],   // ocean
            [[34, 14, 56], [123, 40, 200]],   // violet
            [[10, 50, 45], [47, 158, 111]],   // emerald
            [[60, 30, 10], [255, 138, 61]],   // amber
            [[40, 12, 30], [214, 51, 132]],   // magenta
            [[14, 28, 40], [0, 160, 160]],    // teal
            [[40, 24, 10], [200, 160, 40]],   // gold
        ];
        $idx = abs(crc32($title)) % count($pairs);
        return $pairs[$idx];
    }

    /** Word-wrap to fit a pixel width at the given font size. */
    private function wrap(string $text, int $size, int $maxWidth): array
    {
        if (! $this->font) {
            return [$text];
        }
        $words = explode(' ', strtoupper($text));
        $lines = [];
        $cur = '';
        foreach ($words as $w) {
            $try = trim($cur . ' ' . $w);
            $bbox = imagettfbbox($size, 0, $this->font, $try);
            if (($bbox[2] - $bbox[0]) > $maxWidth && $cur !== '') {
                $lines[] = $cur;
                $cur = $w;
            } else {
                $cur = $try;
            }
        }
        if ($cur !== '') {
            $lines[] = $cur;
        }
        return $lines;
    }
}
