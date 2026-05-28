@extends('layouts.frontend')
@section('title', 'My Account')

@section('content')
<x-breadcrumb title="My Account" :crumbs="['Account' => '']" />

<section class="padding-top padding-bottom" style="min-height:60vh;">
    <div class="container">
        @include('account._nav')

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="row mb-4">
            @php
                $statMeta = [
                    'total' => ['Total Bookings', '#3b82f6'],
                    'confirmed' => ['Confirmed', '#10b981'],
                    'pending' => ['Pending', '#f59e0b'],
                    'cancelled' => ['Cancelled / Refunded', '#ef4444'],
                ];
            @endphp
            @foreach ($stats as $k => $v)
                @php [$label, $color] = $statMeta[$k]; @endphp
                <div class="col-6 col-md-3 mb-3">
                    <div style="background:#1c1d28;border-left:4px solid {{ $color }};padding:18px;border-radius:6px;color:#fff;">
                        <div style="font-size:1.8rem;font-weight:700;">{{ $v }}</div>
                        <div style="color:#9ca3af;font-size:13px;">{{ $label }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div style="background:#1c1d28;padding:24px;border-radius:6px;color:#fff;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="m-0">Recent Bookings</h4>
                <a href="{{ route('account.bookings.index') }}" style="color:#ff5046;text-decoration:none;">View all <i class="fas fa-angle-right"></i></a>
            </div>
            @forelse ($bookings as $b)
                <div style="padding:14px 0;border-bottom:1px solid #2a2d3a;display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                    <div>
                        <div style="font-weight:600;">{{ $b->bookable->title ?? class_basename($b->bookable_type) }}</div>
                        <div style="color:#9ca3af;font-size:13px;">
                            #{{ $b->id }} · {{ $b->seats->count() }} seat(s) · {{ $b->booked_at?->format('d M Y, H:i') }}
                        </div>
                    </div>
                    <div class="text-end">
                        <div style="font-weight:600;">${{ number_format($b->total_amount, 2) }}</div>
                        @php $cls = match($b->status){ 'confirmed'=>'#10b981','pending'=>'#f59e0b','cancelled','refunded'=>'#ef4444', default=>'#6b7280' }; @endphp
                        <span style="background:{{ $cls }};color:#fff;padding:2px 10px;border-radius:10px;font-size:11px;text-transform:uppercase;">{{ $b->status }}</span>
                    </div>
                </div>
            @empty
                <p class="text-center text-muted py-4 m-0">No bookings yet. <a href="{{ route('movies.index') }}" style="color:#ff5046;">Browse movies</a></p>
            @endforelse
        </div>
    </div>
</section>
@endsection
