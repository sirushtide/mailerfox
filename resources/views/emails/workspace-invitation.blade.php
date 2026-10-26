<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="padding:32px 32px 24px;text-align:center;">
                            <div style="display:inline-block;width:56px;height:56px;line-height:56px;border-radius:12px;background-color:#4f46e5;color:#ffffff;font-size:18px;font-weight:700;text-align:center;">
                                {{ strtoupper(substr($workspace->name, 0, 2)) }}
                            </div>
                            <h1 style="margin:16px 0 8px;font-size:20px;font-weight:700;color:#111827;">{{ __("You're invited!") }}</h1>
                            <p style="margin:0;font-size:14px;color:#6b7280;line-height:1.5;">
                                <strong style="color:#111827;">{{ $inviter->full_name }}</strong> {{ __('has invited you to join') }}
                                <strong style="color:#111827;">{{ $workspace->name }}</strong> {{ __('on MailerFox.') }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 32px;text-align:center;">
                            <a href="{{ $acceptUrl }}"
                               style="display:inline-block;padding:12px 32px;background-color:#4f46e5;color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;border-radius:8px;">
                                {{ __('Accept Invitation') }}
                            </a>
                            <p style="margin:16px 0 0;font-size:12px;color:#9ca3af;">
                                {{ __('This invitation expires') }} {{ $invitation->expires_at->diffForHumans() }}.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 32px;border-top:1px solid #e5e7eb;">
                            <p style="margin:0;font-size:11px;color:#9ca3af;text-align:center;">
                                {{ __('If you didn\'t expect this invitation, you can safely ignore this email.') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
