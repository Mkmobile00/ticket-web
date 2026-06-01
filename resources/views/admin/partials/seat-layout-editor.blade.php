@php
    // Normalize the incoming value into an initial grid of rows {label, cells:"sxb..."}.
    $sl = $value;
    if (is_string($sl)) { $sl = json_decode($sl, true) ?: []; }
    $sl = (array) $sl;

    $initGrid = [];
    if (! empty($sl['grid']) && is_array($sl['grid'])) {
        foreach ($sl['grid'] as $r) {
            $lab = strtoupper(trim((string) ($r['label'] ?? '')));
            if ($lab === '') continue;
            $initGrid[] = ['label' => $lab, 'cells' => (string) ($r['cells'] ?? '')];
        }
    } elseif (! empty($sl['rows'])) {
        foreach ((array) $sl['rows'] as $i => $lab) {
            $cnt = max(1, (int) ($sl['seats_per_row'][$i] ?? 20));
            $initGrid[] = ['label' => strtoupper((string) $lab), 'cells' => str_repeat('s', $cnt)];
        }
    }
    if (empty($initGrid)) {
        for ($i = 0; $i < 8; $i++) {
            $initGrid[] = ['label' => chr(65 + $i), 'cells' => str_repeat('s', 12)];
        }
    }
    // Pad every row to a uniform width with aisles.
    $maxCols = 1;
    foreach ($initGrid as $g) { $maxCols = max($maxCols, strlen($g['cells'])); }
    foreach ($initGrid as &$g) { $g['cells'] = str_pad($g['cells'], $maxCols, 'x'); } unset($g);
@endphp

<div class="seat-grid-editor" id="sge-{{ $name }}" data-name="{{ $name }}"
     data-init='@json($initGrid)'>
    <div class="sge-toolbar">
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-secondary sge-tool active" data-tool="s" title="A bookable seat">🪑 Seat</button>
            <button type="button" class="btn btn-outline-secondary sge-tool" data-tool="x" title="Empty walking path — no seat shown to customers">⬚ Aisle / Path</button>
            <button type="button" class="btn btn-outline-secondary sge-tool" data-tool="b" title="No seat — also shows as empty space to customers (kept for your own marking)">🚫 Blocked</button>
        </div>
        <span class="sge-sep"></span>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-primary" data-act="row-add">+ Row</button>
            <button type="button" class="btn btn-outline-primary" data-act="row-del">− Row</button>
            <button type="button" class="btn btn-outline-primary" data-act="col-add">+ Col</button>
            <button type="button" class="btn btn-outline-primary" data-act="col-del">− Col</button>
        </div>
        <span class="sge-sep"></span>
        <span class="sge-hint">Both <b>Aisle</b> &amp; <b>Blocked</b> show as empty walking space to customers. Click or drag to paint; seats auto-number per row.</span>
        <span class="sge-total ms-auto">Seats: <strong class="sge-count">0</strong></span>
    </div>

    <div class="sge-grid-wrap">
        <div class="sge-grid"></div>
        <div class="sge-screen">SCREEN</div>
    </div>

    <input type="hidden" name="{{ $name }}[grid]" class="sge-hidden" value="">
</div>

