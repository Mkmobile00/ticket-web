<?php

namespace Database\Seeders;

use App\Models\CastMember;
use App\Models\Cinema;
use App\Models\City;
use App\Models\Format;
use App\Models\Genre;
use App\Models\Language;
use App\Models\Movie;
use App\Models\Screen;
use App\Models\Showtime;
use App\Models\TicketClass;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Realistic BookMyShow-style Indian dataset: cities, real cinema chains,
 * screens with varied seat layouts, popular movies, cast, showtimes across
 * the next few days, and INR ticket tiers. Resets the catalogue + bookings
 * but preserves user accounts. Re-runnable.
 *
 *   php artisan db:seed --class=BookMyShowSeeder
 */
class BookMyShowSeeder extends Seeder
{
    public function run(): void
    {
        $this->reset();

        $languages = $this->languages();
        $formats = $this->formats();
        $genres = $this->genres();
        $cast = $this->cast();
        $movies = $this->movies($languages, $formats, $genres, $cast);
        $screens = $this->venues();
        $this->schedule($movies, $screens, $languages, $formats);

        $this->command->info('Seeded: ' . City::count() . ' cities, ' . Cinema::count() . ' cinemas, '
            . Screen::count() . ' screens, ' . Movie::count() . ' movies, '
            . Showtime::count() . ' showtimes, ' . TicketClass::count() . ' ticket classes.');
    }

