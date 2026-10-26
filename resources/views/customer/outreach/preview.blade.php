<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }}</title>
    <style>body{margin:0;padding:32px;background:#f5f7fb;color:#1f2937;font-family:Arial,sans-serif}.mail{max-width:680px;margin:auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 12px #00000012}.meta{margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid #e5e7eb;font-size:14px}.meta strong{display:inline-block;width:72px;color:#6b7280}</style>
</head>
<body>
    <main class="mail">
        <div class="meta"><div><strong>To</strong> {{ $lead->email }}</div><div><strong>Subject</strong> {{ $subject }}</div></div>
        {!! $body !!}
    </main>
</body>
</html>
