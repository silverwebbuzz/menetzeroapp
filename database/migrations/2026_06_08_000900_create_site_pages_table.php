<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Editable content for the public policy pages required for payment gateway
 * onboarding (Contact, Terms, Refunds & Cancellations, Privacy). Bodies are
 * HTML and may use {{placeholders}} for site settings (e.g. {{support_email}}).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('body')->nullable();
            $table->boolean('is_published')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        foreach ($this->pages() as $i => $page) {
            DB::table('site_pages')->insert([
                'slug' => $page['slug'],
                'title' => $page['title'],
                'body' => $page['body'],
                'is_published' => true,
                'sort_order' => $i + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_pages');
    }

    private function pages(): array
    {
        return [
            [
                'slug' => 'contact',
                'title' => 'Contact Us',
                'body' => <<<'HTML'
<p>We'd love to hear from you. Whether you have a question about our plans, billing, a technical issue, or anything else, our team is ready to help.</p>
<p>The fastest way to reach us is by email. We aim to respond to all enquiries within one business day.</p>
HTML,
            ],
            [
                'slug' => 'terms',
                'title' => 'Terms & Conditions',
                'body' => <<<'HTML'
<p><em>Last updated: this page is maintained by {{company_legal_name}}.</em></p>

<h2>1. Introduction</h2>
<p>These Terms &amp; Conditions ("Terms") govern your access to and use of the {{brand_name}} carbon accounting platform and related services (the "Service") provided by {{company_legal_name}} ("we", "us", "our"). By creating an account or purchasing a subscription, you agree to these Terms.</p>

<h2>2. The Service</h2>
<p>{{brand_name}} provides software for measuring, tracking and reporting greenhouse gas emissions (Scope 1, Scope 2 and, as an add-on, Scope 3). Features available to you depend on the subscription plan you select.</p>

<h2>3. Accounts</h2>
<p>You are responsible for maintaining the confidentiality of your account credentials and for all activity that occurs under your account. You must provide accurate and complete information when registering.</p>

<h2>4. Subscriptions &amp; Billing</h2>
<ul>
  <li>Paid plans are billed on an <strong>annual</strong> basis in advance.</li>
  <li>Prices are shown on our pricing page. Payments processed through our payment gateway are charged in Indian Rupees (INR).</li>
  <li>Unless you cancel auto-renewal, subscriptions renew automatically at the end of each term.</li>
  <li>Applicable taxes may be added at checkout.</li>
</ul>

<h2>5. Acceptable Use</h2>
<p>You agree not to misuse the Service, attempt to gain unauthorised access, reverse engineer the platform, or use it in violation of any applicable law.</p>

<h2>6. Intellectual Property</h2>
<p>All rights, title and interest in the Service, including software, content and trademarks, remain with {{company_legal_name}}. You retain ownership of the data you submit.</p>

<h2>7. Limitation of Liability</h2>
<p>The Service is provided on an "as is" basis. To the maximum extent permitted by law, {{company_legal_name}} shall not be liable for any indirect, incidental or consequential damages arising from your use of the Service.</p>

<h2>8. Changes to these Terms</h2>
<p>We may update these Terms from time to time. Material changes will be communicated through the platform or by email.</p>

<h2>9. Contact</h2>
<p>For any questions about these Terms, contact us at {{support_email}}.</p>
HTML,
            ],
            [
                'slug' => 'refunds',
                'title' => 'Refunds & Cancellations',
                'body' => <<<'HTML'
<p><em>This Refund &amp; Cancellation Policy applies to all subscriptions purchased from {{company_legal_name}} ({{brand_name}}).</em></p>

<h2>1. Subscription Cancellation</h2>
<p>You may cancel the auto-renewal of your subscription at any time from your account under <strong>Billing → My Subscription</strong>. When you cancel, your plan remains active until the end of the current billing period, after which it will not renew. You will not be charged again once auto-renewal is cancelled.</p>

<h2>2. Refund Eligibility</h2>
<ul>
  <li>You may request a full refund within <strong>7 days</strong> of your initial purchase, provided the account has not made substantial use of paid features (for example, generating reports or adding multiple locations/users beyond the free tier).</li>
  <li>Renewal payments are generally non-refundable. If auto-renewal occurred unexpectedly, contact us within 7 days of the renewal charge and we will review the request.</li>
  <li>One-time setup fees and professional service add-ons (such as Scope 3 services) are non-refundable once work has commenced.</li>
</ul>

<h2>3. How to Request a Refund</h2>
<p>Email {{support_email}} with your account email and the payment reference. Approved refunds are processed to the original payment method within <strong>5–10 business days</strong>. Timing of the credit appearing on your statement depends on your bank or card issuer.</p>

<h2>4. Failed or Duplicate Payments</h2>
<p>If you were charged more than once for the same subscription, or charged for a payment that failed to activate your plan, contact {{support_email}} and we will refund the duplicate/erroneous charge in full.</p>

<h2>5. Contact</h2>
<p>For any billing or refund questions, reach us at {{support_email}} or call {{support_phone}}.</p>
HTML,
            ],
            [
                'slug' => 'privacy',
                'title' => 'Privacy Policy',
                'body' => <<<'HTML'
<p><em>{{company_legal_name}} ({{brand_name}}) is committed to protecting your privacy.</em></p>

<h2>1. Information We Collect</h2>
<p>We collect information you provide when you register and use the Service, including your name, email, company details, and the emissions/activity data you enter. We also collect limited technical data such as log and usage information.</p>

<h2>2. How We Use Information</h2>
<p>We use your information to provide and improve the Service, process payments, calculate emissions and generate reports, communicate with you, and meet legal obligations.</p>

<h2>3. Payments</h2>
<p>Payments are processed by third-party payment gateways. We do not store your full card details on our servers; card data is handled directly by the payment provider in accordance with their security standards.</p>

<h2>4. Data Sharing</h2>
<p>We do not sell your personal data. We share data only with service providers who help us operate the platform (such as hosting and payment processing), and where required by law.</p>

<h2>5. Data Security</h2>
<p>We use industry-standard measures to protect your data, including encryption in transit and access controls.</p>

<h2>6. Your Rights</h2>
<p>You may request access to, correction of, or deletion of your personal data by contacting {{support_email}}.</p>

<h2>7. Contact</h2>
<p>For privacy enquiries, contact {{support_email}}.</p>
HTML,
            ],
        ];
    }
};
