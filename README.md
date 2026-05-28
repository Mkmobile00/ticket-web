# Boleto - Online Ticket Booking Platform

A Laravel-based ticket booking platform converted from the Boleto static HTML template. Admins manage movies, cinemas, showtimes, events, sports, blog content, bookings, and site settings; customers browse listings, view detail pages, and book tickets.

## Tech stack

- Laravel 11 (PHP 8.2+)
- MySQL / MariaDB
- Blade templating
- Bootstrap 4 (frontend) + Bootstrap 5 (admin)
- Built-in Laravel auth (no Breeze/Jetstream)

## Setup

```bash
# 1. Clone & install dependencies
composer install
npm install && npm run build   # optional - all assets ship as static files in public/assets

# 2. Configure environment
cp .env.example .env
php artisan key:generate

# Edit .env and set DB_DATABASE, DB_USERNAME, DB_PASSWORD to your local MySQL.

# 3. Create database, then run migrations + seeders
php artisan migrate:fresh --seed

# 4. Storage symlink (for image uploads from admin)
php artisan storage:link

# 5. Serve
php artisan serve
# or use Laragon's built-in vhost: http://buleto.test
```

## Default logins

| Role     | Email                | Password   |
|----------|----------------------|------------|
| Admin    | `admin@buleto.test`  | `password` |
| Customer | `user@buleto.test`   | `password` |

Admin lands at `/admin`; customers at `/`.

## URL map

### Public (frontend)
- `/` &mdash; homepage with featured movies/events/sports
- `/movies`, `/movies?view=list` &mdash; movie listing (grid / list)
- `/movies/{slug}` &mdash; movie detail
- `/movies/{slug}/showtimes` &mdash; showtimes for a movie
- `/showtimes/{id}/seats` &mdash; seat plan
- `/checkout/movie/{booking}` &mdash; movie booking checkout *(auth)*
- `/events`, `/events/{slug}`, `/events/{slug}/tickets`, `/checkout/event/{booking}`
- `/speakers/{id}` &mdash; event speaker profile
- `/sports`, `/sports/{slug}`, `/sports/{slug}/tickets`, `/checkout/sport/{booking}`
- `/blog`, `/blog/{slug}` &mdash; with category/tag filters and comments
- `/about`, `/contact`, `/apps`, `/popcorn`
- `/login`, `/register`, `/logout`
- `/newsletter/subscribe` (POST)

### Admin (`/admin/*`, `auth + admin` middleware)
- `/admin` &mdash; dashboard with counts + recent bookings/messages
- Resource CRUD at: `movies`, `cinemas`, `screens`, `showtimes`, `ticket-classes`, `promo-codes`, `popcorn-items`, `events`, `event-categories`, `speakers`, `sports`, `sport-categories`, `blog-posts`, `blog-categories`, `blog-tags`, `users`, `cities`, `banners`, `faqs`, `partners`
- `/admin/blog-comments` &mdash; moderate comments (approve / delete)
- `/admin/bookings` &mdash; view, refund, delete
- `/admin/contact-messages` &mdash; view + delete contact form messages
- `/admin/newsletter` &mdash; manage subscribers
- `/admin/settings` &mdash; key/value site settings

## Project structure

