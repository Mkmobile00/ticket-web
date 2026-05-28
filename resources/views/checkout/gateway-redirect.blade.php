<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Redirecting to {{ ucfirst($gateway) }}…</title>
    <style>
        body{font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#0d0f12;color:#e9ecf1;display:grid;place-items:center;height:100vh;margin:0}
        .box{text-align:center}
        .spin{width:42px;height:42px;border:4px solid #2c3138;border-top-color:#ff5046;border-radius:50%;margin:0 auto 18px;animation:s 1s linear infinite}
        @keyframes s{to{transform:rotate(360deg)}}
        button{margin-top:16px;background:#ff5046;color:#fff;border:0;border-radius:8px;padding:11px 20px;font-size:15px;cursor:pointer}
    </style>
</head>
<body>
    <div class="box">
        <div class="spin"></div>
        <p>Redirecting you to <strong>{{ ucfirst($gateway) }}</strong> to complete payment…</p>
        <form id="gw" method="POST" action="{{ $action }}">
            @foreach ($fields as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <button type="submit">Continue to {{ ucfirst($gateway) }}</button>
        </form>
    </div>
    <script>document.getElementById('gw').submit();</script>
</body>
</html>
