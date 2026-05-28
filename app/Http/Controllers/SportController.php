<?php

namespace App\Http\Controllers;

use App\Models\Sport;
use App\Services\SeatBookingService;
use Illuminate\Http\Request;

class SportController extends Controller
{
    public function __construct(private SeatBookingService $booker) {}

    public function index(Request $request)
    {
        $query = Sport::whereIn('status', ['upcoming', 'active', 'live'])
            ->with(['categories', 'city']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('team_home', 'like', "%{$search}%")
                  ->orWhere('team_away', 'like', "%{$search}%");
            });
        }
        if ($date = $request->query('date')) {
            $query->whereDate('sport_date', $date);
        }
        if ($city = $request->query('city')) {
            $query->where('city_id', $city);
        }
        if ($category = $request->query('category')) {
            $query->whereHas('categories', fn ($q) => $q->where('sport_categories.slug', $category)->orWhere('sport_categories.id', $category));
        }

        $sports = $query->orderBy('sport_date')->paginate(9)->withQueryString();
        return view('sports.index', compact('sports'));
    }

    public function show(Sport $sport)
    {
        $sport->load(['categories', 'tickets', 'city']);
        $related = Sport::where('id', '!=', $sport->id)->whereIn('status', ['upcoming', 'active', 'live'])->orderBy('sport_date')->take(3)->get();
        return view('sports.show', compact('sport', 'related'));
    }

    public function tickets(Sport $sport)
    {
        $sport->load('tickets');
        $matchup = $sport->team_home && $sport->team_away ? $sport->team_home . ' vs ' . $sport->team_away : $sport->title;
        return view('bookings.seat-plan', [
            'seatable' => $sport,
            'kind' => 'sport',
            'title' => $matchup,
            'subtitle' => $sport->venue ?? '',
            'dateLine' => \Carbon\Carbon::parse($sport->sport_date)->format('D, M d Y')
                . ($sport->start_time ? ' · ' . \Carbon\Carbon::parse($sport->start_time)->format('H:i') : ''),
            'bannerImg' => $this->banner($sport->banner_image, 'banner10.jpg'),
            'storeUrl' => route('sports.tickets.store', $sport->slug),
        ]);
    }

    public function storeTickets(Request $request, Sport $sport)
    {
        $data = $request->validate([
            'seats' => 'required|array|min:1|max:10',
            'seats.*' => 'string|regex:/^[A-Za-z]{1,2}-\d{1,3}$/',
        ]);

        $booking = $this->booker->reserve(
            $sport->load('tickets'),
            $data['seats'],
            (int) auth()->id(),
            'sess:' . $request->session()->getId(),
        );

        return redirect()->route('checkout.sport', $booking);
    }

    private function banner(?string $img, string $fallback): string
    {
        if (! $img) {
            return asset('assets/images/banner/' . $fallback);
        }
        return str_starts_with($img, 'assets/') ? asset($img) : asset('storage/' . $img);
    }
}
