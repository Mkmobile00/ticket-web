@extends('admin.layouts.admin')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="row g-3 mb-4">
    @php
        $statMeta = [
            'movies' => ['Movies', 'film', 'primary'],
            'events' => ['Events', 'megaphone', 'success'],
            'sports' => ['Sports', 'trophy', 'warning'],
            'blog_posts' => ['Blog Posts', 'journal-text', 'info'],
            'users' => ['Users', 'people', 'secondary'],
            'bookings' => ['Bookings', 'ticket-perforated', 'danger'],
            'messages' => ['Messages', 'envelope', 'dark'],
            'subscribers' => ['Subscribers', 'mailbox', 'primary'],
        ];
    @endphp
    @foreach ($stats as $k => $v)
        @php [$label, $icon, $color] = $statMeta[$k]; @endphp
        <div class="col-6 col-md-3">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3">
                        <span class="badge bg-{{ $color }} p-3"><i class="bi bi-{{ $icon }} fs-5"></i></span>
                    </div>
                    <div>
                        <div class="num">{{ number_format($v) }}</div>
                        <div class="text-muted small">{{ $label }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-white"><strong>Recent Bookings</strong></div>
            <div class="table-responsive">
                <table class="table table-admin mb-0">
                    <thead><tr><th>#</th><th>User</th><th>For</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse ($recentBookings as $b)
                            <tr>
                                <td>{{ $b->id }}</td>
                                <td>{{ $b->user->name ?? 'Guest' }}</td>
                                <td>{{ class_basename($b->bookable_type) }} #{{ $b->bookable_id }}</td>
                                <td>Rs {{ number_format($b->total_amount, 2) }}</td>
                                <td><span class="badge bg-secondary">{{ $b->status }}</span></td>
                                <td class="text-muted">{{ $b->created_at?->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted text-center py-3">No bookings yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-white"><strong>Recent Messages</strong></div>
            <ul class="list-group list-group-flush">
                @forelse ($recentMessages as $m)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $m->name }}</strong>
                            <span class="text-muted small">{{ $m->created_at?->diffForHumans() }}</span>
                        </div>
                        <div class="text-muted small">{{ $m->email }}</div>
                        <div>{{ Str::limit($m->message, 110) }}</div>
                    </li>
                @empty
                    <li class="list-group-item text-muted text-center">No messages yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
