<?php

namespace App\Http\Controllers\Admin;

use App\Models\DeviceToken;
use App\Models\Event;
use App\Models\Movie;
use App\Models\Sport;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Http\Request;

class NotificationController extends AdminController
{
    public function __construct(private FcmService $fcm) {}

    public function index()
    {
        return view('admin.notifications.index', [
            'title' => 'Push Notifications',
            'configured' => $this->fcm->isConfigured(),
            'deviceCount' => DeviceToken::count(),
            'userCount' => DeviceToken::distinct('user_id')->count('user_id'),
            'movies' => Movie::orderBy('title')->get(['slug', 'title']),
            'events' => Event::orderBy('title')->get(['slug', 'title']),
            'sports' => Sport::orderBy('title')->get(['slug', 'title']),
        ]);
    }

    /** Resolve the chosen "open in app" target to a router path. */
    private function resolveLink(Request $request): ?string
    {
        return match ($request->input('link_type')) {
            'home' => '/home',
            'movies' => '/movies',
            'events' => '/events',
            'sports' => '/sports',
            'bookings' => '/bookings',
            'movie' => $request->filled('link_movie') ? '/movies/' . $request->input('link_movie') : null,
            'event' => $request->filled('link_event') ? '/events/' . $request->input('link_event') : null,
            'sport' => $request->filled('link_sport') ? '/sports/' . $request->input('link_sport') : null,
            default => null,
        };
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'body' => 'required|string|max:500',
            'target' => 'required|in:all,email',
            'email' => 'nullable|required_if:target,email|email',
            // Accept any string; filenames may contain spaces — we encode them below.
            'image' => 'nullable|string|max:500',
        ]);

        if (! $this->fcm->isConfigured()) {
            return back()->withInput()->withErrors([
                'title' => 'Firebase is not configured yet. Add the service-account JSON (see storage/app/firebase) first.',
            ]);
        }

        $payload = ['type' => 'broadcast'];
        $link = $this->resolveLink($request);
        if ($link) {
            $payload['link'] = $link;
        }
        // Encode spaces so FCM/the device can fetch images whose filenames have spaces.
        $image = ! empty($data['image']) ? str_replace(' ', '%20', trim($data['image'])) : null;

        if ($data['target'] === 'email') {
            $user = User::where('email', $data['email'])->first();
            if (! $user) {
                return back()->withInput()->withErrors(['email' => 'No user with that email.']);
            }
            $sent = $this->fcm->sendToUser($user, $data['title'], $data['body'], $payload, $image);
            $msg = "Sent to {$user->email} ({$sent} device(s)).";
        } else {
            $sent = $this->fcm->broadcast($data['title'], $data['body'], $payload, $image);
            $msg = "Broadcast delivered to {$sent} device(s).";
        }

        return back()->with('status', $msg);
    }
}
