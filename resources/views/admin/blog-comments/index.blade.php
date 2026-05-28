@extends('admin.layouts.admin')
@section('title', 'Blog Comments')
@section('page-title', 'Blog Comments')

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead><tr><th>#</th><th>Post</th><th>Name</th><th>Email</th><th>Body</th><th>Approved</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse ($comments as $c)
                    <tr>
                        <td>{{ $c->id }}</td>
                        <td><a href="{{ route('blog.show', $c->post->slug) }}" target="_blank">{{ Str::limit($c->post->title ?? '-', 35) }}</a></td>
                        <td>{{ $c->name }}</td>
                        <td>{{ $c->email }}</td>
                        <td>{{ Str::limit($c->body, 80) }}</td>
                        <td>
                            @if ($c->approved)<span class="badge bg-success">Yes</span>@else<span class="badge bg-warning">Pending</span>@endif
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.blog-comments.update', $c->id) }}" class="d-inline">
                                @csrf @method('PUT')
                                <input type="hidden" name="approved" value="{{ $c->approved ? '0' : '1' }}">
                                <button class="btn btn-sm btn-outline-{{ $c->approved ? 'warning' : 'success' }}"><i class="bi bi-{{ $c->approved ? 'x' : 'check' }}-lg"></i></button>
                            </form>
                            <form method="POST" action="{{ route('admin.blog-comments.destroy', $c->id) }}" class="d-inline" onsubmit="return confirm('Delete?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No comments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if ($comments->hasPages())
    <div class="mt-3">{{ $comments->links() }}</div>
@endif
@endsection
