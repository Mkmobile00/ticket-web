<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Booking;

class AccountController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        $bookings = Booking::where('user_id', $user->id)
            ->with(['bookable', 'seats'])
            ->latest('booked_at')
            ->take(5)
            ->get();
        $stats = [
            'total' => Booking::where('user_id', $user->id)->count(),
            'confirmed' => Booking::where('user_id', $user->id)->where('status', 'confirmed')->count(),
            'pending' => Booking::where('user_id', $user->id)->where('status', 'pending')->count(),
            'cancelled' => Booking::where('user_id', $user->id)->whereIn('status', ['cancelled', 'refunded'])->count(),
        ];
        return view('account.dashboard', compact('bookings', 'stats'));
    }
}
