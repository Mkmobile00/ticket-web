{{-- Shared event/sport checkout: line-item summary + payment selector. --}}
<div class="padding-top padding-bottom">
    <div class="container">
        @if ($errors->any())
            <div class="alert alert-danger">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <div class="row justify-content-center">
            {{-- Summary --}}
            <div class="col-lg-5 mb-4">
                <div class="booking-summery bg-one" style="padding:24px;border-radius:10px;">
                    <h4 class="title">Booking Summary</h4>
                    <ul style="list-style:none;padding:0;margin:16px 0;">
                        @foreach ($booking->seats->groupBy('tier_label') as $tier => $group)
                            <li class="d-flex justify-content-between" style="padding:8px 0;border-bottom:1px solid rgba(255,255,255,.1);color:#cfd4db;">
                                <span>{{ $tier ?: 'Seat' }} <span class="text-muted">× {{ $group->count() }}</span><br><small class="text-muted">{{ $group->map(fn($s)=>$s->seat_row.$s->seat_number)->implode(', ') }}</small></span>
                                <span>Rs {{ number_format($group->sum('price'), 2) }}</span>
                            </li>
                        @endforeach
                        <li class="d-flex justify-content-between" style="padding:12px 0;font-weight:700;color:#fff;">
                            <span>Total</span>
                            <span>Rs {{ number_format($booking->total_amount, 2) }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Payment --}}
            <div class="col-lg-5 mb-4">
                <div class="checkout-widget checkout-card" style="background:#fff;border-radius:10px;padding:24px;">
                    <h5 class="title">Payment Option</h5>
                    <form method="POST" action="{{ route('checkout.confirm', $booking->id) }}">
                        @csrf
                        <ul class="payment-option" style="display:flex;gap:10px;flex-wrap:wrap;margin:14px 0;padding:0;list-style:none;">
                            <li><label style="cursor:pointer;"><input type="radio" name="payment_method" value="esewa" checked> eSewa <small>(sandbox)</small></label></li>
                            <li><label style="cursor:pointer;"><input type="radio" name="payment_method" value="khalti"> Khalti</label></li>
                            <li><label style="cursor:pointer;"><input type="radio" name="payment_method" value="credit_card"> Card <small>(test)</small></label></li>
                        </ul>
                        <div class="alert" style="background:#eef6ff;border:1px solid #cfe2ff;color:#234;border-radius:8px;padding:12px 14px;font-size:13px;">
                            <strong>Test payments only.</strong> eSewa sandbox login: <code>9806800001</code> / <code>Nepal@123</code> / MPIN <code>1122</code> / OTP <code>123456</code>. Card auto-completes.
                        </div>
                        <button type="submit" class="custom-button" style="border:0;">Pay Rs {{ number_format($booking->total_amount, 2) }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
