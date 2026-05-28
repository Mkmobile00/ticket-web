<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
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
        return view('events.tickets', compact('event'));
    }

    public function storeTickets(Event $event)
    {
        // Stub for now — full booking flow would go here
        return redirect()->route('events.show', $event)->with('status', 'Tickets reserved (demo).');
    }
}
