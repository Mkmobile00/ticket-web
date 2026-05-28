<?php

namespace App\Http\Controllers;

use App\Models\EventSpeaker;

class SpeakerController extends Controller
{
    public function show($identifier)
    {
        $speaker = EventSpeaker::where('id', $identifier)->orWhere('slug', $identifier)->firstOrFail();
        $speaker->load(['events' => fn ($q) => $q->latest('event_date')]);
        return view('speakers.show', compact('speaker'));
    }
}
