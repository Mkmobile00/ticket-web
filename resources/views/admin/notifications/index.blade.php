@extends('admin.layouts.admin')
@section('title', 'Push Notifications')
@section('page-title', 'Push Notifications')

@section('content')
<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">Send a push notification</h5>

                @unless ($configured)
                    <div class="alert alert-warning">
                        <strong>Firebase not configured.</strong> Add the service-account JSON at
                        <code>storage/app/firebase/service-account.json</code> (or set <code>FIREBASE_CREDENTIALS</code>)
                        to enable sending. See <code>buleto_app/SETUP-GOOGLE-PUSH.md</code>.
                    </div>
                @endunless

                <form method="POST" action="{{ route('admin.notifications.send') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" value="{{ old('title') }}" class="form-control" maxlength="120" required placeholder="e.g. 🎬 New release this Friday!">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="body" class="form-control" rows="3" maxlength="500" required placeholder="Write the notification text…">{{ old('body') }}</textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-sm-5">
                            <label class="form-label">Send to</label>
                            <select name="target" id="target" class="form-select">
                                <option value="all" @selected(old('target') === 'all')>All registered devices</option>
                                <option value="email" @selected(old('target') === 'email')>A specific user (email)</option>
                            </select>
                        </div>
                        <div class="col-sm-7" id="email-wrap" style="{{ old('target') === 'email' ? '' : 'display:none;' }}">
                            <label class="form-label">User email</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="user@example.com">
                        </div>
                    </div>
                    <div class="mb-3 mt-2">
                        <label class="form-label">Image <span class="text-muted small">(optional — shows a big picture in the notification)</span></label>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="text" name="image" id="lfm-image" value="{{ old('image') }}" class="form-control" placeholder="Pick an image or paste a URL">
                            <button type="button" class="btn btn-outline-primary lfm-button" data-input="lfm-image" data-preview="lfm-image-preview">
                                <i class="bi bi-image"></i> Choose
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('lfm-image').value='';document.getElementById('lfm-image-preview').innerHTML='';">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                        <div id="lfm-image-preview" class="mt-2">
                            @if (old('image'))
                                <img src="{{ old('image') }}" style="max-width:140px;max-height:140px;border-radius:8px;border:1px solid #2a2f36;padding:4px;background:#fff;">
                            @endif
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Open in app <span class="text-muted small">(optional — where the notification takes the user)</span></label>
                        <select name="link_type" id="link-type" class="form-select">
                            <option value="">Don't open anything</option>
                            <option value="home">App home</option>
                            <option value="movies">Movies list</option>
                            <option value="events">Events list</option>
                            <option value="sports">Sports list</option>
                            <option value="bookings">My bookings</option>
                            <option value="movie">A specific movie…</option>
                            <option value="event">A specific event…</option>
                            <option value="sport">A specific sport…</option>
                        </select>

                        <div class="mt-2 link-item" id="pick-movie" style="display:none;">
                            <select name="link_movie" class="form-select">
                                <option value="">— Select a movie —</option>
                                @foreach ($movies as $m)
                                    <option value="{{ $m->slug }}">{{ $m->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mt-2 link-item" id="pick-event" style="display:none;">
                            <select name="link_event" class="form-select">
                                <option value="">— Select an event —</option>
                                @foreach ($events as $e)
                                    <option value="{{ $e->slug }}">{{ $e->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mt-2 link-item" id="pick-sport" style="display:none;">
                            <select name="link_sport" class="form-select">
                                <option value="">— Select a sport —</option>
                                @foreach ($sports as $s)
                                    <option value="{{ $s->slug }}">{{ $s->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary" {{ $configured ? '' : 'disabled' }}>
                        <i class="bi bi-send"></i> Send notification
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small mb-3">Reach</h6>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span>Status</span>
                    @if ($configured)
                        <span class="badge bg-success">Firebase connected</span>
                    @else
                        <span class="badge bg-secondary">Not configured</span>
                    @endif
                </div>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span>Registered devices</span><strong>{{ $deviceCount }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span>Users reachable</span><strong>{{ $userCount }}</strong>
                </div>
                <p class="text-muted small mt-3 mb-0">
                    Devices register automatically when a user signs in to the mobile app with
                    notifications allowed.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('target').addEventListener('change', function () {
        document.getElementById('email-wrap').style.display = this.value === 'email' ? '' : 'none';
    });

    // Show the matching content picker when a "specific …" target is chosen.
    document.getElementById('link-type').addEventListener('change', function () {
        var map = { movie: 'pick-movie', event: 'pick-event', sport: 'pick-sport' };
        document.querySelectorAll('.link-item').forEach(function (el) { el.style.display = 'none'; });
        if (map[this.value]) document.getElementById(map[this.value]).style.display = '';
    });
</script>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="/vendor/laravel-filemanager/js/stand-alone-button.js"></script>
<script>
    jQuery(function ($) {
        $('.lfm-button').filemanager('image', { prefix: '/filemanager' });
        // Refresh the preview thumbnail whenever the URL changes.
        $('#lfm-image').on('input change', function () {
            var url = this.value.trim();
            $('#lfm-image-preview').html(url
                ? '<img src="' + url + '" style="max-width:140px;max-height:140px;border-radius:8px;border:1px solid #2a2f36;padding:4px;background:#fff;">'
                : '');
        });
    });
</script>
@endpush
