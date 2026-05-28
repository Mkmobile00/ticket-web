@extends('admin.layouts.admin')
@section('title', $title)
@section('page-title', $title)

@php
    $routePrefix = 'admin.' . Str::plural($resource);
    $hasImageField = collect($fields)->contains(fn ($f) => ($f['type'] ?? '') === 'image');
@endphp

@push('styles')
@if ($hasImageField)
    <style>
        .lfm-preview { max-width:160px; max-height:160px; border-radius:6px; border:1px solid #2a2f36; padding:4px; background:#fff; }
        .lfm-input-group { display:flex; gap:8px; align-items:center; }
        .lfm-input-group input { flex:1; }
    </style>
@endif
<style>
    .row-picker { display:flex; flex-wrap:wrap; gap:8px; }
    .row-chip { position:relative; cursor:pointer; user-select:none; }
    .row-chip input { position:absolute; opacity:0; pointer-events:none; }
    .row-chip span {
        display:inline-flex; align-items:center; justify-content:center;
        width:42px; height:42px; border-radius:8px; font-weight:600;
        border:1px solid #ced4da; background:#fff; color:#495057;
        transition:all .15s ease;
    }
    .row-chip:hover span { border-color:#0d6efd; color:#0d6efd; }
    .row-chip input:checked + span { background:#0d6efd; color:#fff; border-color:#0d6efd; }
    .row-picker-help { font-size:.85rem; color:#6c757d; margin-top:6px; }

    .seat-layout-rows { display:flex; flex-direction:column; gap:8px; }
    .seat-row { display:flex; align-items:center; gap:10px; }
    .seat-row .row-letter { width:80px; text-align:center; font-weight:600; text-transform:uppercase; }
    .seat-row .row-count { width:120px; }
    .seat-row .lbl { color:#6c757d; font-size:.9rem; }
    .seat-layout-total { margin-top:8px; font-size:.9rem; color:#495057; }
    .seat-layout-total strong { color:#0d6efd; }
</style>
@endpush

@section('content')
@include('admin.partials.section-help')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $item->exists ? route($routePrefix . '.update', $item->id) : route($routePrefix . '.store') }}" enctype="multipart/form-data">
            @csrf
            @if ($item->exists) @method('PUT') @endif

            @foreach ($fields as $field)
                @php
                    $name = $field['name'];
                    $type = $field['type'] ?? 'text';
                    $label = $field['label'] ?? ucwords(str_replace('_', ' ', $name));
                    $rawValue = old($name, $item->{$name} ?? '');
                    $value = (is_array($rawValue) || is_object($rawValue)) ? json_encode($rawValue, JSON_PRETTY_PRINT) : $rawValue;
                    if (in_array($type, ['date', 'time', 'datetime-local'], true) && $rawValue !== '' && $rawValue !== null) {
                        try {
                            $dt = $rawValue instanceof \DateTimeInterface
                                ? $rawValue
                                : \Illuminate\Support\Carbon::parse((string) $rawValue);
                            $value = match ($type) {
                                'date' => $dt->format('Y-m-d'),
                                'time' => $dt->format('H:i'),
                                'datetime-local' => $dt->format('Y-m-d\TH:i'),
                            };
                        } catch (\Throwable $e) { /* leave $value as-is */ }
                    }
                @endphp
                <div class="mb-3">
                    <label class="form-label">{{ $label }}</label>
                    @if ($type === 'select-or-new')
                        @php
                            $newField = preg_replace('/_id$/', '_new', $name);
                            $newPlaceholder = $field['new_placeholder'] ?? 'Type new value';
                            $oldNew = old($newField, '');
                            $startInNew = $oldNew !== '';
                        @endphp
                        <div class="select-or-new" data-name="{{ $name }}">
                            <div class="d-flex gap-2 align-items-stretch">
                                <select class="form-select picker" name="{{ $name }}" @if ($startInNew) style="display:none" @endif>
                                    @if (!empty($field['placeholder']))
                                        <option value="">{{ $field['placeholder'] }}</option>
                                    @endif
                                    @foreach ($field['options'] ?? [] as $optVal => $optLabel)
                                        <option value="{{ $optVal }}" @selected((string) $value === (string) $optVal)>{{ $optLabel }}</option>
                                    @endforeach
                                    <option value="__new__">+ Add new…</option>
                                </select>
                                <input type="text" class="form-control new-input" name="{{ $newField }}" value="{{ $oldNew }}" placeholder="{{ $newPlaceholder }}" @if (!$startInNew) style="display:none" disabled @endif>
                                <button type="button" class="btn btn-outline-secondary back-to-list" @if (!$startInNew) style="display:none" @endif title="Pick existing instead">×</button>
                            </div>
                        </div>
                    @elseif ($type === 'seat-layout')
                        @php
                            $sl = old($name, $item->{$name} ?? []);
                            if (is_string($sl)) {
                                $decoded = json_decode($sl, true);
                                $sl = is_array($decoded) ? $decoded : [];
                            }
                            $slRows = (array) ($sl['rows'] ?? []);
                            $slCounts = (array) ($sl['seats_per_row'] ?? []);
                            $pairs = [];
                            foreach ($slRows as $i => $r) {
                                $pairs[] = ['row' => $r, 'count' => $slCounts[$i] ?? 20];
                            }
                            if (empty($pairs)) {
                                $pairs[] = ['row' => 'A', 'count' => 20];
                            }
                        @endphp
                        <div class="seat-layout" data-name="{{ $name }}">
                            <div class="seat-layout-rows">
                                @foreach ($pairs as $p)
                                    <div class="seat-row">
                                        <input type="text" name="{{ $name }}[rows][]" value="{{ $p['row'] }}" maxlength="2" class="form-control row-letter" placeholder="A">
                                        <span class="lbl">row,</span>
                                        <input type="number" name="{{ $name }}[seats_per_row][]" value="{{ $p['count'] }}" min="1" max="200" class="form-control row-count">
                                        <span class="lbl">seats</span>
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove row">×</button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary add-row mt-2">+ Add Row</button>
                            <div class="seat-layout-total">Total seats: <strong class="total-display">0</strong> (auto-calculated from rows above)</div>
                        </div>
                    @elseif ($type === 'rows')
                        @php
                            $selected = old($name, $item->{$name} ?? []);
                            if (is_string($selected)) {
                                $decoded = json_decode($selected, true);
                                $selected = is_array($decoded) ? $decoded : [];
                            }
                            $selected = array_map('strtoupper', (array) $selected);
                        @endphp
                        <div class="row-picker">
                            @foreach (($field['rows'] ?? range('A', 'Z')) as $letter)
                                <label class="row-chip">
                                    <input type="checkbox" name="{{ $name }}[]" value="{{ $letter }}" @checked(in_array($letter, $selected))>
                                    <span>{{ $letter }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="row-picker-help">
                            Tick the seat rows that get this price. Only rows that exist on this showtime&rsquo;s screen are shown.
                            Customers sitting in these rows pay the price above.
                        </div>
                    @elseif ($type === 'textarea')
                        <textarea class="form-control" name="{{ $name }}" rows="5">{{ $value }}</textarea>
                    @elseif ($type === 'select')
                        <select class="form-select" name="{{ $name }}">
                            @if (!empty($field['placeholder']))
                                <option value="">{{ $field['placeholder'] }}</option>
                            @endif
                            @foreach ($field['options'] ?? [] as $optVal => $optLabel)
                                <option value="{{ $optVal }}" @selected((string) $value === (string) $optVal)>{{ $optLabel }}</option>
                            @endforeach
                        </select>
                    @elseif ($type === 'checkbox')
                        <div><input type="checkbox" name="{{ $name }}" value="1" @checked($value)></div>
                    @elseif ($type === 'image')
                        @php
                            $previewUrl = $value
                                ? (str_starts_with($value, 'http') ? $value
                                    : (str_starts_with($value, 'assets/') ? asset($value)
                                        : (str_starts_with($value, '/storage/') ? $value
                                            : asset('storage/' . ltrim($value, '/')))))
                                : null;
                        @endphp
                        <div class="lfm-input-group">
                            <input type="text" class="form-control" name="{{ $name }}" id="lfm-{{ $name }}" value="{{ $value }}" placeholder="Pick an image or paste URL">
                            <button type="button" class="btn btn-outline-primary lfm-button" data-input="lfm-{{ $name }}" data-preview="lfm-preview-{{ $name }}">
                                <i class="bi bi-image"></i> Choose
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('lfm-{{ $name }}').value=''; document.getElementById('lfm-preview-{{ $name }}').innerHTML='';">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                        <div id="lfm-preview-{{ $name }}" class="mt-2">
                            @if ($previewUrl)
                                <img src="{{ $previewUrl }}" class="lfm-preview" alt="preview">
                            @endif
                        </div>
                    @else
                        <input class="form-control" type="{{ $type }}" name="{{ $name }}" value="{{ $value }}">
                    @endif
                </div>
            @endforeach

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route($routePrefix . '.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button class="btn btn-primary" type="submit">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
@if ($hasImageField)
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="/vendor/laravel-filemanager/js/stand-alone-button.js"></script>
<script>
    // Bind every .lfm-button to its paired input/preview using the LFM jQuery plugin.
    // The LFM popup calls window.SetUrl(items) on the opener after a pick — that handler
    // is registered by .filemanager() per button so each click targets the correct field.
    jQuery(function ($) {
        $('.lfm-button').each(function () {
            $(this).filemanager('image', { prefix: '/filemanager' });
        });
    });
</script>
@endif
<script>
(function () {
    document.querySelectorAll('.select-or-new').forEach(function (container) {
        const select = container.querySelector('.picker');
        const input = container.querySelector('.new-input');
        const back = container.querySelector('.back-to-list');

        function showInput() {
            select.style.display = 'none';
            input.style.display = '';
            input.disabled = false;
            back.style.display = '';
            input.focus();
        }
        function showSelect() {
            input.value = '';
            input.disabled = true;
            input.style.display = 'none';
            back.style.display = 'none';
            select.style.display = '';
            select.value = '';
        }

        select.addEventListener('change', function () {
            if (this.value === '__new__') showInput();
        });
        back.addEventListener('click', showSelect);
    });

    document.querySelectorAll('.seat-layout').forEach(function (container) {
        const name = container.dataset.name;
        const rowsWrap = container.querySelector('.seat-layout-rows');
        const totalDisplay = container.querySelector('.total-display');
        const totalInput = document.querySelector('input[name="total_seats"]');

        function nextLetter() {
            const used = Array.from(rowsWrap.querySelectorAll('.row-letter'))
                .map(i => (i.value || '').trim().toUpperCase())
                .filter(Boolean);
            for (let c = 65; c <= 90; c++) {
                const l = String.fromCharCode(c);
                if (!used.includes(l)) return l;
            }
            return '';
        }

        function recalcTotal() {
            let sum = 0;
            rowsWrap.querySelectorAll('.row-count').forEach(i => { sum += parseInt(i.value, 10) || 0; });
            totalDisplay.textContent = sum;
            if (totalInput) totalInput.value = sum;
        }

        function bindRow(row) {
            row.querySelector('.remove-row').addEventListener('click', function () {
                if (rowsWrap.children.length <= 1) {
                    row.querySelector('.row-letter').value = '';
                    row.querySelector('.row-count').value = 0;
                } else {
                    row.remove();
                }
                recalcTotal();
            });
            row.querySelectorAll('input').forEach(i => i.addEventListener('input', recalcTotal));
            row.querySelector('.row-letter').addEventListener('input', function () {
                this.value = this.value.toUpperCase();
            });
        }

        rowsWrap.querySelectorAll('.seat-row').forEach(bindRow);

        container.querySelector('.add-row').addEventListener('click', function () {
            const letter = nextLetter();
            const div = document.createElement('div');
            div.className = 'seat-row';
            div.innerHTML =
                '<input type="text" name="' + name + '[rows][]" value="' + letter + '" maxlength="2" class="form-control row-letter" placeholder="A">' +
                '<span class="lbl">row,</span>' +
                '<input type="number" name="' + name + '[seats_per_row][]" value="20" min="1" max="200" class="form-control row-count">' +
                '<span class="lbl">seats</span>' +
                '<button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove row">&times;</button>';
            rowsWrap.appendChild(div);
            bindRow(div);
            recalcTotal();
        });

        recalcTotal();
        if (totalInput) totalInput.readOnly = true;
    });
})();
</script>
@endpush
