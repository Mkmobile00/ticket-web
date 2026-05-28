<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::where('user_id', auth()->id())
            ->with(['bookable', 'seats.ticketClass.showtime.screen.cinema'])
            ->latest('booked_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $bookings = $query->paginate(10)->withQueryString();
        return view('account.bookings', compact('bookings'));
    }

    public function show(Booking $booking)
    {
        abort_unless($booking->user_id === auth()->id(), 403);
        $booking->load(['bookable', 'seats.ticketClass.showtime.screen.cinema', 'addons.popcornItem']);
        return view('account.booking-show', compact('booking'));
    }

    public function cancel(Booking $booking)
    {
        abort_unless($booking->user_id === auth()->id(), 403);
        if (in_array($booking->status, ['cancelled', 'refunded'])) {
            return back()->with('status', 'Booking already cancelled.');
        }
        $booking->update(['status' => 'cancelled']);
        return back()->with('status', 'Booking #' . $booking->id . ' has been cancelled.');
    }
}
