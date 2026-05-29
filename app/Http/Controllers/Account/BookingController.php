<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Mail\BookingCancelledMail;
use App\Models\Booking;
use App\Models\Showtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

        // Load relations for the email BEFORE we delete the seats.
        $booking->load(['seats', 'user', 'showtime.movie', 'bookable']);

        DB::transaction(function () use ($booking) {
            $count = $booking->seats->count();
            // Free the seats: deleting booking_seats releases the unique-seat slot
            // so the seats become bookable again, and restore the showtime counter.
            if ($count > 0 && $booking->showtime_id) {
                Showtime::where('id', $booking->showtime_id)->increment('available_seats', $count);
            }
            $booking->seats()->delete();
            $booking->update(['status' => 'cancelled']);
        });

        try {
            if ($booking->user?->email) {
                Mail::to($booking->user->email)->send(new BookingCancelledMail($booking));
            }
        } catch (\Throwable $e) {
            Log::warning('Cancellation email failed', ['booking' => $booking->id, 'error' => $e->getMessage()]);
        }

        return back()->with('status', 'Booking #' . $booking->id . ' has been cancelled and seats released.');
    }
}
