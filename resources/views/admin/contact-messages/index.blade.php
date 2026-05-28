@extends('admin.layouts.admin')
@section('title', 'Contact Messages')
@section('page-title', 'Contact Messages')

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Subject</th><th>Received</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse ($messages as $m)
                    <tr>
                        <td>{{ $m->id }}</td>
                        <td>{{ $m->name }}</td>
                        <td>{{ $m->email }}</td>
                        <td>{{ Str::limit($m->subject ?? '-', 40) }}</td>
                        <td class="text-muted">{{ $m->created_at?->diffForHumans() }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.contact-messages.show', $m->id) }}"><i class="bi bi-eye"></i></a>
                            <form method="POST" action="{{ route('admin.contact-messages.destroy', $m->id) }}" class="d-inline" onsubmit="return confirm('Delete?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No messages.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if ($messages->hasPages())
    <div class="mt-3">{{ $messages->links() }}</div>
@endif
@endsection
