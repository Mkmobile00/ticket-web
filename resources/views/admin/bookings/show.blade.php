@extends('admin.layouts.admin')
@section('title', 'Booking #' . $booking->id)
@section('page-title', 'Booking #' . $booking->id)

@section('content')
<div class="row g-3">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-white"><strong>Booking details</strong></div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">User</dt><dd class="col-sm-8">{{ $booking->user->name ?? '-' }} ({{ $booking->user->email ?? '-' }})</dd>
                    <dt class="col-sm-4">Bookable</dt><dd class="col-sm-8">{{ class_basename($booking->bookable_type) }} #{{ $booking->bookable_id }}</dd>
                    <dt class="col-sm-4">Total</dt><dd class="col-sm-8">${{ number_format($booking->total_amount, 2) }}</dd>
                    <dt class="col-sm-4">Discount</dt><dd class="col-sm-8">${{ number_format($booking->discount_amount ?? 0, 2) }}</dd>
                    <dt class="col-sm-4">Promo</dt><dd class="col-sm-8">{{ $booking->promoCode->code ?? '-' }}</dd>
                    <dt class="col-sm-4">Payment</dt><dd class="col-sm-8">{{ $booking->payment_method ?? '-' }} / {{ $booking->transaction_id ?? '-' }}</dd>
                    <dt class="col-sm-4">Status</dt><dd class="col-sm-8"><span class="badge bg-secondary">{{ $booking->status }}</span></dd>
                    <dt class="col-sm-4">Booked at</dt><dd class="col-sm-8">{{ $booking->booked_at?->format('Y-m-d H:i') }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-white"><strong>Seats</strong></div>
            <ul class="list-group list-group-flush">
                @forelse ($booking->seats as $s)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Row {{ $s->seat_row }} - Seat {{ $s->seat_number }}</span>
                        <span>{{ $s->ticketClass->name ?? '' }} (${{ number_format($s->ticketClass->price ?? 0, 2) }})</span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">No seats.</li>
                @endforelse
            </ul>
        </div>
        @if ($booking->addons->count())
            <div class="card mt-3">
                <div class="card-header bg-white"><strong>Add-ons</strong></div>
                <ul class="list-group list-group-flush">
                    @foreach ($booking->addons as $a)
                        <li class="list-group-item">{{ $a->popcornItem->name ?? 'Item' }} &times; {{ $a->quantity }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
<div class="mt-3">
    @if ($booking->status !== 'refunded')
        <form method="POST" action="{{ route('admin.bookings.refund', $booking->id) }}" class="d-inline" onsubmit="return confirm('Refund this booking?')">
            @csrf
            <button class="btn btn-warning"><i class="bi bi-arrow-counterclockwise"></i> Refund</button>
        </form>
    @endif
    <form method="POST" action="{{ route('admin.bookings.destroy', $booking->id) }}" class="d-inline" onsubmit="return confirm('Delete this booking?')">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
    </form>
    <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary">Back</a>
</div>
@endsection
