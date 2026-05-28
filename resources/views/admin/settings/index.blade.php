@extends('admin.layouts.admin')
@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf
    <div class="card">
        <div class="card-body">
            @forelse ($settings as $key => $setting)
                <div class="mb-3">
                    <label class="form-label">{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                    @if (Str::length($setting->value) > 60)
                        <textarea class="form-control" name="settings[{{ $key }}]" rows="3">{{ $setting->value }}</textarea>
                    @else
                        <input class="form-control" type="text" name="settings[{{ $key }}]" value="{{ $setting->value }}">
                    @endif
                </div>
            @empty
                <p class="text-muted">No settings configured yet.</p>
            @endforelse
        </div>
        <div class="card-footer text-end">
            <button class="btn btn-primary" type="submit">Save settings</button>
        </div>
    </div>
</form>
@endsection
