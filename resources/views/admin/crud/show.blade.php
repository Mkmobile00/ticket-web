@extends('admin.layouts.admin')
@section('title', class_basename($item))

@section('content')
<div class="card">
    <div class="card-body">
        <h4>{{ class_basename($item) }} #{{ $item->id }}</h4>
        <table class="table mt-3">
            @foreach ($item->getAttributes() as $key => $val)
                <tr>
                    <th width="220">{{ ucwords(str_replace('_', ' ', $key)) }}</th>
                    <td>{{ is_scalar($val) ? Str::limit((string) $val, 200) : json_encode($val) }}</td>
                </tr>
            @endforeach
        </table>
        <a href="{{ route('admin.' . Str::plural($resource) . '.edit', $item->id) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
        <a href="{{ route('admin.' . Str::plural($resource) . '.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
</div>
@endsection
