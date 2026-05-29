@extends('emails.layout')
@section('heading', $heading)
@section('body')
    <p>{{ $intro }}</p>
    <p style="text-align:center;margin:24px 0;">
        <span style="display:inline-block;font-size:30px;font-weight:800;letter-spacing:8px;color:#ff5046;background:#fff3f1;border:1px dashed #ff8a3d;border-radius:10px;padding:14px 26px;">{{ $code }}</span>
    </p>
    <p style="font-size:13px;color:#888;">This code expires in 30 minutes. If you didn't request it, you can ignore this email.</p>
@endsection
