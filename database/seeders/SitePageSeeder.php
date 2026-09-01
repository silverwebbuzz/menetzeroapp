<?php

namespace Database\Seeders;

use App\Models\SitePage;
use Illuminate\Database\Seeder;

/**
 * Public policy pages.
 *
 * /terms, /refunds and /privacy are routed and linked from the marketing
 * footer, but no row ever existed, so PageController::show()'s firstOrFail()
 * returned 404 on all three. Payment gateways check these during onboarding.
 *
 * Bodies are HTML: public/page.blade.php renders them with {!! !!}. The
 * {{placeholder}} tokens are substituted from site settings by
 * SitePage::renderedBody(), which escapes each value -- so company details
 * stay editable in admin rather than hardcoded here, and only the keys in
 * SiteContentController::$settingKeys resolve.
 *
 * updateOrCreate keyed on slug: re-running must not duplicate a page or
 * silently overwrite wording that has been edited in admin since. To
 * deliberately reset a page, delete the row first and re-run.
 */
class SitePageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $slug => $page) {
            SitePage::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $page['title'],
                    'body' => $page['body'],
                    'is_published' => true,
                    'sort_order' => $page['sort_order'],
                ]
            );
        }
    }

    /** @return array<string, array{title: string, sort_order: int, body: string}> */
    protected function pages(): array
    {
        return [
            'terms' => [
                'title' => 'Terms & Conditions',
                'sort_order' => 10,
                'body' => $this->terms(),
            ],
            'refunds' => [
                'title' => 'Refunds & Cancellations',
                'sort_order' => 20,
                'body' => $this->refunds(),
            ],
            'privacy' => [
                'title' => 'Privacy Policy',
                'sort_order' => 30,
                'body' => $this->privacy(),
            ],
        ];
    }

    protected function terms(): string
    {
        return <<<'HTML'
<p><em>Last updated: 1 September 2026</em></p>

<p>These terms govern your use of the {{brand_name}} platform, operated by
{{company_legal_name}} ("we", "us"). By creating an account or purchasing a
subscription you agree to them. Please read them alongside our
<a href="/privacy">Privacy Policy</a> and
<a href="/refunds">Refunds &amp; Cancellations</a> policy.</p>

<h2>1. The service</h2>
<p>{{brand_name}} is a software platform for greenhouse gas accounting and
sustainability disclosure. It helps you record activity data, apply emission
factors, and produce reports aligned to frameworks including the GHG Protocol,
IFRS S1 and S2, and GRI.</p>

<h2>2. Outputs are your responsibility</h2>
<p>Reports, calculations and disclosures produced by the platform are
<strong>draft working papers prepared from data you supply</strong>. They are
not an audit, not third-party assurance, and not regulatory certification.</p>
<p>You are responsible for the accuracy and completeness of the data you enter,
for reviewing every figure before relying on it, and for obtaining independent
assurance where a regulator, exchange or counterparty requires it. Emission
factors are drawn from published sources and are applied as documented in the
platform; selecting the appropriate factor for your circumstances remains your
decision.</p>

<h2>3. Accounts</h2>
<p>You must give accurate registration details and keep your credentials
secure. You are responsible for activity under your account, including that of
users you invite. Tell us promptly at {{support_email}} if you believe an
account has been compromised.</p>
<p>Where a consultancy manages a workspace on behalf of a client, the
consultancy is responsible for the permissions it grants and for its own
engagement terms with that client.</p>

<h2>4. Subscriptions and payment</h2>
<p>Paid plans are billed annually in advance unless your order says otherwise.
Prices are shown before you confirm a purchase, and the amount displayed at
checkout is the amount payable.</p>
<p>Payments are processed by our payment provider. We do not receive or store
your full card details. Where a currency is unavailable through the provider,
we may charge the equivalent published price in a supported currency; this is
shown to you before payment and recorded on your invoice.</p>
<p>Subscriptions renew at the end of each term unless cancelled. Cancellation
and refund rights are set out in our
<a href="/refunds">Refunds &amp; Cancellations</a> policy.</p>

<h2>5. Your data</h2>
<p>You retain ownership of the data you upload. You grant us the licence needed
to host and process it so we can provide the service. We use it to operate the
platform for you, and as described in our <a href="/privacy">Privacy
Policy</a>. You can export your data while your subscription is active.</p>

<h2>6. Acceptable use</h2>
<p>Do not use the platform to break the law, infringe others' rights, upload
malicious code, attempt to gain unauthorised access, or resell access outside
an agreed consultancy or reseller arrangement. We may suspend an account that
puts the platform or other customers at risk, and will tell you why.</p>

<h2>7. Availability</h2>
<p>We work to keep the service available but do not guarantee uninterrupted
access. Maintenance, updates and factors outside our control may cause
downtime. Any service-level commitment applies only if stated in a separate
written agreement.</p>

<h2>8. Liability</h2>
<p>Nothing in these terms excludes liability that cannot lawfully be excluded.
Subject to that, we are not liable for indirect or consequential loss, or for
loss of profit, revenue, or anticipated savings. Our total liability arising
from the service is limited to the amount you paid us in the twelve months
before the claim.</p>
<p>In particular, we are not liable for decisions taken, or regulatory
positions adopted, on the basis of reports produced by the platform: those
outputs depend on the data you enter and require your own review.</p>

<h2>9. Changes</h2>
<p>We may update these terms as the service develops. Material changes will be
notified by email or in the platform before they take effect. Continued use
after that constitutes acceptance.</p>

<h2>10. Governing law</h2>
<p>These terms are governed by the laws of {{country}}, and the courts of
{{country}} have exclusive jurisdiction, unless a mandatory consumer
protection law in your own country gives you a different right.</p>

<h2>11. Contact</h2>
<p>{{company_legal_name}}<br>
{{address_line}}, {{city}}, {{country}}<br>
Email: {{support_email}}<br>
Phone: {{support_phone}}</p>
HTML;
    }

    protected function refunds(): string
    {
        return <<<'HTML'
<p><em>Last updated: 1 September 2026</em></p>

<p>This policy explains how cancellations and refunds work for
{{brand_name}} subscriptions, operated by {{company_legal_name}}.</p>

<h2>Try before you pay</h2>
<p>{{brand_name}} has a free plan. We encourage you to use it to confirm the
platform fits your needs before purchasing, as paid plans are billed annually
in advance.</p>

<h2>Cancelling a subscription</h2>
<p>You can cancel at any time from <strong>Plan &amp; billing</strong> in your
account, or by emailing {{support_email}}.</p>
<p>Cancellation takes effect <strong>at the end of your current paid
term</strong>. Your plan stays fully active until that date, and it will not
renew afterwards. Cancelling does not, by itself, trigger a refund of the
current term.</p>

<h2>Refunds</h2>
<p><strong>Within 14 days of a first purchase.</strong> If you have just bought
a paid plan for the first time and it is not right for you, contact
{{support_email}} within 14 days of payment and we will refund it in full.</p>
<p><strong>Renewals.</strong> If an annual renewal was charged and you had
intended to cancel, contact us within 14 days of the charge and we will refund
it, provided the renewed term has not been substantially used.</p>
<p><strong>Service failure.</strong> If the platform is materially unavailable
or does not work as described and we cannot resolve it in reasonable time, we
will refund the affected portion of your term.</p>
<p><strong>Duplicate or incorrect charges.</strong> Refunded in full, always.
Tell us and we will correct it.</p>

<h2>What is not normally refunded</h2>
<ul>
<li>Part-used annual terms outside the periods above — access continues to the
end of the term instead.</li>
<li>Consultancy client slots that have been assigned to a client workspace and
used to produce reports.</li>
<li>Fees for bespoke onboarding, data migration or advisory work already
delivered.</li>
</ul>
<p>If your circumstances are not covered here, contact us. We would rather
discuss it than have you feel unfairly treated.</p>

<h2>How refunds are paid</h2>
<p>Approved refunds are returned to the original payment method, normally
within 5–10 business days of approval, depending on your bank or card issuer.
We cannot refund to a different method.</p>
<p>Where the original payment was taken in a currency other than the one
quoted, the refund is issued in the currency actually charged. The amount you
receive may differ slightly from the amount you paid because of exchange rate
movement between the two dates; that difference is set by your bank, not by
us.</p>

<h2>Requesting a refund</h2>
<p>Email {{support_email}} with your account name, the invoice number, and the
reason. We aim to acknowledge within 2 business days and to decide within 5.
If we decline, we will explain why.</p>

<h2>Contact</h2>
<p>{{company_legal_name}}<br>
{{address_line}}, {{city}}, {{country}}<br>
Email: {{support_email}}<br>
Phone: {{support_phone}}</p>
HTML;
    }

    protected function privacy(): string
    {
        return <<<'HTML'
<p><em>Last updated: 1 September 2026</em></p>

<p>{{company_legal_name}} ("we", "us") operates the {{brand_name}} platform.
This policy explains what personal data we collect, why, and what rights you
have over it.</p>

<h2>Who is responsible</h2>
<p>We are the controller for account and billing data. For the sustainability
data you upload into your workspace, you are the controller and we act as your
processor, handling it on your instructions to provide the service.</p>

<h2>What we collect</h2>
<ul>
<li><strong>Account data</strong> — name, work email, phone, company name and
role, so we can create and secure your account.</li>
<li><strong>Billing data</strong> — company legal name, billing address, tax
registration number, invoice history. Card details are handled by our payment
provider and are never stored on our systems.</li>
<li><strong>Workspace data</strong> — the activity, energy, emissions and
disclosure information you enter. This is mostly organisational rather than
personal, though it can include names of people you record as owners of a risk
or contact points for a site.</li>
<li><strong>Technical data</strong> — IP address, browser type, pages visited
and timestamps, kept to keep the service secure and diagnose faults.</li>
</ul>

<h2>Why we use it</h2>
<ul>
<li>To provide the platform and produce the reports you ask for — performance
of our contract with you.</li>
<li>To take payment and issue invoices — contract and legal obligation.</li>
<li>To send service messages such as receipts, renewal notices and security
alerts — contract and legitimate interests.</li>
<li>To keep the platform secure, prevent abuse, and improve reliability —
legitimate interests.</li>
<li>To meet accounting and tax record-keeping duties — legal obligation.</li>
</ul>
<p>We do not sell your personal data. We do not use your workspace data to
train machine learning models for other customers.</p>

<h2>Marketing</h2>
<p>We send marketing email only where you have opted in or where you are an
existing customer and the message concerns a similar service. Every marketing
email carries an unsubscribe link. Opting out never affects service messages
such as receipts or security notices.</p>

<h2>Who we share it with</h2>
<p>We share personal data only with providers who help us run the service, and
only as far as needed: hosting and infrastructure, the payment provider that
processes your card, and our email delivery provider. Each is bound to protect
it and to use it only on our instructions.</p>
<p>Where a consultancy manages your workspace, its authorised users can see the
data in that workspace. We may also disclose data where the law requires it.</p>

<h2>International transfers</h2>
<p>Our providers may process data outside {{country}}. Where that happens we
rely on appropriate safeguards, such as standard contractual clauses, so your
data keeps an equivalent level of protection.</p>

<h2>How long we keep it</h2>
<p>Account and workspace data is kept while your account is active. After
closure we delete or anonymise it within 90 days, except records we must retain
for tax and accounting purposes — invoices in particular — which are kept for
the period the law requires.</p>

<h2>Security</h2>
<p>Access is protected by authentication and role-based permissions, traffic is
encrypted in transit, and passwords are stored hashed. Invoices and uploaded
documents are held in private storage that is not publicly reachable. No system
is perfectly secure, but we work to keep this one sound and to tell you
promptly if something goes wrong.</p>

<h2>Your rights</h2>
<p>You can ask us to give you a copy of your personal data, correct it, delete
it, restrict or object to how we use it, or provide it in a portable format.
You can also withdraw consent where we relied on it. Email {{support_email}}
and we will respond within 30 days.</p>
<p>If you are unhappy with our response you may complain to your local data
protection authority.</p>

<h2>Cookies</h2>
<p>We use cookies that are necessary for the platform to work — keeping you
signed in, protecting forms against cross-site request forgery, and
remembering your display preferences such as currency. We do not use
advertising cookies. Blocking necessary cookies will stop you being able to
sign in.</p>

<h2>Changes</h2>
<p>We will post any update here and change the date above. Material changes
will be notified by email or in the platform.</p>

<h2>Contact</h2>
<p>{{company_legal_name}}<br>
{{address_line}}, {{city}}, {{country}}<br>
Email: {{support_email}}<br>
Phone: {{support_phone}}</p>
HTML;
    }
}
