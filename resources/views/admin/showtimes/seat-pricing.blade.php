@extends('admin.layouts.admin')
@section('title', $title)
@section('page-title', $title)

@php
    use Illuminate\Support\Str;
    $autoName = fn ($p) => 'Rs ' . rtrim(rtrim(number_format((float) $p, 2, '.', ''), '0'), '.');
@endphp

@section('content')
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h5 class="mb-1">Seat prices &mdash; per row</h5>
                <div class="text-muted">
                    {{ $showtime->label ?? ('Showtime #' . $showtime->id) }}
                </div>
                <div class="small text-muted mt-1">
                    Type a price for each row. Rows with the <strong>same price &amp; name</strong> are grouped into one
                    ticket-class tier automatically. Leave a row blank to keep it unpriced.
                </div>
            </div>
            <a href="{{ route('admin.showtimes.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to showtimes
            </a>
        </div>
    </div>
</div>

@if (empty($rows))
    <div class="alert alert-warning">
        This showtime&rsquo;s screen has no seat rows defined yet. Set up the screen&rsquo;s seat layout first
        (Screens &rarr; edit &rarr; seat layout), then come back here.
    </div>
@else
<form method="POST" action="{{ route('admin.showtimes.pricing.save', $showtime) }}">
    @csrf

    {{-- Quick fill --}}
    <div class="card mb-3">
        <div class="card-body d-flex align-items-end flex-wrap gap-2">
            <div>
                <label class="form-label small mb-1">Apply to all rows</label>
                <div class="input-group" style="max-width:320px;">
                    <span class="input-group-text">Rs</span>
                    <input type="number" step="0.01" min="0" id="bulk-price" class="form-control" placeholder="e.g. 250">
                    <input type="text" id="bulk-name" class="form-control" placeholder="Tier name (optional)">
                </div>
            </div>
            <button type="button" class="btn btn-outline-primary" id="apply-all">
                <i class="bi bi-magic"></i> Fill every row
            </button>
            <button type="button" class="btn btn-outline-secondary" id="clear-all">
                <i class="bi bi-eraser"></i> Clear all
            </button>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-admin mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width:90px;">Row</th>
                        <th style="width:220px;">Price (Rs)</th>
                        <th>Tier name <span class="text-muted small">(optional &mdash; defaults to &ldquo;Rs price&rdquo;)</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        @php
                            $curPrice = old("prices.$row", $current[$row]['price'] ?? '');
                            $curName = $current[$row]['name'] ?? '';
                            // Don't pre-fill an auto-generated "Rs X" name — keep the field clean.
                            if ($curName !== '' && $curName === $autoName($current[$row]['price'] ?? 0)) {
                                $curName = '';
                            }
                            $curName = old("labels.$row", $curName);
                        @endphp
                        <tr>
                            <td><span class="badge bg-secondary fs-6">{{ $row }}</span></td>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-text">Rs</span>
                                    <input type="number" step="0.01" min="0"
                                           class="form-control price-input"
                                           name="prices[{{ $row }}]"
                                           value="{{ $curPrice }}"
                                           placeholder="0.00">
                                </div>
                            </td>
                            <td>
                                <input type="text" class="form-control name-input"
                                       name="labels[{{ $row }}]"
                                       value="{{ $curName }}"
                                       placeholder="e.g. Classic, Premium, IMAX">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="text-muted small" id="summary"></div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save prices</button>
    </div>
</form>

<script>
(function () {
    var rows = document.querySelectorAll('tbody tr');

    document.getElementById('apply-all').addEventListener('click', function () {
        var p = document.getElementById('bulk-price').value;
        var n = document.getElementById('bulk-name').value;
        rows.forEach(function (tr) {
            tr.querySelector('.price-input').value = p;
            tr.querySelector('.name-input').value = n;
        });
        summarize();
    });

    document.getElementById('clear-all').addEventListener('click', function () {
        rows.forEach(function (tr) {
            tr.querySelector('.price-input').value = '';
            tr.querySelector('.name-input').value = '';
        });
        summarize();
    });

    function summarize() {
        var groups = {}, priced = 0;
        rows.forEach(function (tr) {
            var p = tr.querySelector('.price-input').value;
            if (p === '' || p === null) return;
            priced++;
            var num = parseFloat(p).toFixed(2);
            var name = tr.querySelector('.name-input').value.trim() || ('Rs ' + parseFloat(p));
            groups[name + '|' + num] = true;
        });
        var nTiers = Object.keys(groups).length;
        document.getElementById('summary').textContent =
            priced + ' of ' + rows.length + ' rows priced → ' + nTiers + ' tier' + (nTiers === 1 ? '' : 's');
    }

    document.querySelectorAll('.price-input, .name-input').forEach(function (el) {
        el.addEventListener('input', summarize);
    });
    summarize();
})();
</script>
@endif
@endsection
