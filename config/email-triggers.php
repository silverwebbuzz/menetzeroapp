<?php

/**
 * Where each email template is sent from — pages, forms, and source files.
 * Shown on Admin → Email Templates for ops reference.
 *
 * Keys match email_templates.slug / config/emails.php template keys.
 *
 * @return array<string, list<array{
 *   label: string,
 *   route?: string|null,
 *   path?: string|null,
 *   file?: string|null,
 *   note?: string|null,
 *   status?: 'live'|'planned'|'internal',
 * }>>
 */
return [

    'welcome' => [
        [
            'label' => 'Client sign-up form',
            'route' => 'register',
            'file' => 'app/Http/Controllers/Auth/RegisterController.php',
            'status' => 'live',
        ],
        [
            'label' => 'Google sign-up (new company user)',
            'file' => 'app/Http/Controllers/Auth/OAuthController.php',
            'status' => 'live',
        ],
    ],

    'welcome_consultant' => [
        [
            'label' => 'Consultant agency registration',
            'route' => 'consultant.register',
            'file' => 'app/Http/Controllers/Consultant/AuthController.php',
            'status' => 'live',
        ],
    ],

    'email_verification' => [
        [
            'label' => 'After client registration (when verification enabled)',
            'route' => 'register',
            'file' => 'app/Http/Controllers/Auth/RegisterController.php',
            'note' => 'Controlled by EMAIL_VERIFICATION_ON_REGISTER in .env',
            'status' => 'live',
        ],
        [
            'label' => 'Resend verification (Laravel auth)',
            'file' => 'app/Models/User.php',
            'status' => 'live',
        ],
    ],

    'password_reset' => [
        [
            'label' => 'Forgot password form',
            'route' => 'password.request',
            'path' => '/forgot-password',
            'file' => 'app/Http/Controllers/Auth/ForgotPasswordController.php',
            'status' => 'live',
        ],
    ],

    'password_changed' => [
        [
            'label' => 'Profile → change password',
            'route' => 'client.profile',
            'path' => '/profile',
            'file' => 'app/Http/Controllers/ProfileController.php',
            'status' => 'live',
        ],
    ],

    'team_invitation' => [
        [
            'label' => 'Company portal → Roles & team → invite member',
            'route' => 'roles.index',
            'path' => '/roles',
            'file' => 'app/Services/CompanyInvitationService.php',
            'note' => 'Login required. Also sent on resend from staff management.',
            'status' => 'live',
        ],
        [
            'label' => 'Consultant portal → Team → invite member',
            'route' => 'consultant.team.index',
            'file' => 'app/Http/Controllers/StaffManagementController.php',
            'status' => 'live',
        ],
        [
            'label' => 'Invitation accept page (recipient lands here)',
            'route' => 'invitations.accept',
            'path' => '/invitations/accept/{token}',
            'file' => 'app/Http/Controllers/InvitationController.php',
            'status' => 'live',
        ],
    ],

    'subscription_confirmed' => [
        [
            'label' => 'Subscription checkout — successful payment',
            'route' => 'subscriptions.upgrade',
            'path' => '/subscriptions/upgrade',
            'file' => 'app/Services/PaymentCompletionService.php',
            'note' => 'Sent to company owners after Razorpay/Cashfree payment completes.',
            'status' => 'live',
        ],
    ],

    'invoice_receipt' => [
        [
            'label' => 'Same as subscription confirmed (payment receipt)',
            'route' => 'subscriptions.billing',
            'path' => '/subscriptions/billing',
            'file' => 'app/Services/PaymentCompletionService.php',
            'status' => 'live',
        ],
    ],

    'system_alert' => [
        [
            'label' => 'Payment webhook failures',
            'file' => 'app/Http/Controllers/PaymentWebhookController.php',
            'note' => 'Delivered to MAIL_ALERT_TO (help@ by default) — internal ops only.',
            'status' => 'internal',
        ],
        [
            'label' => 'Subscription / checkout errors',
            'file' => 'app/Http/Controllers/Client/SubscriptionController.php',
            'status' => 'internal',
        ],
    ],

    'contact_sales_ack' => [
        [
            'label' => 'Public contact page — sales mailto link',
            'route' => 'contact',
            'path' => '/contact',
            'file' => 'resources/views/public/contact.blade.php',
            'note' => 'Users email sales via mailto today. This template is the auto-reply when a sales contact form is added.',
            'status' => 'planned',
        ],
        [
            'label' => 'Sales inbox address (Site Content)',
            'route' => 'admin.site-content.index',
            'file' => 'app/Http/Controllers/Admin/SiteContentController.php',
            'note' => 'Contact page shows sales_email from Site Content (often hello@menetzero.com).',
            'status' => 'live',
        ],
    ],

    'contact_support_ack' => [
        [
            'label' => 'Public contact page — support mailto link',
            'route' => 'contact',
            'path' => '/contact',
            'file' => 'resources/views/public/contact.blade.php',
            'note' => 'Users email support via mailto today. This template is the auto-reply when a support contact form is added.',
            'status' => 'planned',
        ],
        [
            'label' => 'Support inbox address (Site Content)',
            'route' => 'admin.site-content.index',
            'file' => 'app/Http/Controllers/Admin/SiteContentController.php',
            'note' => 'Contact page shows support_email from Site Content (often help@menetzero.com).',
            'status' => 'live',
        ],
    ],

];
