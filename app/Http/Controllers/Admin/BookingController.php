<?php

namespace App\Http\Controllers\Admin;

use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends AdminController
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $query = Booking::with(['user', 'bookable'])->latest();

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                if (ctype_digit($q)) {
                    $w->orWhere('id', (int) $q);
                }
                $w->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%"));
            });
        }
        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        $bookings = $query->paginate(15)->withQueryString();
        $statuses = Booking::query()->select('status')->distinct()->pluck('status')->filter()->values();

        return view('admin.bookings.index', compact('bookings', 'q', 'status', 'statuses'));
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
