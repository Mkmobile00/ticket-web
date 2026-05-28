<?php

namespace App\Http\Controllers;

use App\Models\Showtime;
use App\Services\SeatBookingService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private SeatBookingService $booker) {}

    public function seats(Showtime $showtime)
    {
        $showtime->load(['movie', 'screen.cinema', 'ticketClasses', 'language', 'format']);

        return view('bookings.seat-plan', [
            'seatable' => $showtime,
            'kind' => 'showtime',
            'title' => $showtime->movie->title ?? 'Showtime',
            'subtitle' => ($showtime->screen->cinema->name ?? '') . ($showtime->screen->name ? ' — ' . $showtime->screen->name : ''),
            'dateLine' => \Carbon\Carbon::parse($showtime->show_date)->format('D, M d Y') . ' · ' . \Carbon\Carbon::parse($showtime->show_time)->format('H:i'),
            'bannerImg' => $this->banner($showtime->movie->banner_image ?? null, 'banner04.jpg'),
            'storeUrl' => route('showtimes.seats.store', $showtime->id),
        ]);
    }

    public function storeSeats(Request $request, Showtime $showtime)
    {
        $data = $request->validate([
            'seats' => 'required|array|min:1|max:10',
            'seats.*' => 'string|regex:/^[A-Za-z]{1,2}-\d{1,3}$/',
        ]);

        $booking = $this->booker->reserve(
            $showtime->load('ticketClasses', 'movie'),
            $data['seats'],
            (int) auth()->id(),
            'sess:' . $request->session()->getId(),
        );

        return redirect()->route('checkout.movie', $booking);
    }

    private function banner(?string $img, string $fallback): string
    {
        if (! $img) {
            return asset('assets/images/banner/' . $fallback);
        }
        return str_starts_with($img, 'assets/') ? asset($img) : asset('storage/' . $img);
    }
}
