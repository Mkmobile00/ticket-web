@extends('layouts.frontend')
@section('title', 'My Bookings')

@section('content')
<x-breadcrumb title="My Bookings" :crumbs="['Account' => route('account.dashboard'), 'Bookings' => '']" />

<section class="padding-top padding-bottom" style="min-height:60vh;">
    <div class="container">
        @include('account._nav')

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="mb-3">
            @php $current = request()->query('status'); @endphp
            <a href="{{ route('account.bookings.index') }}" class="btn btn-sm {{ ! $current ? 'btn-danger' : 'btn-outline-light' }}">All</a>
            @foreach (['pending', 'confirmed', 'cancelled', 'refunded'] as $s)
                <a href="{{ route('account.bookings.index', ['status' => $s]) }}" class="btn btn-sm {{ $current === $s ? 'btn-danger' : 'btn-outline-light' }}">{{ ucfirst($s) }}</a>
            @endforeach
        </div>

        <div style="background:#1c1d28;border-radius:6px;color:#fff;overflow-x:auto;">
            <table class="table" style="margin:0;color:#fff;">
                <thead style="background:#252734;">
                    <tr>
                        <th>#</th>
                        <th>For</th>
                        <th>Cinema</th>
                        <th>Seats</th>
                        <th>Show Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $b)
                        @php
                            $firstSeat = $b->seats->first();
                            $showtime = $firstSeat?->ticketClass?->showtime;
                            $cinema = $showtime?->screen?->cinema;
                            $seatLabels = $b->seats->pluck('seat_row')->zip($b->seats->pluck('seat_number'))->map(fn($p) => $p[0] . $p[1])->join(', ');
                            $cls = match($b->status){ 'confirmed'=>'success','pending'=>'warning','cancelled'=>'danger','refunded'=>'secondary', default=>'dark' };
                        @endphp
                        <tr>
                            <td>{{ $b->id }}</td>
                            <td>{{ $b->bookable->title ?? class_basename($b->bookable_type) }}</td>
                            <td>{{ $cinema?->name ?? '—' }}</td>
                            <td>{{ $seatLabels ?: '—' }}</td>
                            <td>{{ $showtime ? \Carbon\Carbon::parse($showtime->show_date)->format('d M Y') . ' ' . \Carbon\Carbon::parse($showtime->show_time)->format('H:i') : '—' }}</td>
                            <td>${{ number_format($b->total_amount, 2) }}</td>
                            <td><span class="badge bg-{{ $cls }}">{{ $b->status }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('account.bookings.show', $b->id) }}" class="btn btn-sm btn-outline-light"><i class="fas fa-eye"></i></a>
                                @if (! in_array($b->status, ['cancelled', 'refunded']))
                                    <form method="POST" action="{{ route('account.bookings.cancel', $b->id) }}" class="d-inline" onsubmit="return confirm('Cancel this booking? This cannot be undone.')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i> Cancel</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-4">No bookings found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($bookings->hasPages())
            <div class="mt-3">{{ $bookings->links() }}</div>
        @endif
    </div>
</section>
@endsection