```
app/
  Http/
    Controllers/
      AuthController, HomeController, MovieController, EventController,
      SportController, BlogController, BookingController, CheckoutController,
      ContactController, NewsletterController, PageController, PopcornController,
      SpeakerController
      Admin/
        AdminController (base with smart-default columns/fields/rules)
        DashboardController, MovieController, CinemaController, ScreenController,
        ShowtimeController, TicketClassController, PromoCodeController,
        PopcornItemController, EventController, EventCategoryController,
        EventSpeakerController, SportController, SportCategoryController,
        BlogPostController, BlogCategoryController, BlogTagController,
        BlogCommentController, BookingController, ContactMessageController,
        NewsletterController, UserController, CityController, BannerController,
        FaqController, PartnerController, SettingController
    Middleware/
      AdminMiddleware  (registered as 'admin' alias in bootstrap/app.php)
  Models/
    Movie, Genre, Language, Format, CastMember, MovieGallery,
    Cinema, Screen, Showtime, TicketClass, PromoCode, PopcornItem,
    Event, EventCategory, EventSpeaker, EventTicket, EventStat,
    Sport, SportCategory, SportTicket,
    BlogPost, BlogCategory, BlogTag, BlogPostImage, BlogComment,
    Booking (polymorphic), BookingSeat, BookingAddon,
    City, Setting, ContactMessage, NewsletterSubscriber, SidebarBanner,
    Faq, Partner, User

resources/
  views/
    layouts/
      frontend.blade.php  (preloader + header + content + newsletter/footer + scripts)
      auth.blade.php      (preloader + content + scripts; for login/register/404)
    partials/
      header.blade.php, footer.blade.php, newsletter.blade.php, breadcrumb.blade.php
    components/           (Blade x-components)
      movie-card, movie-list-card, event-card, sport-card,
      blog-card, blog-post-item, speaker-card
    home.blade.php
    movies/{index,list,show,showtimes}.blade.php
    events/{index,show,tickets}.blade.php
    sports/{index,show,tickets}.blade.php
    blog/{index,show}.blade.php
    speakers/show.blade.php
    bookings/seats.blade.php
    checkout/{movie,event,sport}.blade.php
    pages/{about,contact,apps}.blade.php
    popcorn/index.blade.php
    auth/{login,register}.blade.php
    errors/404.blade.php
    admin/
      layouts/admin.blade.php  (sidebar + topbar shell)
      dashboard.blade.php
      crud/{index,form,show}.blade.php  (generic resource scaffolds)
      bookings/{index,show}.blade.php
      blog-comments/index.blade.php
      contact-messages/{index,show}.blade.php
      newsletter/index.blade.php
      settings/index.blade.php
    frontend/  (original .html files kept as reference, can be deleted)

routes/
  web.php   (frontend + auth)
  admin.php (loaded from web.php; auth+admin protected, /admin prefix)

database/
  migrations/  (47 migrations covering all tables)
  seeders/     (23 seeders covering users, cities, movies, showtimes, events, sports, blog, settings, etc.)
```

## Seed data sizes

After `php artisan migrate:fresh --seed` you should see roughly:
- 7 users (1 admin, 1 customer, 5 blog authors)
- 5 cities, 8 cinemas, 15 screens
- 12 movies with cast/genres/languages/formats
- 252 showtimes (~21 per movie over the next 7 days) + 3 ticket classes per showtime
- 8 popcorn items
- 8 events (across 8 categories) + speakers
- 6 sports events
- 10 blog posts + categories + tags
- 9 settings, plus FAQs/banners/partners/promo codes

## Conventions

- Slug-based URLs: `/movies/venus`, `/events/digital-marketing-conference-2020`
- Pagination on all listings (`->paginate(...)->links()`)
- Polymorphic `bookings` table (`bookable_type` + `bookable_id`) handles movie/event/sport bookings
- Admin CRUD uses smart defaults: `Admin\AdminController` introspects `$fillable` + DB column types to render a generic form/table. Override `columns()`, `fields()`, or `rules()` per controller for custom UI.
- Image uploads (admin forms with file inputs) land in `storage/app/public/...` &mdash; served via the `storage:link` symlink as `/storage/...`. Components prefer `asset('storage/...')` for uploaded files and fall back to `asset('assets/...')` for the static template images.
- Status enums are entity-specific: movies use `now_showing` / `coming_soon`; events & sports use `upcoming` / `live`.

## What's intentionally left as a follow-up

- Detail views (`movies/show`, `events/show`, `sports/show`, `blog/show`, `speakers/show`) currently render with the original template's static sample text in the body. The controller passes the real `$movie`/`$event`/etc. model, but the surrounding hardcoded sections (cast lists, related posts, comment threads, etc.) need to be templated against the model fields when you want them live.
- Admin generic forms cover scalar fields. Many-to-many relations (movie genres/languages/formats, event speakers, blog tags), JSON fields (`screens.seat_layout`, `ticket_classes.seat_rows`), and image uploads need per-resource form overrides &mdash; extend the relevant `Admin\*Controller` and provide a tailored view.
- Booking flow stops at `BookingController@storeSeats` &mdash; payment integration is a stub.

## Useful artisan commands

```bash
php artisan route:list                       # all registered routes
php artisan migrate:fresh --seed             # nuke + reseed DB
php artisan db:seed --class=MovieSeeder      # re-run a single seeder
php artisan tinker                           # poke at models
```
