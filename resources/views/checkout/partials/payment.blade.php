{{-- Shared event/sport checkout: summary + snacks add-ons + payment selector. --}}
@php
    $rate = (float) config('app.vat_rate');
    $addonsTotal = $booking->addons->sum(fn ($a) => $a->price * $a->quantity);
    $subtotalExVat = round($booking->total_amount / (1 + $rate), 2);
    $vat = round($booking->total_amount - $subtotalExVat, 2);
@endphp
<div class="padding-top padding-bottom">
    <div class="container">
        @if ($errors->any())
            <div class="alert alert-danger">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <div class="row justify-content-center">
            {{-- Summary + snacks --}}
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
                        <li id="summary-snacks-row" class="d-flex justify-content-between" style="padding:8px 0;border-bottom:1px solid rgba(255,255,255,.1);color:#cfd4db;{{ $addonsTotal > 0 ? '' : 'display:none;' }}">
                            <span>Snacks</span><span>Rs <span id="summary-snacks">{{ number_format($addonsTotal, 2) }}</span></span>
                        </li>
                        <li class="d-flex justify-content-between" style="padding:8px 0;color:#cfd4db;">
                            <span>Subtotal</span><span>Rs <span id="summary-subtotal">{{ number_format($subtotalExVat, 2) }}</span></span>
                        </li>
                        <li class="d-flex justify-content-between" style="padding:8px 0;color:#cfd4db;">
                            <span>VAT (5%)</span><span>Rs <span id="summary-vat">{{ number_format($vat, 2) }}</span></span>
                        </li>
                        <li class="d-flex justify-content-between" style="padding:12px 0;font-weight:700;color:#fff;border-top:1px solid rgba(255,255,255,.1);">
                            <span>Amount Payable</span>
                            <span>Rs <span id="summary-payable">{{ number_format($booking->total_amount, 2) }}</span></span>
                        </li>
                    </ul>

                    @if (!empty($popcorn) && $popcorn->isNotEmpty())
                        @php $existingAddons = $booking->addons->keyBy('popcorn_item_id'); @endphp
                        <h5 class="title" style="margin-top:18px;">Add Snacks <span id="snacks-saving" style="display:none;font-size:12px;color:#ff8a3d;">saving…</span></h5>
                        <div class="snacks-list">
                            @foreach ($popcorn as $item)
                                @php $qty = (int) ($existingAddons[$item->id]->quantity ?? 0); @endphp
                                <div class="d-flex align-items-center" style="gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.06);">
                                    <div style="flex:1;">
                                        <div style="font-weight:600;color:#cfd4db;">{{ $item->name }}</div>
                                        <div style="font-size:12px;color:#8b95b5;">Rs {{ number_format($item->price, 2) }}</div>
                                    </div>
                                    <div class="d-flex align-items-center" style="gap:10px;">
                                        <button type="button" class="snack-dec" data-id="{{ $item->id }}" style="width:30px;height:30px;border-radius:50%;border:1px solid #3a4658;background:transparent;color:#cfd4db;font-size:18px;cursor:pointer;">−</button>
                                        <span class="snack-qty" data-id="{{ $item->id }}" style="min-width:18px;text-align:center;font-weight:700;color:#fff;">{{ $qty }}</span>
                                        <button type="button" class="snack-inc" data-id="{{ $item->id }}" style="width:30px;height:30px;border-radius:50%;border:1px solid #ff5046;background:transparent;color:#ff8a3d;font-size:18px;cursor:pointer;">+</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
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
                        <button type="submit" class="custom-button" style="border:0;">Pay Rs <span id="pay-amount">{{ number_format($booking->total_amount, 2) }}</span></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var url = "{{ route('checkout.addons', $booking->id) }}";
    var csrf = "{{ csrf_token() }}";
    var timer = null;
    function fmt(n) { return Number(n).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function collect() {
        var items = [];
        document.querySelectorAll('.snack-qty').forEach(function (el) {
            var q = parseInt(el.textContent) || 0;
            if (q > 0) items.push({ popcorn_item_id: parseInt(el.dataset.id), quantity: q });
        });
        return items;
    }
    function save() {
        var saving = document.getElementById('snacks-saving');
        if (saving) saving.style.display = 'inline';
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ items: collect() })
        }).then(function (r) { return r.json(); }).then(function (d) {
            if (saving) saving.style.display = 'none';
            var row = document.getElementById('summary-snacks-row');
            document.getElementById('summary-snacks').textContent = fmt(d.addons_total);
            if (row) row.style.display = d.addons_total > 0 ? '' : 'none';
            document.getElementById('summary-subtotal').textContent = fmt(d.subtotal);
            document.getElementById('summary-vat').textContent = fmt(d.vat);
            document.getElementById('summary-payable').textContent = fmt(d.payable);
            document.getElementById('pay-amount').textContent = fmt(d.payable);
        }).catch(function () { if (saving) saving.style.display = 'none'; });
    }
    function change(id, delta) {
        var el = document.querySelector('.snack-qty[data-id="' + id + '"]');
        if (!el) return;
        var q = (parseInt(el.textContent) || 0) + delta;
        if (q < 0) q = 0;
        if (q > 50) q = 50;
        el.textContent = q;
        clearTimeout(timer);
        timer = setTimeout(save, 500);
    }
    document.querySelectorAll('.snack-inc').forEach(function (b) { b.addEventListener('click', function () { change(b.dataset.id, 1); }); });
    document.querySelectorAll('.snack-dec').forEach(function (b) { b.addEventListener('click', function () { change(b.dataset.id, -1); }); });
})();
</script>
