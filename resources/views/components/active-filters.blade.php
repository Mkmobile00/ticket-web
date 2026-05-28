@props(['route'])
@php
    $filters = collect(request()->query())->filter(fn ($v, $k) => $v !== '' && $v !== null && ! in_array($k, ['page', 'view']));
@endphp
@if ($filters->isNotEmpty())
    @php
        $labels = [];
        foreach ($filters as $key => $value) {
            switch ($key) {
                case 'search':
                    $labels[] = 'Search: "' . e($value) . '"';
                    break;
                case 'city':
                    $name = \App\Models\City::find($value)?->name;
                    $labels[] = 'City: ' . ($name ?: $value);
                    break;
                case 'cinema':
                    $name = \App\Models\Cinema::find($value)?->name;
                    $labels[] = 'Cinema: ' . ($name ?: $value);
                    break;
                case 'date':
                    try { $labels[] = 'Date: ' . \Carbon\Carbon::parse($value)->format('D, d M Y'); }
                    catch (\Throwable $e) { $labels[] = 'Date: ' . $value; }
                    break;
                case 'category':
                    $labels[] = 'Category: ' . ucwords(str_replace('-', ' ', $value));
                    break;
                case 'genre':
                    $labels[] = 'Genre: ' . ucwords(str_replace('-', ' ', $value));
                    break;
                case 'tag':
                    $labels[] = 'Tag: ' . ucwords(str_replace('-', ' ', $value));
                    break;
                case 'status':
                    $labels[] = 'Status: ' . ucfirst($value);
                    break;
                default:
                    $labels[] = ucfirst($key) . ': ' . $value;
            }
        }
    @endphp
    <div class="container" style="margin-bottom:20px;">
        <div style="background:rgba(255,255,255,0.06);padding:14px 18px;border-radius:6px;display:flex;flex-wrap:wrap;align-items:center;gap:10px;">
            <span style="color:#cfd2d6;font-size:13px;">
                <i class="fas fa-filter"></i> Active filters:
            </span>
            @foreach ($labels as $label)
                <span style="background:#ff5046;color:#fff;padding:4px 12px;border-radius:14px;font-size:12px;">
                    {{ $label }}
                </span>
            @endforeach
            <span style="color:#9ca3af;font-size:13px;margin-left:auto;">{{ ($paginator ?? null)?->total() ?? '' }} {{ $resultLabel ?? 'results' }}</span>
            <a href="{{ $route }}" style="background:transparent;border:1px solid #cfd2d6;color:#cfd2d6;padding:4px 14px;border-radius:14px;font-size:12px;text-decoration:none;">
                <i class="fas fa-times"></i> Clear filters
            </a>
        </div>
    </div>
@endif
