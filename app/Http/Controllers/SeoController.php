<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Event;
use App\Models\Movie;
use App\Models\Sport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Search-engine endpoints: an always-current XML sitemap built from the
 * database and a robots.txt that points crawlers at it. Both are dynamic so
 * they use the live host and never go stale as the catalog changes.
 */
class SeoController extends Controller
{
    /** GET /sitemap.xml */
    public function sitemap()
    {
        $urls = [];
        $add = function (string $loc, $lastmod = null, string $freq = 'weekly', string $priority = '0.6') use (&$urls) {
            $urls[] = [
                'loc'      => $loc,
                'lastmod'  => $lastmod ? Carbon::parse($lastmod)->toAtomString() : null,
                'freq'     => $freq,
                'priority' => $priority,
            ];
        };

        // Static / listing pages.
        $add(url('/'), now(), 'daily', '1.0');
        $add(url('/movies'), now(), 'daily', '0.9');
        $add(url('/events'), now(), 'daily', '0.9');
        $add(url('/sports'), now(), 'daily', '0.9');
        $add(url('/blog'), now(), 'weekly', '0.7');
        $add(url('/about'), null, 'monthly', '0.4');
        $add(url('/contact'), null, 'monthly', '0.4');

        // Match the public catalog's "visible" status filters so the sitemap
        // lists exactly the pages a visitor can actually reach.
        foreach (Movie::whereIn('status', ['now_showing', 'coming_soon', 'active'])->get() as $m) {
            $add(url('/movie?m=' . urlencode($m->slug ?: Str::slug($m->title))), $m->updated_at, 'weekly', '0.8');
        }
        foreach (Event::whereIn('status', ['upcoming', 'ongoing', 'live', 'active'])->get() as $e) {
            $add(url('/event?e=' . urlencode($e->slug ?: Str::slug($e->title))), $e->updated_at, 'weekly', '0.8');
        }
        foreach (Sport::whereIn('status', ['upcoming', 'live', 'active'])->get() as $s) {
            $add(url('/event?e=' . urlencode($s->slug ?: Str::slug($s->title))), $s->updated_at, 'weekly', '0.8');
        }
        foreach (BlogPost::query()->whereNotNull('published_at')->where('published_at', '<=', now())->get() as $p) {
            $add(url('/blog/' . ($p->slug ?: $p->id)), $p->updated_at, 'monthly', '0.6');
        }

        $e = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= '  <url>' . "\n"
                . '    <loc>' . $e($u['loc']) . '</loc>' . "\n"
                . ($u['lastmod'] ? '    <lastmod>' . $e($u['lastmod']) . '</lastmod>' . "\n" : '')
                . '    <changefreq>' . $u['freq'] . '</changefreq>' . "\n"
                . '    <priority>' . $u['priority'] . '</priority>' . "\n"
                . '  </url>' . "\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /** GET /robots.txt */
    public function robots()
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /account',
            'Disallow: /checkout',
            'Disallow: /seats',
            'Disallow: /showtimes',
            'Disallow: /event-seats',
            'Disallow: /event-checkout',
            'Disallow: /sign-in',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /design-api/',
            'Disallow: /api/',
            '',
            'Sitemap: ' . url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines) . "\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
