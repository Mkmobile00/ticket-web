@extends('admin.layouts.admin')
@section('title', 'Message from ' . $message->name)
@section('page-title', 'Message')

@section('content')
<div class="card">
    <div class="card-body">
        <h5>{{ $message->subject ?: 'No subject' }}</h5>
        <div class="text-muted small mb-3">From <strong>{{ $message->name }}</strong> &lt;{{ $message->email }}&gt; · {{ $message->created_at?->format('Y-m-d H:i') }}</div>
        <div style="white-space: pre-wrap">{{ $message->message }}</div>
    </div>
</div>
<div class="mt-3">
    <a href="mailto:{{ $message->email }}?subject=Re: {{ urlencode($message->subject ?? '') }}" class="btn btn-primary"><i class="bi bi-reply"></i> Reply by Email</a>
    <form method="POST" action="{{ route('admin.contact-messages.destroy', $message->id) }}" class="d-inline" onsubmit="return confirm('Delete?')">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
    </form>
    <a href="{{ route('admin.contact-messages.index') }}" class="btn btn-outline-secondary">Back</a>
</div>
@endsection
