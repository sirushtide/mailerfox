<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Unsubscribe') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f7f8fa;
            color: #1f2937;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 40px 32px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        h1 { font-size: 22px; margin: 0 0 8px; color: #111827; }
        p  { font-size: 14px; line-height: 1.55; color: #4b5563; margin: 0 0 24px; }
        .email { font-weight: 600; color: #111827; }
        .actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        button, .btn {
            font: inherit;
            font-weight: 600;
            font-size: 14px;
            padding: 11px 20px;
            border-radius: 10px;
            cursor: pointer;
            border: 1px solid transparent;
            transition: background .15s, border-color .15s, color .15s;
        }
        .btn-primary  { background: #dc2626; color: #fff; }
        .btn-primary:hover  { background: #b91c1c; }
        .btn-secondary { background: #fff; color: #374151; border-color: #e5e7eb; }
        .btn-secondary:hover { background: #f3f4f6; }
        .icon { width: 56px; height: 56px; border-radius: 50%; background: #fee2e2; color: #b91c1c; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px; }
        .icon-success { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>
<div class="card">
    @if($alreadyDone)
        <div class="icon icon-success">
            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1>{{ __('You have been unsubscribed') }}</h1>
        <p>
            @if($lead)
                <span class="email">{{ $lead->email }}</span>
                {{ __('will no longer receive emails from this campaign.') }}
            @else
                {{ __('Your address will no longer receive emails from this campaign.') }}
            @endif
        </p>
    @else
        <div class="icon">
            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12"/>
            </svg>
        </div>
        <h1>{{ __('Unsubscribe?') }}</h1>
        <p>
            @if($lead)
                {{ __('Confirm that you want to unsubscribe') }}
                <span class="email">{{ $lead->email }}</span>
                {{ __('from this campaign.') }}
            @else
                {{ __('Confirm that you want to unsubscribe from this campaign.') }}
            @endif
        </p>
        <form method="POST" action="{{ route('outreach.unsubscribe.confirm', $lead?->uuid ?? request()->route('uuid')) }}" class="actions">
            @csrf
            <button type="submit" class="btn-primary">{{ __('Yes, unsubscribe me') }}</button>
            <a href="#" onclick="window.close(); return false;" class="btn btn-secondary" style="text-decoration: none; display: inline-block;">{{ __('Cancel') }}</a>
        </form>
    @endif
</div>
</body>
</html>
