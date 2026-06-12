<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ config('app.name', 'MeNetZero') }}</title>
    @if(!empty($previewText))
        <!-- preview: {{ strip_tags($previewText) }} -->
    @endif
    <style>
        body { margin: 0; padding: 0; background: #f8fafc; font-family: Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #334155; line-height: 1.6; }
        .wrap { max-width: 600px; margin: 0 auto; padding: 24px 16px; }
        .card { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
        .brand { padding: 20px 24px; border-bottom: 1px solid #e2e8f0; text-align: center; }
        .brand-name { font-size: 18px; font-weight: 700; color: #16a34a; margin: 0; }
        .body { padding: 28px 24px; font-size: 15px; }
        .body p { margin: 0 0 14px; }
        .body a { color: #16a34a; }
        .footer { padding: 16px 24px 20px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; text-align: center; }
        .footer a { color: #64748b; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="brand">
                <p class="brand-name">{{ config('app.name', 'MeNetZero') }}</p>
            </div>
            <div class="body">
                {!! $bodyHtml !!}
            </div>
            <div class="footer">
                <p style="margin:0 0 6px;">This is an automated message from {{ config('app.name', 'MeNetZero') }}.</p>
                <p style="margin:0;">Need help? <a href="mailto:{{ config('mail.addresses.help.address', 'help@menetzero.com') }}">{{ config('mail.addresses.help.address', 'help@menetzero.com') }}</a>
                · Sales: <a href="mailto:{{ config('mail.addresses.hello.address', 'hello@menetzero.com') }}">{{ config('mail.addresses.hello.address', 'hello@menetzero.com') }}</a></p>
                <p style="margin:8px 0 0;">&copy; {{ date('Y') }} {{ config('app.name', 'MeNetZero') }}</p>
            </div>
        </div>
    </div>
</body>
</html>