    private function reset(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ([
            'booking_addons', 'booking_seats', 'payments', 'bookings',
            'ticket_classes', 'showtimes', 'screens', 'cinemas',
            'movie_cast', 'language_movie', 'genre_movie', 'format_movie',
            'movie_gallery', 'movies', 'cast_members', 'cities',
        ] as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
            }
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /** @return array<string,Language> keyed by code */
    private function languages(): array
    {
        $out = [];
        foreach ([
            ['Hindi', 'hi'], ['English', 'en'], ['Tamil', 'ta'],
            ['Telugu', 'te'], ['Kannada', 'kn'], ['Malayalam', 'ml'],
        ] as [$name, $code]) {
            $out[$code] = Language::updateOrCreate(['code' => $code], ['name' => $name]);
        }
        return $out;
    }

    /** @return array<string,Format> keyed by name */
    private function formats(): array
    {
        $out = [];
        foreach (['2D', '3D', 'IMAX 2D', 'IMAX 3D', '4DX', 'Dolby Cinema'] as $name) {
            $out[$name] = Format::updateOrCreate(['name' => $name], []);
        }
        return $out;
    }

    /** @return array<string,Genre> keyed by name */
    private function genres(): array
    {
        $out = [];
        foreach (['Action', 'Drama', 'Comedy', 'Thriller', 'Romance', 'Sci-Fi', 'Horror', 'Animation', 'Crime', 'Adventure'] as $name) {
            $out[$name] = Genre::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
        return $out;
    }

    /** @return array<string,CastMember> keyed by name */
    private function cast(): array
    {
        $names = [
            'Shah Rukh Khan', 'Deepika Padukone', 'Ranbir Kapoor', 'Rashmika Mandanna',
            'Prabhas', 'Vijay', 'Allu Arjun', 'Hrithik Roshan', 'Vikrant Massey',
            'Ajay Devgn', 'Atlee', 'Rajkumar Hirani', 'Sandeep Reddy Vanga',
            'Prashanth Neel', 'Nag Ashwin', 'Lokesh Kanagaraj',
        ];
        $out = [];
        foreach ($names as $n) {
            $out[$n] = CastMember::updateOrCreate(['slug' => Str::slug($n)], ['name' => $n]);
        }
        return $out;
    }

    private function movies(array $L, array $F, array $G, array $C): array
    {
        $data = [
            ['Jawan', 'hi', ['Action', 'Thriller'], ['2D', '3D', 'IMAX 2D'], 169, '2023-09-07', 'movie01.jpg', 8.9, 85, [['Shah Rukh Khan', 'Vikram Rathore', 'actor'], ['Atlee', 'Director', 'director']]],
            ['Pathaan', 'hi', ['Action', 'Thriller'], ['2D', 'IMAX 2D'], 146, '2023-01-25', 'movie02.jpg', 8.3, 80, [['Shah Rukh Khan', 'Pathaan', 'actor'], ['Deepika Padukone', 'Rubina', 'actor']]],
            ['Animal', 'hi', ['Action', 'Crime', 'Drama'], ['2D'], 201, '2023-12-01', 'movie03.jpg', 7.8, 78, [['Ranbir Kapoor', 'Ranvijay', 'actor'], ['Sandeep Reddy Vanga', 'Director', 'director']]],
            ['Dunki', 'hi', ['Comedy', 'Drama'], ['2D'], 161, '2023-12-21', 'movie04.jpg', 7.4, 70, [['Shah Rukh Khan', 'Hardy', 'actor'], ['Rajkumar Hirani', 'Director', 'director']]],
            ['Salaar: Part 1', 'te', ['Action', 'Thriller'], ['2D', '3D'], 175, '2023-12-22', 'movie05.jpg', 8.1, 82, [['Prabhas', 'Deva', 'actor'], ['Prashanth Neel', 'Director', 'director']]],
            ['Leo', 'ta', ['Action', 'Thriller'], ['2D'], 164, '2023-10-19', 'movie06.jpg', 7.9, 76, [['Vijay', 'Parthiban', 'actor'], ['Lokesh Kanagaraj', 'Director', 'director']]],
            ['Kalki 2898 AD', 'te', ['Sci-Fi', 'Action', 'Adventure'], ['3D', 'IMAX 3D'], 181, '2024-06-27', 'movie07.jpg', 8.5, 88, [['Prabhas', 'Bhairava', 'actor'], ['Nag Ashwin', 'Director', 'director']]],
            ['Stree 2', 'hi', ['Comedy', 'Horror'], ['2D'], 149, '2024-08-15', 'movie08.jpg', 8.0, 84, [['Rashmika Mandanna', 'Chanda', 'actor']]],
            ['Fighter', 'hi', ['Action', 'Drama'], ['IMAX 2D', '2D'], 166, '2024-01-25', 'movie09.jpg', 7.5, 72, [['Hrithik Roshan', 'Patty', 'actor'], ['Deepika Padukone', 'Minni', 'actor']]],
            ['Pushpa 2: The Rule', 'te', ['Action', 'Drama'], ['2D', '3D'], 200, '2024-12-05', 'movie10.jpg', 8.4, 86, [['Allu Arjun', 'Pushpa Raj', 'actor'], ['Rashmika Mandanna', 'Srivalli', 'actor']]],
            ['12th Fail', 'hi', ['Drama'], ['2D'], 147, '2023-10-27', 'movie11.jpg', 9.0, 92, [['Vikrant Massey', 'Manoj', 'actor']]],
            ['Singham Again', 'hi', ['Action'], ['2D', '3D'], 144, '2024-11-01', 'movie12.jpg', 7.2, 68, [['Ajay Devgn', 'Bajirao Singham', 'actor']]],
        ];

        $movies = [];
        foreach ($data as [$title, $lang, $genreNames, $formatNames, $dur, $release, $poster, $userRating, $tomato, $castRows]) {
            $m = Movie::create([
                'title' => $title,
                'slug' => Str::slug($title),
                'synopsis' => "$title — a blockbuster now showing in cinemas. Book your tickets for an unforgettable big-screen experience.",
                'poster_image' => 'assets/images/movie/' . $poster,
                'banner_image' => 'assets/images/banner/banner' . sprintf('%02d', random_int(1, 10)) . '.jpg',
                'release_date' => $release,
                'duration_minutes' => $dur,
                'rating_tomato' => $tomato,
                'rating_audience' => $tomato - 3,
                'user_rating' => round($userRating / 2, 2), // /5 scale
                'status' => 'now_showing',
            ]);

            $m->languages()->sync([$L[$lang]->id]);
            $m->genres()->sync(collect($genreNames)->map(fn ($g) => $G[$g]->id)->all());
            $m->formats()->sync(collect($formatNames)->map(fn ($f) => $F[$f]->id)->all());

            $order = 0;
            foreach ($castRows as [$person, $character, $role]) {
                if (isset($C[$person])) {
                    $m->cast()->attach($C[$person]->id, [
                        'character_name' => $character,
                        'role' => $role,
                        'order' => $order++,
                    ]);
                }
            }

            $movies[] = ['model' => $m, 'lang' => $lang, 'formats' => $formatNames];
        }
        return $movies;
    }

    /** Create cities -> cinemas -> screens. @return array<int,array{screen:Screen,type:string}> */
    private function venues(): array
    {
        // chain + location templates per city
        $map = [
            'Mumbai' => ['PVR ICON: Infiniti Mall, Andheri', 'INOX: R-City Mall, Ghatkopar', 'Cinépolis: Viviana Mall, Thane'],
            'Delhi-NCR' => ['PVR: Select Citywalk, Saket', 'INOX: Nehru Place', 'Cinépolis: DLF Mall of India, Noida'],
            'Bengaluru' => ['PVR: Forum Mall, Koramangala', 'INOX: Garuda Mall', 'Cinépolis: Orion Mall, Rajajinagar'],
            'Hyderabad' => ['PVR: Inorbit Mall, Cyberabad', 'AMB Cinemas: Gachibowli', 'INOX: GVK One, Banjara Hills'],
            'Chennai' => ['PVR: Grand Mall, Velachery', 'INOX: Express Avenue', 'Sathyam Cinemas: Royapettah'],
            'Kolkata' => ['INOX: South City Mall', 'PVR: Mani Square', 'Cinépolis: Acropolis Mall'],
            'Pune' => ['PVR: Phoenix Marketcity, Viman Nagar', 'INOX: Bund Garden Road', 'Cinépolis: Seasons Mall'],
            'Ahmedabad' => ['PVR: Acropolis Mall', 'INOX: Himalaya Mall', 'Cinépolis: Ahmedabad One Mall'],
        ];

        // screen blueprint: name + layout type
        $screenPlan = [
            ['Audi 1', 'standard'],
            ['Audi 2', 'premium'],
            ['Audi 3 (Recliner)', 'recliner'],
            ['IMAX', 'imax'],
        ];

        $screens = [];
        foreach ($map as $cityName => $cinemaNames) {
            $city = City::create(['name' => $cityName, 'slug' => Str::slug($cityName)]);
            foreach ($cinemaNames as $cinemaName) {
                $cinema = Cinema::create([
                    'name' => $cinemaName,
                    'city_id' => $city->id,
                    'address' => $cinemaName . ', ' . $cityName,
                    'is_active' => true,
                ]);
                // each cinema: 2-3 standard/premium screens + IMAX only at PVR/AMB
                $plans = [$screenPlan[0], $screenPlan[1], $screenPlan[2]];
                if (Str::startsWith($cinemaName, ['PVR', 'AMB'])) {
                    $plans[] = $screenPlan[3];
                }
                foreach ($plans as [$sname, $type]) {
                    $layout = $this->layout($type);
                    $screen = Screen::create([
                        'cinema_id' => $cinema->id,
                        'name' => $sname,
                        'total_seats' => array_sum($layout['seats_per_row']),
                        'seat_layout' => $layout,
                    ]);
                    $screens[] = ['screen' => $screen, 'type' => $type];
                }
            }
        }
        return $screens;
    }

    /** Seat layout templates by screen type. */
    private function layout(string $type): array
    {
        return match ($type) {
            'recliner' => $this->grid(range('A', 'F'), 8),         // 48 recliners
            'premium' => $this->grid(range('A', 'H'), 16),         // 128
            'imax' => $this->grid(range('A', 'N'), 26),            // 364
            default => $this->grid(range('A', 'J'), 20),           // standard 200
        };
    }

    private function grid(array $rows, int $perRow): array
    {
        return ['rows' => array_values($rows), 'seats_per_row' => array_fill(0, count($rows), $perRow)];
    }

    /** Create showtimes + ticket classes for each movie across a sample of screens/days/times. */
    private function schedule(array $movies, array $screens, array $L, array $F): void
    {
        $times = ['10:00:00', '13:15:00', '16:30:00', '19:45:00', '22:30:00'];
        $today = Carbon::today();

        foreach ($movies as $mv) {
            $movie = $mv['model'];
            $primaryLang = $L[$mv['lang']];

            // 4 distinct screens per movie, spread across venues
            $picked = collect($screens)->shuffle()->take(4);

            foreach ($picked as $entry) {
                $screen = $entry['screen'];
                $type = $entry['type'];
                // pick a format the movie supports that suits the screen
                $format = $this->pickFormat($mv['formats'], $type, $F);

                for ($d = 0; $d < 4; $d++) {                 // next 4 days
                    foreach (collect($times)->shuffle()->take(3) as $time) {  // 3 random slots/day
                        $showtime = Showtime::create([
                            'movie_id' => $movie->id,
                            'screen_id' => $screen->id,
                            'language_id' => $primaryLang->id,
                            'format_id' => $format->id,
                            'show_date' => $today->copy()->addDays($d)->toDateString(),
                            'show_time' => $time,
                            'available_seats' => $screen->total_seats,
                            'status' => 'active',
                        ]);
                        $this->ticketClasses($showtime, $type, $screen->seat_layout['rows']);
                    }
                }
            }
        }
    }

    private function pickFormat(array $movieFormats, string $screenType, array $F): Format
    {
        if ($screenType === 'imax') {
            foreach (['IMAX 3D', 'IMAX 2D'] as $f) {
                if (in_array($f, $movieFormats, true)) return $F[$f];
            }
        }
        // otherwise first non-IMAX format the movie has, default 2D
        foreach ($movieFormats as $f) {
            if (! Str::startsWith($f, 'IMAX')) return $F[$f];
        }
        return $F['2D'];
    }

    /** INR ticket tiers per screen type, mapped to seat rows. */
    private function ticketClasses(Showtime $st, string $type, array $rows): void
    {
        $half = (int) ceil(count($rows) / 2);
        $front = array_slice($rows, 0, $half);
        $back = array_slice($rows, $half);

        $tiers = match ($type) {
            'recliner' => [['Recliner', 500, $rows]],
            'premium' => [['Executive', 220, $front], ['Royal', 380, $back]],
            'imax' => [['IMAX', 450, $front], ['IMAX Prime', 600, $back]],
            default => [['Classic', 190, $front], ['Prime', 260, $back]],
        };

        foreach ($tiers as [$name, $price, $tierRows]) {
            TicketClass::create([
                'showtime_id' => $st->id,
                'name' => $name,
                'price' => $price,
                'seat_rows' => array_values($tierRows),
            ]);
        }
    }
}
