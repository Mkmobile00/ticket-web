@extends('emails.layout')
@section('heading', '🎉 Welcome to ' . config('app.name', 'Boleto') . '!')
@section('sub', 'Your account is ready')
@section('body')
    <p>Hi {{ $user->name }},</p>
    <p>Thanks for joining {{ config('app.name', 'Boleto') }} — your one-stop platform to book tickets for movies, events and sports.</p>
    <p style="margin:22px 0;text-align:center;">
        <a href="{{ route('movies.index') }}" style="background:linear-gradient(135deg,#ff5046,#ff8a3d);color:#fff;text-decoration:none;padding:12px 26px;border-radius:8px;font-weight:700;display:inline-block;">Browse what's on</a>
    </p>
    <p style="font-size:13px;color:#888;">You signed up with <strong>{{ $user->email }}</strong>. If this wasn't you, please ignore this email.</p>
@endsection
