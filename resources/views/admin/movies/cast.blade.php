@extends('admin.layouts.admin')
@section('title', 'Cast & Crew — ' . $movie->title)
@section('page-title', 'Cast & Crew')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="mb-0">{{ $movie->title }}</h5>
        <small class="text-muted">Select existing members to attach. Add new people in
            <a href="{{ route('admin.cast-members.index') }}">Cast &amp; Crew</a>.</small>
    </div>
    <a href="{{ route('admin.movies.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Movies</a>
</div>

@if (session('status'))
    <div class="alert alert-success py-2">{{ session('status') }}</div>
@endif

<div class="row g-3">
    {{-- Add / attach --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-uppercase small text-muted mb-3">Add to this movie</h6>
                @if ($all->isEmpty())
                    <p class="text-muted small">No cast/crew members yet.
                        <a href="{{ route('admin.cast-members.create') }}">Add one first →</a></p>
                @else
                    <form method="POST" action="{{ route('admin.movies.cast.attach', $movie->id) }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small">Person</label>
                            <select name="cast_member_id" class="form-select" required>
                                <option value="">— Select —</option>
                                @foreach ($all as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Role</label>
                            <select name="role" class="form-select" required>
                                @foreach (['actor' => 'Actor (Cast)', 'director' => 'Director', 'producer' => 'Producer', 'writer' => 'Writer', 'music' => 'Music', 'cinematography' => 'Cinematography', 'editor' => 'Editor'] as $val => $lbl)
                                    <option value="{{ $val }}">{{ $lbl }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Actors show under <b>Cast</b>; everyone else under <b>Crew</b>.</div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Character / "As" (cast only)</label>
                            <input type="text" name="character_name" class="form-control" placeholder="e.g. Vikram Rathore">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Order</label>
                            <input type="number" name="order" class="form-control" value="0" min="0">
                        </div>
                        <button class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Add</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Current cast/crew --}}
    <div class="col-lg-8">
        @php
            $cast = $movie->cast->filter(fn ($p) => strtolower($p->pivot->role ?? 'actor') === 'actor');
            $crew = $movie->cast->filter(fn ($p) => strtolower($p->pivot->role ?? 'actor') !== 'actor');
        @endphp

        @foreach (['Cast' => $cast, 'Crew' => $crew] as $group => $people)
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="text-uppercase small text-muted mb-3">{{ $group }} ({{ $people->count() }})</h6>
                    @if ($people->isEmpty())
                        <p class="text-muted small mb-0">No {{ strtolower($group) }} attached yet.</p>
                    @else
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr>
                                <th>Name</th>
                                <th>{{ $group === 'Cast' ? 'As' : 'Role' }}</th>
                                <th style="width:60px">Order</th>
                                <th class="text-end">Remove</th>
                            </tr></thead>
                            <tbody>
                            @foreach ($people->sortBy(fn ($p) => $p->pivot->order ?? 0) as $p)
                                <tr>
                                    <td class="fw-semibold">{{ $p->name }}</td>
                                    <td>{{ $group === 'Cast' ? ($p->pivot->character_name ?: '—') : ucwords($p->pivot->role) }}</td>
                                    <td>{{ $p->pivot->order }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('admin.movies.cast.detach', [$movie->id, $p->id]) }}" onsubmit="return confirm('Remove {{ $p->name }} from this movie?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
