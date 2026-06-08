<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\SiteSetting;
use App\Models\SitePage;

/**
 * Sets the correct branding/legal entity on already-seeded installs:
 * brand "MeNetZero" operated by parent company "Silver Webbuzz Private Limited".
 * Safe + idempotent — only touches the relationship wording and contact fields.
 */
return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            'company_legal_name' => 'Silver Webbuzz Private Limited',
            'brand_name' => 'MeNetZero',
            'support_phone' => '+91 9998010029',
            'country' => 'India',
            'business_hours' => 'Monday – Saturday, 10:00 AM to 7:00 PM (IST)',
            // Wrong default-region placeholders removed; add the registered
            // office address from Admin → Site Content.
            'address_line' => '',
            'city' => '',
        ];

        foreach ($settings as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // Update the brand/parent-company sentence in each policy page if the
        // original seeded wording is still present.
        $replacements = [
            'contact' => [
                "We'd love to hear from you. Whether you have a question about our plans, billing, a technical issue, or anything else, our team is ready to help.",
                "{{brand_name}} is a brand owned and operated by {{company_legal_name}}. Whether you have a question about our plans, billing, a technical issue, or anything else, our team is ready to help.",
            ],
            'terms' => [
                'These Terms &amp; Conditions ("Terms") govern your access to and use of the {{brand_name}} carbon accounting platform and related services (the "Service") provided by {{company_legal_name}} ("we", "us", "our"). By creating an account or purchasing a subscription, you agree to these Terms.',
                '{{brand_name}} is a brand owned and operated by {{company_legal_name}} ("we", "us", "our"). These Terms &amp; Conditions ("Terms") govern your access to and use of the {{brand_name}} carbon accounting platform and related services (the "Service"). By creating an account or purchasing a subscription, you agree to these Terms.',
            ],
            'refunds' => [
                'This Refund &amp; Cancellation Policy applies to all subscriptions purchased from {{company_legal_name}} ({{brand_name}}).',
                'This Refund &amp; Cancellation Policy applies to all subscriptions purchased from {{brand_name}}, a brand owned and operated by {{company_legal_name}}.',
            ],
            'privacy' => [
                '{{company_legal_name}} ({{brand_name}}) is committed to protecting your privacy.',
                '{{company_legal_name}}, which owns and operates the {{brand_name}} platform, is committed to protecting your privacy.',
            ],
        ];

        foreach ($replacements as $slug => [$old, $new]) {
            $page = SitePage::where('slug', $slug)->first();
            if ($page && str_contains((string) $page->body, $old)) {
                $page->body = str_replace($old, $new, $page->body);
                $page->save();
            }
        }
    }

    public function down(): void
    {
        // Branding/legal content is intentionally not reverted.
    }
};
