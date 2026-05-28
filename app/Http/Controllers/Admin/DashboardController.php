<?php

namespace App\Http\Controllers\Admin;

use App\Models\BlogPost;
use App\Models\Booking;
use App\Models\ContactMessage;
use App\Models\Event;
use App\Models\Movie;
use App\Models\NewsletterSubscriber;
use App\Models\Sport;
use App\Models\User;

class DashboardController extends AdminController
{
    public function index()
    {
        $stats = [
            'movies' => Movie::count(),
            'events' => Event::count(),
            'sports' => Sport::count(),
            'blog_posts' => BlogPost::count(),
            'users' => User::count(),
            'bookings' => Booking::count(),
            'messages' => ContactMessage::count(),
            'subscribers' => NewsletterSubscriber::count(),
        ];

        $recentBookings = Booking::with(['user', 'bookable'])->latest()->take(5)->get();
        $recentMessages = ContactMessage::latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentBookings', 'recentMessages'));
    }
}
