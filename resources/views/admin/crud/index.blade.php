@extends('admin.layouts.admin')
@section('title', $title)
@section('page-title', $title)

@php $routePrefix = 'admin.' . Str::plural($resource); @endphp

@section('content')
@include('admin.partials.section-help')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="m-0">{{ $title }}</h4>
    <a href="{{ route($routePrefix . '.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Create</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead>
                <tr>
                    @foreach ($columns as $col)
                        @php
                            $isFk = str_ends_with($col, '_id') && $col !== 'id' && isset(($fkLabels ?? [])[$col]);
                            $headerLabel = $isFk ? ucwords(str_replace(['_', ' id'], [' ', ''], $col)) : ucwords(str_replace('_', ' ', $col));
                        @endphp
                        <th>{{ $headerLabel }}</th>
                    @endforeach
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        @foreach ($columns as $col)
                            <td>
                                @php
                                    $val = data_get($item, $col);
                                    $isImageCol = is_string($val)
                                        && (in_array($col, ['image','photo','thumbnail','banner','poster','logo','avatar','cover','icon'])
                                            || str_ends_with($col, '_image') || str_ends_with($col, '_photo'));
                                @endphp
                                @if ($isImageCol && $val)
                                    <img src="{{ image_url($val) }}" alt="" style="width:50px;height:50px;object-fit:cover;border-radius:4px;border:1px solid #e5e7eb;">
                                @elseif (str_ends_with($col, '_id') && $col !== 'id' && isset(($fkLabels ?? [])[$col][$val]))
                                    {{ $fkLabels[$col][$val] }} <span class="text-muted small">#{{ $val }}</span>
                                @elseif ($val instanceof \Carbon\Carbon || $val instanceof \DateTimeInterface)
                                    {{ \Carbon\Carbon::parse($val)->format('Y-m-d H:i') }}
                                @elseif (is_bool($val))
                                    @if ($val) <span class="badge bg-success">Yes</span> @else <span class="badge bg-secondary">No</span> @endif
                                @elseif (is_array($val) || is_object($val))
                                    <code class="small">{{ Str::limit(json_encode($val), 60) }}</code>
                                @elseif (is_null($val))
                                    <span class="text-muted">&mdash;</span>
                                @else
                                    {{ Str::limit((string) $val, 80) }}
                                @endif
                            </td>
                        @endforeach
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route($routePrefix . '.edit', $item->id) }}"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route($routePrefix . '.destroy', $item->id) }}" class="d-inline" onsubmit="return confirm('Delete this {{ $resource }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($columns) + 1 }}" class="text-center text-muted py-4">No records.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if ($items->hasPages())
    <div class="mt-3">{{ $items->links() }}</div>
@endif
@endsection
