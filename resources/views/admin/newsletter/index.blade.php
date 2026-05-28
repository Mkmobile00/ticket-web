@extends('admin.layouts.admin')
@section('title', 'Newsletter Subscribers')
@section('page-title', 'Newsletter Subscribers')

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead><tr><th>#</th><th>Email</th><th>Subscribed</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse ($subscribers as $s)
                    <tr>
                        <td>{{ $s->id }}</td>
                        <td>{{ $s->email }}</td>
                        <td class="text-muted">{{ $s->created_at?->diffForHumans() }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.newsletter.destroy', $s->id) }}" class="d-inline" onsubmit="return confirm('Remove subscriber?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No subscribers.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if ($subscribers->hasPages())
    <div class="mt-3">{{ $subscribers->links() }}</div>
@endif
@endsection
