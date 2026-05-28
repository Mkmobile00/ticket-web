<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\SeatBookingService;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(private SeatBookingService $booker) {}

    public function index(Request $request)
    {
        $query = Event::whereIn('status', ['upcoming', 'active', 'live'])
            ->with('categories');

        if ($search = $request->query('search')) {
            $query->where('title', 'like', "%{$search}%");
        }
        if ($date = $request->query('date')) {
            $query->whereDate('event_date', $date);
        }
        if ($category = $request->query('category')) {
            $query->whereHas('categories', fn ($q) => $q->where('event_categories.slug', $category)->orWhere('event_categories.id', $category));
        }
        if ($city = $request->query('city')) {
            // Events table has no city_id; filter by address text containing city name as a fallback
            $cityName = \App\Models\City::find($city)?->name;
            if ($cityName) {
                $query->where('address', 'like', "%{$cityName}%");
            }
        }

        $events = $query->orderBy('event_date')->paginate(9)->withQueryString();
        return view('events.index', compact('events'));
    }

    public function show(Event $event)
    {
        $event->load(['categories', 'speakers', 'tickets', 'stats']);
        $related = Event::where('id', '!=', $event->id)->whereIn('status', ['upcoming', 'active', 'live'])->orderBy('event_date')->take(3)->get();
        return view('events.show', compact('event', 'related'));
    }

    public function tickets(Event $event)
    {
        $event->load('tickets');
        return view('bookings.seat-plan', [
            'seatable' => $event,
            'kind' => 'event',
            'title' => $event->title,
            'subtitle' => $event->address ?? $event->organizer ?? '',
            'dateLine' => \Carbon\Carbon::parse($event->event_date)->format('D, M d Y')
                . ($event->start_time ? ' · ' . \Carbon\Carbon::parse($event->start_time)->format('H:i') : ''),
            'bannerImg' => $this->banner($event->banner_image, 'banner07.jpg'),
            'storeUrl' => route('events.tickets.store', $event->slug),
        ]);
    }

    public function storeTickets(Request $request, Event $event)
    {
        $data = $request->validate([
            'seats' => 'required|array|min:1|max:10',
            'seats.*' => 'string|regex:/^[A-Za-z]{1,2}-\d{1,3}$/',
        ]);

        $booking = $this->booker->reserve(
            $event->load('tickets'),
            $data['seats'],
            (int) auth()->id(),
            'sess:' . $request->session()->getId(),
        );

        return redirect()->route('checkout.event', $booking);
    }

    private function banner(?string $img, string $fallback): string
    {
        if (! $img) {
            return asset('assets/images/banner/' . $fallback);
        }
        return str_starts_with($img, 'assets/') ? asset($img) : asset('storage/' . $img);
    }
}
