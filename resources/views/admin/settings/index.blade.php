@extends('admin.layouts.admin')
@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
    @csrf
    <div class="card">
        <div class="card-body">
            @forelse ($settings as $key => $setting)
                @php $isImage = Str::endsWith($key, ['_bg', '_image', '_logo']); @endphp
                <div class="mb-3">
                    <label class="form-label">{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                    @if ($isImage)
                        @if ($setting->value)
                            <div class="mb-2"><img src="{{ \App\Models\Setting::image($key) }}" alt="" style="max-height:90px;border-radius:6px;border:1px solid #eee;"></div>
                        @endif
                        <input class="form-control mb-2" type="text" name="settings[{{ $key }}]" value="{{ $setting->value }}" placeholder="assets/... path, storage path, or full URL">
                        <input class="form-control" type="file" name="files[{{ $key }}]" accept="image/*">
                        <small class="text-muted">Upload a new image, or paste a path/URL above.</small>
                    @elseif (Str::length($setting->value) > 60)
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
