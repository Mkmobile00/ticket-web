<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>@yield('heading', config('app.name', 'Boleto'))</title></head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f4f5f7;margin:0;padding:24px;color:#222;">
    <div style="max-width:560px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e5e7eb;">
        <div style="background:linear-gradient(135deg,#ff5046,#ff8a3d);padding:22px 28px;color:#fff;">
            <h2 style="margin:0;font-size:20px;">@yield('heading')</h2>
            @hasSection('sub')<p style="margin:6px 0 0;opacity:.9;">@yield('sub')</p>@endif
        </div>
        <div style="padding:24px 28px;line-height:1.6;">@yield('body')</div>
        <div style="background:#f7f8fa;padding:14px 28px;text-align:center;font-size:12px;color:#888;">
            {{ config('app.name', 'Boleto') }} &middot; This is an automated message, please do not reply.
        </div>
    </div>
</body>
</html>
