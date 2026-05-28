@extends('layouts.frontend')
@section('title', 'My Profile')

@section('content')
<x-breadcrumb title="My Profile" :crumbs="['Account' => route('account.dashboard'), 'Profile' => '']" />

<section class="padding-top padding-bottom" style="min-height:60vh;">
    <div class="container">
        @include('account._nav')

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <div class="row">
            <div class="col-lg-6 mb-4">
                <div style="background:#1c1d28;padding:30px;border-radius:6px;color:#fff;">
                    <h5 class="mb-3"><i class="fas fa-user-edit"></i> Personal Details</h5>
                    <form method="POST" action="{{ route('account.profile.update') }}">
                        @csrf @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                style="width:100%;padding:10px;background:#252734;border:1px solid #2a2d3a;color:#fff;border-radius:4px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                                style="width:100%;padding:10px;background:#252734;border:1px solid #2a2d3a;color:#fff;border-radius:4px;">
                        </div>
                        <button class="btn btn-danger"><i class="fas fa-save"></i> Save Changes</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div style="background:#1c1d28;padding:30px;border-radius:6px;color:#fff;">
                    <h5 class="mb-3"><i class="fas fa-key"></i> Change Password</h5>
                    <form method="POST" action="{{ route('account.profile.password') }}">
                        @csrf @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" required
                                style="width:100%;padding:10px;background:#252734;border:1px solid #2a2d3a;color:#fff;border-radius:4px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password (min 8 chars)</label>
                            <input type="password" name="password" required
                                style="width:100%;padding:10px;background:#252734;border:1px solid #2a2d3a;color:#fff;border-radius:4px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="password_confirmation" required
                                style="width:100%;padding:10px;background:#252734;border:1px solid #2a2d3a;color:#fff;border-radius:4px;">
                        </div>
                        <button class="btn btn-danger"><i class="fas fa-key"></i> Change Password</button>
                    </form>
                </div>
            </div>
        </div>

        <div style="background:#1c1d28;padding:20px;border-radius:6px;color:#9ca3af;font-size:13px;">
            <strong style="color:#fff;">Account info:</strong>
            Member since {{ $user->created_at?->format('M Y') }}
            @if ($user->is_admin)<span class="badge bg-warning ms-2">Admin</span>@endif
        </div>
    </div>
</section>
@endsection
