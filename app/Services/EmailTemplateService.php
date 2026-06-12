<?php

namespace App\Services;

use App\Mail\TemplateMail;
use App\Models\Company;
use App\Models\Consultant;
use App\Models\EmailTemplate;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailTemplateService
{
    public function resolve(string $slug): ?EmailTemplate
    {
        $template = EmailTemplate::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if ($template) {
            return $template;
        }

        $defaults = config("emails.templates.{$slug}");
        if (!$defaults) {
            return null;
        }

        return new EmailTemplate([
            'slug' => $slug,
            'name' => $defaults['name'] ?? $slug,
            'description' => $defaults['description'] ?? null,
            'mailer' => $defaults['mailer'] ?? 'noreply',
            'reply_to' => $defaults['reply_to'] ?? 'help',
            'subject' => $defaults['subject'] ?? '',
            'body_html' => $defaults['body'] ?? '',
            'placeholders' => $defaults['placeholders'] ?? [],
            'is_active' => true,
        ]);
    }

    /**
     * @param  string|array<int, string>  $to
     * @param  array<string, mixed>  $variables
     */
    public function send(string $slug, string|array $to, array $variables = []): bool
    {
        $template = $this->resolve($slug);
        if (!$template) {
            Log::warning('Email template not found', ['slug' => $slug]);

            return false;
        }

        try {
            $mailer = $this->mailerName($template->mailer);

            $mailable = (new TemplateMail($template, $variables))->mailer($mailer);

            Mail::mailer($mailer)->to($to)->send($mailable);

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send templated email', [
                'slug' => $slug,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendToUser(string $slug, User $user, array $variables = []): bool
    {
        return $this->send($slug, $user->email, array_merge([
            'user_name' => $user->name,
            'user_email' => $user->email,
        ], $variables));
    }

    public function sendToConsultant(string $slug, Consultant $consultant, array $variables = []): bool
    {
        return $this->send($slug, $consultant->email, array_merge([
            'user_name' => $consultant->name,
            'user_email' => $consultant->email,
            'company_name' => $consultant->company_name,
            'dashboard_url' => route('consultant.dashboard'),
        ], $variables));
    }

    public function sendSystemAlert(string $subject, string $message, array $context = []): bool
    {
        $to = config('emails.alert_to');

        return $this->send('system_alert', $to, [
            'alert_subject' => $subject,
            'alert_message' => $message,
            'alert_context' => json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function sendTeamInvitation(\App\Models\CompanyInvitation $invitation): bool
    {
        $invitation->loadMissing(['company', 'companyCustomRole', 'inviter']);

        $roleName = $invitation->companyCustomRole?->role_name
            ?? ($invitation->company_custom_role_id ? 'Staff' : 'Owner');

        return $this->send('team_invitation', $invitation->email, [
            'invitee_email' => $invitation->email,
            'company_name' => $invitation->company?->name ?? 'your company',
            'inviter_name' => $invitation->inviter?->name ?? 'A team admin',
            'role_name' => $roleName,
            'invitation_url' => route('invitations.accept', $invitation->token),
            'expires_at' => $invitation->expires_at?->format('F j, Y') ?? now()->addDays(7)->format('F j, Y'),
        ]);
    }

    public function sendPaymentNotifications(PaymentTransaction $transaction): void
    {
        $transaction->loadMissing(['company', 'subscription.plan']);
        $recipients = $this->companyRecipientEmails($transaction->company_id);
        if ($recipients === []) {
            return;
        }

        $company = $transaction->company;
        $planName = $transaction->subscription?->plan?->plan_name ?? 'Subscription';
        $vars = [
            'company_name' => $company?->name ?? 'Your company',
            'plan_name' => $planName,
            'amount' => number_format((float) $transaction->amount, 2),
            'currency' => strtoupper((string) $transaction->currency),
            'expires_at' => $transaction->subscription?->expires_at?->format('F j, Y') ?? '—',
            'billing_url' => route('subscriptions.billing'),
            'invoice_number' => $transaction->invoice_number ?: ('TXN-' . $transaction->id),
            'invoice_url' => $transaction->invoice_url ?: route('subscriptions.billing'),
            'paid_at' => ($transaction->paid_at ?? now())->format('F j, Y g:i A'),
            'description' => $transaction->description ?: $planName,
        ];

        foreach ($recipients as $email) {
            $user = User::where('email', $email)->first();
            $payload = array_merge($vars, [
                'user_name' => $user?->name ?? 'there',
            ]);
            $this->send('subscription_confirmed', $email, $payload);
            $this->send('invoice_receipt', $email, $payload);
        }
    }

    /**
     * @return list<string>
     */
    public function companyRecipientEmails(?int $companyId): array
    {
        if (!$companyId) {
            return [];
        }

        $company = Company::find($companyId);
        $emails = [];

        if ($company?->email) {
            $emails[] = $company->email;
        }

        $owners = User::query()
            ->whereHas('companyRoles', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->where('is_active', true)
                    ->whereNull('company_custom_role_id');
            })
            ->pluck('email');

        return array_values(array_unique(array_filter(array_merge($emails, $owners->all()))));
    }

    protected function mailerName(string $key): string
    {
        return mail_transport_for_mailbox($key);
    }
}
