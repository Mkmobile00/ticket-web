<?php

namespace App\Models;

use App\Models\Concerns\Seatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Showtime extends Model
{
    use HasFactory, Seatable;

    protected $fillable = ['movie_id', 'screen_id', 'language_id', 'format_id', 'show_date', 'show_time', 'available_seats', 'status'];

    protected $casts = [
        'show_date' => 'date',
    ];

    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    public function screen()
    {
        return $this->belongsTo(Screen::class);
    }

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function format()
    {
        return $this->belongsTo(Format::class);
    }

    public function ticketClasses()
    {
        return $this->hasMany(TicketClass::class);
    }

    /** Seat map comes from the screen; price tiers from ticket classes. */
    public function seatLayoutArray(): array
    {
        $l = $this->screen?->seat_layout ?: [];
        if (is_string($l)) {
            $l = json_decode($l, true) ?: [];
        }
        return ['rows' => $l['rows'] ?? [], 'seats_per_row' => $l['seats_per_row'] ?? [], 'grid' => $l['grid'] ?? null];
    }

    public function seatTiers()
    {
        return $this->ticketClasses->map(fn ($t) => [
            'id' => $t->id, 'name' => $t->name, 'price' => (float) $t->price, 'rows' => (array) $t->seat_rows,
        ]);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Human-readable label for admin dropdowns/lists, e.g.
     * "Jawan — PVR: Forum Mall (IMAX) · Wed 28 May 7:45 PM".
     */
    public function getLabelAttribute(): string
    {
        $movie = $this->movie?->title ?? 'Movie';
        $cinema = $this->screen?->cinema?->name ?? 'Cinema';
        $screen = $this->screen?->name ? ' (' . $this->screen->name . ')' : '';
        $date = \Illuminate\Support\Carbon::parse($this->show_date)->format('D d M');
        $time = \Illuminate\Support\Carbon::parse($this->show_time)->format('g:i A');
        return "{$movie} — {$cinema}{$screen} · {$date} {$time}";
    }
}
