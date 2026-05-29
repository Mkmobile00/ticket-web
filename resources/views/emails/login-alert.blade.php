@extends('emails.layout')
@section('heading', 'New sign-in to your account')
@section('body')
    <p>Hi {{ $user->name }},</p>
    <p>Your {{ config('app.name', 'Boleto') }} account was just signed in to:</p>
    <table style="width:100%;border-collapse:collapse;font-size:14px;margin:14px 0;">
        <tr><td style="padding:6px 0;color:#666;">When</td><td style="padding:6px 0;text-align:right;font-weight:600;">{{ $when }}</td></tr>
        <tr><td style="padding:6px 0;color:#666;">IP address</td><td style="padding:6px 0;text-align:right;">{{ $ip }}</td></tr>
        <tr><td style="padding:6px 0;color:#666;">Account</td><td style="padding:6px 0;text-align:right;">{{ $user->email }}</td></tr>
    </table>
    <p style="font-size:13px;color:#888;">If this was you, no action is needed. If you don't recognise this activity, please change your password immediately.</p>
@endsection
