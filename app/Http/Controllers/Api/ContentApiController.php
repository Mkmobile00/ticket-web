<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\City;
use App\Models\ContactMessage;
use App\Models\Event;
use App\Models\Faq;
use App\Models\Movie;
use App\Models\NewsletterSubscriber;
use App\Models\Partner;
use App\Models\PopcornItem;
use App\Models\PromoCode;
use App\Models\SidebarBanner;
use App\Models\Sport;
use Illuminate\Http\Request;

/** Public content: home feed, search, popcorn, promo, faqs, blog, partners, contact, newsletter. */
class ContentApiController extends Controller
{
    private function img(?string $p, string $fallback = 'assets/images/banner/banner04.jpg'): string
    {
        $p = $p ?: $fallback;
        return str_starts_with($p, 'http') ? $p
            : (str_starts_with($p, 'assets/') ? asset($p) : asset('storage/' . ltrim($p, '/')));
    }

    private function movieCard(Movie $m): array
    {
        return [
            'id' => $m->id, 'title' => $m->title, 'slug' => $m->slug,
            'poster_image' => $this->img($m->poster_image),
            'user_rating' => (float) $m->user_rating,
            'genres' => $m->genres->pluck('name'),
        ];
    }

    /** GET /api/v1/home — one call for the home screen. */
    public function home()
    {
        return response()->json([
            'banners' => SidebarBanner::where('is_active', true)->orderBy('position')->get()
                ->map(fn ($b) => ['title' => $b->title, 'image' => $this->img($b->image), 'link' => $b->link]),
            'now_showing' => Movie::whereIn('status', ['now_showing', 'active'])->with('genres:id,name')->latest()->take(8)->get()
                ->map(fn ($m) => $this->movieCard($m)),
            'events' => Event::whereIn('status', ['upcoming', 'active', 'live'])->orderBy('event_date')->take(4)->get()
                ->map(fn ($e) => ['id' => $e->id, 'title' => $e->title, 'slug' => $e->slug, 'banner_image' => $this->img($e->banner_image), 'date' => optional($e->event_date)->toDateString()]),
            'sports' => Sport::whereIn('status', ['upcoming', 'active', 'live'])->orderBy('sport_date')->take(4)->get()
                ->map(fn ($s) => ['id' => $s->id, 'title' => $s->title, 'slug' => $s->slug, 'banner_image' => $this->img($s->banner_image), 'date' => optional($s->sport_date)->toDateString()]),
            'cities' => City::orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    /** GET /api/v1/search?q= — across movies, events, sports. */
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (strlen($q) < 2) {
            return response()->json(['movies' => [], 'events' => [], 'sports' => []]);
        }
        return response()->json([
            'movies' => Movie::where('title', 'like', "%{$q}%")->with('genres:id,name')->take(10)->get()->map(fn ($m) => $this->movieCard($m)),
            'events' => Event::where('title', 'like', "%{$q}%")->take(10)->get(['id', 'title', 'slug']),
            'sports' => Sport::where('title', 'like', "%{$q}%")->orWhere('team_home', 'like', "%{$q}%")->orWhere('team_away', 'like', "%{$q}%")->take(10)->get(['id', 'title', 'slug', 'team_home', 'team_away']),
        ]);
    }

    /** GET /api/v1/popcorn — food & beverage add-ons. */
    public function popcorn()
    {
        return response()->json(['data' => PopcornItem::where('is_active', true)->get()
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'description' => $p->description, 'price' => (float) $p->price, 'image' => $this->img($p->image)])]);
    }

    /** POST /api/v1/promo/validate  { code, amount } */
    public function validatePromo(Request $request)
    {
        $data = $request->validate(['code' => 'required|string', 'amount' => 'required|numeric|min:0']);
        $promo = PromoCode::where('code', $data['code'])->where('is_active', true)->first();

        $now = now();
        $invalid = ! $promo
            || ($promo->valid_from && $promo->valid_from->gt($now))
            || ($promo->valid_to && $promo->valid_to->lt($now))
            || ($promo->usage_limit && $promo->usage_count >= $promo->usage_limit);

        if ($invalid) {
            return response()->json(['valid' => false, 'message' => 'Invalid or expired promo code.'], 422);
        }

        $amount = (float) $data['amount'];
        $discount = $promo->discount_type === 'percentage'
            ? round($amount * ((float) $promo->discount_value) / 100, 2)
            : min($amount, (float) $promo->discount_value);

        return response()->json([
            'valid' => true,
            'code' => $promo->code,
            'discount' => $discount,
            'final_amount' => round($amount - $discount, 2),
            'message' => 'Promo applied.',
        ]);
    }

    /** GET /api/v1/faqs?page=movie|event|... */
    public function faqs(Request $request)
    {
        $faqs = Faq::where('is_active', true)
            ->when($request->query('page'), fn ($q, $p) => $q->where('page_type', $p))
            ->orderBy('order')->get(['question', 'answer', 'page_type']);
        return response()->json(['data' => $faqs]);
    }

    /** GET /api/v1/blog  &  GET /api/v1/blog/{slug} */
    public function blog()
    {
        $posts = BlogPost::whereNotNull('published_at')->with('author:id,name')->latest('published_at')->paginate(10);
        $posts->getCollection()->transform(fn ($p) => [
            'id' => $p->id, 'title' => $p->title, 'slug' => $p->slug, 'excerpt' => $p->excerpt,
            'thumbnail' => $this->img($p->thumbnail), 'author' => $p->author->name ?? null,
            'published_at' => optional($p->published_at)->toDateString(), 'views' => $p->views,
        ]);
        return response()->json($posts);
    }

    public function blogShow(BlogPost $post)
    {
        $post->load('author:id,name', 'categories:id,name', 'tags:id,name');
        return response()->json(['data' => [
            'id' => $post->id, 'title' => $post->title, 'slug' => $post->slug,
            'content' => $post->content, 'thumbnail' => $this->img($post->thumbnail),
            'author' => $post->author->name ?? null, 'published_at' => optional($post->published_at)->toDateString(),
            'categories' => $post->categories->pluck('name'), 'tags' => $post->tags->pluck('name'),
        ]]);
    }

    /** GET /api/v1/partners */
    public function partners()
    {
        return response()->json(['data' => Partner::where('is_active', true)->get()
            ->map(fn ($p) => ['name' => $p->name, 'logo' => $this->img($p->logo), 'url' => $p->url])]);
    }

    /** POST /api/v1/contact  { name, email, subject, message } */
    public function contact(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:160',
            'subject' => 'nullable|string|max:160',
            'message' => 'required|string|max:5000',
        ]);
        ContactMessage::create($data);
        return response()->json(['message' => 'Thanks! Your message has been received.'], 201);
    }

    /** POST /api/v1/newsletter  { email } */
    public function newsletter(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:160']);
        NewsletterSubscriber::firstOrCreate(['email' => $data['email']], ['subscribed_at' => now()]);
        return response()->json(['message' => 'Subscribed!'], 201);
    }
}
