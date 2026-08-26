{{--
    Shared transactional-email chrome (Phase 2 — Emails).

    ALL 14 configured templates render through this one file, plus the admin
    preview (Admin\EmailTemplateController::preview), ContactInboundMail and
    RawTestMail — five callers in total. Restyling here improves every
    transactional email at once, and the admin preview stays accurate for free.

    THE BODY IS NOT TOUCHED. {!! $bodyHtml !!} is admin-editable content stored
    in email_templates.body_html (falling back to config/emails.php). Every
    shipped body uses inline styles only — verified: zero `class="` across all
    14 templates — so the chrome owns no body styling and this rewrite cannot
    alter template content.

    WHY TABLES: the previous version used <div>s plus a <style> block. Outlook
    on Windows renders through Word, which ignores much of that — max-width and
    border-radius in particular — so the email lost its column and ran full
    width. The layout below is table-based with inline styles, the standard
    Outlook-safe pattern, with an MSO conditional to pin the width.

    The <style> block is KEPT even though the chrome no longer depends on it:
    admins can edit body_html, and older stored bodies may still reference the
    .body / .footer classes. Removing it would silently restyle their content.
    It is now a compatibility shim, not the layout mechanism.
--}}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="format-detection" content="telephone=no,date=no,address=no,email=no">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ config('app.name', 'MeNetZero') }}</title>
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings>
        <o:PixelsPerInch>96</o:PixelsPerInch>
    </o:OfficeDocumentSettings></xml></noscript>
    <![endif]-->
    <style>
        /* Compatibility shim for admin-edited bodies that may still use these
           class names. The chrome itself is styled inline, below. */
        body { margin: 0; padding: 0; background: #f1f5f9;
            font-family: Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #334155; line-height: 1.6; }
        .body { font-size: 15px; }
        .body p { margin: 0 0 14px; }
        .body a { color: #15803d; }
        .footer { font-size: 12px; color: #94a3b8; text-align: center; }
        .footer a { color: #64748b; }
        a { color: #15803d; }
        table { border-collapse: collapse; }
        img { border: 0; line-height: 100%; outline: none; text-decoration: none; }
        /* Phones: let the card use the full width. */
        @media only screen and (max-width: 620px) {
            .mnz-card { width: 100% !important; }
            .mnz-pad { padding-left: 20px !important; padding-right: 20px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;">

    @if(!empty($previewText))
        {{-- Inbox preview line: hidden in the rendered email, read by clients. --}}
        <div style="display:none;font-size:1px;color:#f1f5f9;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
            {{ \Illuminate\Support\Str::limit(trim(strip_tags($previewText)), 140) }}
        </div>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="background:#f1f5f9;margin:0;padding:0;">
        <tr>
            <td align="center" style="padding:24px 12px;">

                <!--[if mso]>
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"><tr><td>
                <![endif]-->

                <table role="presentation" class="mnz-card" width="600" cellpadding="0" cellspacing="0" border="0"
                    style="width:600px;max-width:600px;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;">

                    {{-- Brand --}}
                    <tr>
                        <td class="mnz-pad" align="center"
                            style="padding:22px 24px;border-bottom:1px solid #e2e8f0;">
                            <span style="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:18px;font-weight:700;color:#15803d;letter-spacing:-0.01em;">
                                {{ config('app.name', 'MeNetZero') }}
                            </span>
                        </td>
                    </tr>

                    {{-- Body — admin-editable content, rendered untouched --}}
                    <tr>
                        <td class="mnz-pad body"
                            style="padding:28px 24px;font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:15px;line-height:1.6;color:#334155;">
                            {!! $bodyHtml !!}
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td class="mnz-pad footer" align="center"
                            style="padding:16px 24px 20px;border-top:1px solid #e2e8f0;font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;line-height:1.55;color:#94a3b8;">
                            <p style="margin:0 0 6px;">This is an automated message from {{ config('app.name', 'MeNetZero') }}.</p>
                            <p style="margin:0;">Need help?
                                <a href="mailto:{{ config('mail.addresses.help.address', 'help@menetzero.com') }}" style="color:#64748b;">{{ config('mail.addresses.help.address', 'help@menetzero.com') }}</a>
                                &middot; Sales:
                                <a href="mailto:{{ config('mail.addresses.hello.address', 'hello@menetzero.com') }}" style="color:#64748b;">{{ config('mail.addresses.hello.address', 'hello@menetzero.com') }}</a>
                            </p>
                            <p style="margin:8px 0 0;">&copy; {{ date('Y') }} {{ config('app.name', 'MeNetZero') }}</p>
                        </td>
                    </tr>

                </table>

                <!--[if mso]>
                </td></tr></table>
                <![endif]-->

            </td>
        </tr>
    </table>
</body>
</html>
