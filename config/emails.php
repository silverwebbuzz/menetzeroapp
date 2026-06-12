<?php

/**
 * MeNetZero transactional email registry.
 *
 * Addresses (see config/mail.php):
 *   hello@menetzero.com   — sales, demos, partnerships
 *   help@menetzero.com    — support, billing help (also Reply-To on noreply mail)
 *   noreply@menetzero.com — automated system mail
 */

return [

    'alert_to' => env('MAIL_ALERT_TO', env('MAIL_HELP_ADDRESS', 'help@menetzero.com')),

    /** Optional fallback BCC; admin can override via Site settings → Email Tester. */
    'global_bcc' => env('MAIL_GLOBAL_BCC', ''),

    'verification_on_register' => env('EMAIL_VERIFICATION_ON_REGISTER', false),

    'templates' => [

        'welcome' => [
            'name' => 'Welcome — company registration',
            'description' => 'Sent when a company user registers (email or Google).',
            'mailer' => 'noreply',
            'reply_to' => 'help',
            'subject' => 'Welcome to {{app_name}}',
            'placeholders' => ['user_name', 'user_email', 'dashboard_url', 'help_email', 'app_name'],
            'body' => <<<'HTML'
<p>Hello <strong>{{user_name}}</strong>,</p>
<p>Thank you for joining {{app_name}}. Your account is ready with <strong>{{user_email}}</strong>.</p>
<p>You can set up locations, enter emissions data, and build your GHG inventory from your dashboard.</p>
<p style="text-align:center;margin:28px 0;"><a href="{{dashboard_url}}" style="display:inline-block;padding:12px 28px;background:#16a34a;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;">Open dashboard</a></p>
<p>Questions? Contact us at <a href="mailto:{{help_email}}">{{help_email}}</a>.</p>
<p>Best regards,<br>The {{app_name}} Team</p>
HTML,
        ],

        'welcome_consultant' => [
            'name' => 'Welcome — consultant registration',
            'description' => 'Sent when a consultant creates an agency account.',
            'mailer' => 'noreply',
            'reply_to' => 'help',
            'subject' => 'Welcome to {{app_name}} Consultant Portal',
            'placeholders' => ['user_name', 'user_email', 'company_name', 'dashboard_url', 'help_email', 'app_name'],
            'body' => <<<'HTML'
<p>Hello <strong>{{user_name}}</strong>,</p>
<p>Your consultant account for <strong>{{company_name}}</strong> is ready.</p>
<p>Complete your directory profile, add managed clients, and explore agency packs from your dashboard.</p>
<p style="text-align:center;margin:28px 0;"><a href="{{dashboard_url}}" style="display:inline-block;padding:12px 28px;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;">Agency dashboard</a></p>
<p>Need help? Email <a href="mailto:{{help_email}}">{{help_email}}</a>.</p>
HTML,
        ],

        'email_verification' => [
            'name' => 'Email verification',
            'description' => 'Sent when a user must verify their email address.',
            'mailer' => 'noreply',
            'reply_to' => 'help',
            'subject' => 'Verify your {{app_name}} email',
            'placeholders' => ['user_name', 'verify_url', 'help_email', 'app_name'],
            'body' => <<<'HTML'
<p>Hello {{user_name}},</p>
<p>Please confirm your email address to secure your {{app_name}} account.</p>
<p style="text-align:center;margin:28px 0;"><a href="{{verify_url}}" style="display:inline-block;padding:12px 28px;background:#16a34a;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;">Verify email</a></p>
<p>If you did not create an account, you can ignore this message.</p>
<p>This link expires in 60 minutes. Need help? <a href="mailto:{{help_email}}">{{help_email}}</a></p>
HTML,
        ],

        'password_reset' => [
            'name' => 'Password reset',
            'description' => 'Sent when a user requests a password reset link.',
            'mailer' => 'noreply',
            'reply_to' => 'help',
            'subject' => 'Reset your {{app_name}} password',
            'placeholders' => ['user_name', 'reset_url', 'help_email', 'app_name'],
            'body' => <<<'HTML'
<p>Hello {{user_name}},</p>
<p>We received a request to reset your password. Click the button below to choose a new one.</p>
<p style="text-align:center;margin:28px 0;"><a href="{{reset_url}}" style="display:inline-block;padding:12px 28px;background:#16a34a;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;">Reset password</a></p>
<p>If you did not request this, ignore this email — your password will not change.</p>
<p>This link expires in 60 minutes. Support: <a href="mailto:{{help_email}}">{{help_email}}</a></p>
HTML,
        ],

        'password_changed' => [
            'name' => 'Password changed',
            'description' => 'Sent after a user successfully updates their password.',
            'mailer' => 'noreply',
            'reply_to' => 'help',
            'subject' => 'Your {{app_name}} password was changed',
            'placeholders' => ['user_name', 'changed_at', 'help_email', 'app_name'],
            'body' => <<<'HTML'
<p>Hello {{user_name}},</p>
<p>This confirms your {{app_name}} password was changed on <strong>{{changed_at}}</strong>.</p>
<p>If you made this change, no action is needed.</p>
<p>If you did <strong>not</strong> change your password, contact us immediately at <a href="mailto:{{help_email}}">{{help_email}}</a>.</p>
HTML,
        ],

        'team_invitation' => [
            'name' => 'Team invitation',
            'description' => 'Sent when a company admin invites someone to their workspace.',
            'mailer' => 'noreply',
            'reply_to' => 'help',
            'subject' => 'You\'re invited to {{company_name}} on {{app_name}}',
            'placeholders' => ['invitee_email', 'company_name', 'inviter_name', 'role_name', 'invitation_url', 'expires_at', 'app_name'],
            'body' => <<<'HTML'
<p>Hello,</p>
<p><strong>{{inviter_name}}</strong> invited you to join <strong>{{company_name}}</strong> on {{app_name}} as <strong>{{role_name}}</strong>.</p>
<p style="text-align:center;margin:28px 0;"><a href="{{invitation_url}}" style="display:inline-block;padding:12px 28px;background:#16a34a;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;">Accept invitation</a></p>
<p>This invitation expires on {{expires_at}}.</p>
HTML,
        ],

        'subscription_confirmed' => [
            'name' => 'Subscription confirmed',
            'description' => 'Sent after a successful plan purchase or upgrade.',
            'mailer' => 'noreply',
            'reply_to' => 'help',
            'subject' => 'Your {{plan_name}} subscription is active',
            'placeholders' => ['user_name', 'company_name', 'plan_name', 'amount', 'currency', 'expires_at', 'billing_url', 'help_email', 'app_name'],
            'body' => <<<'HTML'
<p>Hello {{user_name}},</p>
<p>Payment received — <strong>{{company_name}}</strong> is now on the <strong>{{plan_name}}</strong> plan.</p>
<ul>
<li>Amount: <strong>{{amount}} {{currency}}</strong></li>
<li>Renews / expires: <strong>{{expires_at}}</strong></li>
</ul>
<p style="text-align:center;margin:28px 0;"><a href="{{billing_url}}" style="display:inline-block;padding:12px 28px;background:#16a34a;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;">View billing</a></p>
<p>Billing questions: <a href="mailto:{{help_email}}">{{help_email}}</a></p>
HTML,
        ],

        'invoice_receipt' => [
            'name' => 'Invoice / receipt',
            'description' => 'Sent with payment receipt details and invoice link when available.',
            'mailer' => 'noreply',
            'reply_to' => 'help',
            'subject' => 'Receipt — {{invoice_number}} · {{app_name}}',
            'placeholders' => ['user_name', 'company_name', 'amount', 'currency', 'invoice_number', 'invoice_url', 'paid_at', 'description', 'help_email', 'app_name'],
            'body' => <<<'HTML'
<p>Hello {{user_name}},</p>
<p>Thank you for your payment.</p>
<table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;">
<tr><td style="padding:8px 0;color:#64748b;">Description</td><td style="padding:8px 0;"><strong>{{description}}</strong></td></tr>
<tr><td style="padding:8px 0;color:#64748b;">Amount</td><td style="padding:8px 0;"><strong>{{amount}} {{currency}}</strong></td></tr>
<tr><td style="padding:8px 0;color:#64748b;">Invoice #</td><td style="padding:8px 0;">{{invoice_number}}</td></tr>
<tr><td style="padding:8px 0;color:#64748b;">Paid on</td><td style="padding:8px 0;">{{paid_at}}</td></tr>
</table>
<p style="text-align:center;margin:28px 0;"><a href="{{invoice_url}}" style="display:inline-block;padding:12px 28px;background:#16a34a;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;">View invoice</a></p>
<p>Billing support: <a href="mailto:{{help_email}}">{{help_email}}</a></p>
HTML,
        ],

        'system_alert' => [
            'name' => 'System alert (internal)',
            'description' => 'Sent to the ops inbox when a critical system event occurs.',
            'mailer' => 'noreply',
            'reply_to' => 'help',
            'subject' => '[{{app_name}} Alert] {{alert_subject}}',
            'placeholders' => ['alert_subject', 'alert_message', 'alert_context', 'app_name', 'app_url'],
            'body' => <<<'HTML'
<p><strong>System alert</strong></p>
<p>{{alert_message}}</p>
<pre style="background:#f1f5f9;padding:12px;border-radius:8px;font-size:12px;white-space:pre-wrap;">{{alert_context}}</pre>
<p><a href="{{app_url}}">Open {{app_name}}</a></p>
HTML,
        ],

        'contact_sales_ack' => [
            'name' => 'Contact — sales acknowledgement',
            'description' => 'Auto-reply when someone contacts sales (hello@).',
            'mailer' => 'hello',
            'reply_to' => 'hello',
            'subject' => 'We received your message — {{app_name}}',
            'placeholders' => ['sender_name', 'sender_email', 'message_excerpt', 'hello_email', 'app_name'],
            'body' => <<<'HTML'
<p>Hello {{sender_name}},</p>
<p>Thank you for contacting {{app_name}}. Our team received your message and will respond shortly.</p>
<p style="background:#f8fafc;padding:12px;border-radius:8px;color:#475569;">{{message_excerpt}}</p>
<p>For demos and partnerships, email <a href="mailto:{{hello_email}}">{{hello_email}}</a>.</p>
HTML,
        ],

        'contact_support_ack' => [
            'name' => 'Contact — support acknowledgement',
            'description' => 'Auto-reply when someone contacts support (help@).',
            'mailer' => 'help',
            'reply_to' => 'help',
            'subject' => 'Support request received — {{app_name}}',
            'placeholders' => ['sender_name', 'sender_email', 'message_excerpt', 'help_email', 'app_name'],
            'body' => <<<'HTML'
<p>Hello {{sender_name}},</p>
<p>We received your support request and will get back to you as soon as possible.</p>
<p style="background:#f8fafc;padding:12px;border-radius:8px;color:#475569;">{{message_excerpt}}</p>
<p>Reference: {{sender_email}} · Reply to this thread or write to <a href="mailto:{{help_email}}">{{help_email}}</a>.</p>
HTML,
        ],

    ],
];
