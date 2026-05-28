@extends('admin.layouts.admin')
@section('title', 'Bookings')
@section('page-title', 'Bookings')

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead><tr><th>#</th><th>User</th><th>Type</th><th>Total</th><th>Status</th><th>Booked</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse ($bookings as $b)
                    <tr>
                        <td>{{ $b->id }}</td>
                        <td>{{ $b->user->name ?? 'Guest' }}</td>
                        <td>{{ class_basename($b->bookable_type) }} #{{ $b->bookable_id }}</td>
                        <td>Rs {{ number_format($b->total_amount, 2) }}</td>
                        <td>
                            @php $cls = match($b->status) { 'confirmed'=>'success','pending'=>'warning','refunded'=>'danger', default=>'secondary' }; @endphp
                            <span class="badge bg-{{ $cls }}">{{ $b->status }}</span>
                        </td>
                        <td class="text-muted">{{ $b->created_at?->diffForHumans() }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.bookings.show', $b->id) }}"><i class="bi bi-eye"></i></a>
                            @if ($b->status !== 'refunded')
                                <form method="POST" action="{{ route('admin.bookings.refund', $b->id) }}" class="d-inline" onsubmit="return confirm('Mark as refunded?')">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-warning"><i class="bi bi-arrow-counterclockwise"></i></button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.bookings.destroy', $b->id) }}" class="d-inline" onsubmit="return confirm('Delete this booking?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No bookings yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if ($bookings->hasPages())
    <div class="mt-3">{{ $bookings->links() }}</div>
@endif
@endsection