@once
@push('styles')
<style>
    .seat-grid-editor{border:1px solid #e0e3e8;border-radius:8px;padding:14px;background:#fafbfc;}
    .sge-toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px;}
    .sge-toolbar .sge-sep{width:1px;height:24px;background:#dee2e6;}
    .sge-tool.active{background:#0d6efd;color:#fff;border-color:#0d6efd;}
    .sge-hint{font-size:.8rem;color:#6c757d;}
    .sge-total{font-size:.85rem;color:#495057;}
    .sge-grid-wrap{overflow:auto;padding:10px;background:#fff;border-radius:6px;border:1px solid #eef0f3;}
    .sge-grid{display:inline-block;}
    .sge-line{display:flex;align-items:center;gap:4px;margin:3px 0;white-space:nowrap;}
    .sge-rl{width:22px;text-align:center;font-size:12px;color:#6c757d;font-weight:600;flex:0 0 22px;}
    .sge-cell{width:26px;height:26px;border-radius:5px;font-size:10px;display:inline-flex;align-items:center;justify-content:center;
              cursor:pointer;user-select:none;border:1px solid transparent;color:#fff;}
    .sge-cell.s{background:#3a4658;}
    .sge-cell.b{background:#9aa3af;color:#33373d;}
    .sge-cell.x{background:transparent;border:1px dashed #d4d8de;color:#cdd2da;}
</style>
@endpush
@push('scripts')
<script>
document.querySelectorAll('.seat-grid-editor').forEach(function (root) {
    var name = root.dataset.name;
    var gridEl = root.querySelector('.sge-grid');
    var hidden = root.querySelector('.sge-hidden');
    var countEl = root.querySelector('.sge-count');
    var totalInput = document.querySelector('input[name="total_seats"]');
    var tool = 's';
    var painting = false;

    var init = JSON.parse(root.dataset.init || '[]');
    // model: array of rows; each row = array of chars 's'|'x'|'b'
    var rows = init.map(function (r) { return (r.cells || '').split(''); });
    if (!rows.length) rows = [[]];

    function letter(i) { return String.fromCharCode(65 + i); }

    function serialize() {
        var grid = rows.map(function (cells, i) {
            return { label: letter(i), cells: cells.join('') };
        });
        hidden.value = JSON.stringify(grid);
        var seats = rows.reduce(function (a, cells) {
            return a + cells.filter(function (c) { return c === 's'; }).length;
        }, 0);
        countEl.textContent = seats;
        if (totalInput) totalInput.value = seats;
    }

    function render() {
        gridEl.innerHTML = '';
        rows.forEach(function (cells, ri) {
            var line = document.createElement('div'); line.className = 'sge-line';
            var rl = document.createElement('span'); rl.className = 'sge-rl'; rl.textContent = letter(ri);
            line.appendChild(rl);
            var seatNo = 0;
            cells.forEach(function (c, ci) {
                var cell = document.createElement('span');
                cell.className = 'sge-cell ' + c;
                if (c === 's') { seatNo++; cell.textContent = seatNo; }
                else if (c === 'b') { cell.textContent = '×'; }
                cell.dataset.r = ri; cell.dataset.c = ci;
                line.appendChild(cell);
            });
            line.appendChild(rl.cloneNode(true));
            gridEl.appendChild(line);
        });
        serialize();
    }

    function paint(cell) {
        var ri = +cell.dataset.r, ci = +cell.dataset.c;
        if (rows[ri][ci] === tool) return;
        rows[ri][ci] = tool;
        render(); // re-render to refresh seat numbers
    }

    // Tool selection
    root.querySelectorAll('.sge-tool').forEach(function (b) {
        b.addEventListener('click', function () {
            root.querySelectorAll('.sge-tool').forEach(function (x) { x.classList.remove('active'); });
            b.classList.add('active');
            tool = b.dataset.tool;
        });
    });

    // Row/Col controls
    root.querySelectorAll('[data-act]').forEach(function (b) {
        b.addEventListener('click', function () {
            var act = b.dataset.act;
            var cols = rows[0] ? rows[0].length : 12;
            if (act === 'row-add' && rows.length < 26) { var nr = []; for (var i=0;i<cols;i++) nr.push('s'); rows.push(nr); }
            else if (act === 'row-del' && rows.length > 1) { rows.pop(); }
            else if (act === 'col-add' && cols < 40) { rows.forEach(function (r) { r.push('s'); }); }
            else if (act === 'col-del' && cols > 1) { rows.forEach(function (r) { r.pop(); }); }
            render();
        });
    });

    // Paint on click + drag
    gridEl.addEventListener('mousedown', function (e) {
        var cell = e.target.closest('.sge-cell'); if (!cell) return;
        e.preventDefault(); painting = true; paint(cell);
    });
    gridEl.addEventListener('mouseover', function (e) {
        if (!painting) return;
        var cell = e.target.closest('.sge-cell'); if (cell) paint(cell);
    });
    document.addEventListener('mouseup', function () { painting = false; });

    render();
    // ensure the hidden field is populated even if the form submits without edits
    root.closest('form')?.addEventListener('submit', serialize);
});
</script>
@endpush
@endonce
