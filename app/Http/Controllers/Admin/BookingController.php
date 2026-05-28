<?php

namespace App\Http\Controllers\Admin;

use App\Models\Booking;

class BookingController extends AdminController
{
    public function index()
    {
        $bookings = Booking::with(['user', 'bookable'])->latest()->paginate(15);
        return view('admin.bookings.index', compact('bookings'));
    }

    public function show($id)
    {
        $booking = Booking::with(['user', 'bookable', 'seats.ticketClass', 'addons.popcornItem', 'promoCode'])->findOrFail($id);
        return view('admin.bookings.show', compact('booking'));
    }

    public function destroy($id)
    {
        Booking::findOrFail($id)->delete();
        return back()->with('status', 'Booking deleted.');
    }

    public function refund($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->update(['status' => 'refunded']);
        return back()->with('status', 'Booking #' . $booking->id . ' marked refunded.');
    }
}
