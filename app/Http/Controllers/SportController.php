<?php

namespace App\Http\Controllers;

use App\Models\Sport;
use Illuminate\Http\Request;

class SportController extends Controller
{
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
        return view('sports.tickets', compact('sport'));
    }

    public function storeTickets(Sport $sport)
    {
        return redirect()->route('sports.show', $sport)->with('status', 'Tickets reserved (demo).');
    }
}
