<?php

namespace App\Services;

use App\Models\ClientSubscription;
use App\Models\ConsultantSubscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Phase 10a — offline renewal window detection + email nudges.
 * CTAs point to Request a package / Request clients (no self-serve checkout).
 */
class SubscriptionRenewalNudgeService
{
    public const WINDOW_DAYS = 45;

    /** Days-before-expiry buckets for email idempotency. */
    public const EMAIL_BUCKETS = [45, 14, 3];

    public function __construct(
        protected SubscriptionService $clientSubscriptions,
        protected ConsultantAgencyRenewalService $consultantRenewals,
        protected ConsultantAgencySubscriptionService $consultantSubscriptions,
        protected EmailTemplateService $emails,
    ) {
    }

    public function windowDays(): int
    {
        return self::WINDOW_DAYS;
    }

    /**
     * @return array{show: bool, expires_at: Carbon|null, days_left: int|null, plan_name: string|null, request_url: string|null}|null
     */
    public function companyNudgeForUser(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        $company = $user->getActiveCompany();
        if (!$company || !$company->isClient() || $company->isManagedClient()) {
            return null;
        }

        $subscription = $this->clientSubscriptions->getActiveSubscription($company->id, 'client');
        if (!$subscription || !$this->companyNeedsRenewalAttention($subscription)) {
            return null;
        }

        $expires = $subscription->expires_at;
        $daysLeft = (int) now()->startOfDay()->diffInDays($expires->copy()->startOfDay(), false);

        return [
            'show' => true,
            'expires_at' => $expires,
            'days_left' => max(0, $daysLeft),
            'plan_name' => $subscription->plan?->plan_name ?? 'your package',
            'request_url' => route('subscriptions.request-package'),
        ];
    }

    public function companyNeedsRenewalAttention(ClientSubscription $subscription): bool
    {
        if ($subscription->status !== 'active' || !$subscription->expires_at) {
            return false;
        }

        if ($this->isFreeOrTrialClientPlan($subscription)) {
            return false;
        }

        $expires = $subscription->expires_at->copy()->endOfDay();
        if ($expires->isPast()) {
            return true;
        }

        $windowStart = $expires->copy()->subDays($this->windowDays())->startOfDay();

        return now()->greaterThanOrEqualTo($windowStart);
    }

    public function isFreeOrTrialClientPlan(ClientSubscription $subscription): bool
    {
        $code = $subscription->plan?->plan_code;
        if (in_array($code, ['client_free'], true)) {
            return true;
        }

        $name = strtolower((string) ($subscription->plan?->plan_name ?? ''));

        return str_contains($name, 'free');
    }

    /**
     * Daily email sweep. Returns counts for CLI output.
     *
     * @return array{company_sent: int, consultant_sent: int, skipped: int}
     */
    public function sendDueReminderEmails(bool $dryRun = false): array
    {
        $sentCompany = 0;
        $sentConsultant = 0;
        $skipped = 0;

        foreach ($this->expiringCompanySubscriptions() as $subscription) {
            $bucket = $this->matchingEmailBucket($subscription->expires_at);
            if ($bucket === null) {
                $skipped++;
                continue;
            }
            if ($this->alreadySentBucket($subscription->metadata ?? [], $bucket)) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $sentCompany++;
                continue;
            }

            if ($this->sendCompanyEmail($subscription, $bucket)) {
                $sentCompany++;
            } else {
                $skipped++;
            }
        }

        foreach ($this->expiringConsultantSubscriptions() as $subscription) {
            if (!$this->consultantRenewals->needsRenewalFlow((int) $subscription->consultant_company_id)) {
                $skipped++;
                continue;
            }

            $bucket = $this->matchingEmailBucket($subscription->expires_at);
            if ($bucket === null) {
                $skipped++;
                continue;
            }
            if ($this->alreadySentBucket($subscription->metadata ?? [], $bucket)) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $sentConsultant++;
                continue;
            }

            if ($this->sendConsultantEmail($subscription, $bucket)) {
                $sentConsultant++;
            } else {
                $skipped++;
            }
        }

        return [
            'company_sent' => $sentCompany,
            'consultant_sent' => $sentConsultant,
            'skipped' => $skipped,
        ];
    }

    /**
     * @return Collection<int, ClientSubscription>
     */
    public function expiringCompanySubscriptions(): Collection
    {
        $horizon = now()->addDays($this->windowDays())->endOfDay();

        return ClientSubscription::query()
            ->with(['plan', 'company.users'])
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $horizon)
            ->where('expires_at', '>=', now()->subDays(7))
            ->get()
            ->filter(fn (ClientSubscription $s) => $this->companyNeedsRenewalAttention($s));
    }

    /**
     * @return Collection<int, ConsultantSubscription>
     */
    public function expiringConsultantSubscriptions(): Collection
    {
        $horizon = now()->addDays($this->windowDays())->endOfDay();

        return ConsultantSubscription::query()
            ->with(['plan', 'consultantCompany.users'])
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', $horizon->toDateString())
            ->whereDate('expires_at', '>=', now()->subDays(7)->toDateString())
            ->get()
            ->filter(fn (ConsultantSubscription $s) => $this->consultantRenewals->subscriptionNeedsRenewalAttention($s));
    }

    protected function matchingEmailBucket(Carbon|\DateTimeInterface|string|null $expiresAt): ?int
    {
        if (!$expiresAt) {
            return null;
        }

        $expires = Carbon::parse($expiresAt)->startOfDay();
        $daysLeft = (int) now()->startOfDay()->diffInDays($expires, false);

        if ($daysLeft < 0) {
            return 3; // final nudge for recently expired / last 3-day bucket
        }

        if ($daysLeft > self::WINDOW_DAYS) {
            return null;
        }

        // Entered window: assign to the largest bucket threshold that daysLeft has reached.
        // 45→14→3: when daysLeft is 40, bucket=45; when 10, bucket=14; when 2, bucket=3.
        $chosen = null;
        foreach (self::EMAIL_BUCKETS as $bucket) {
            if ($daysLeft <= $bucket) {
                $chosen = $bucket;
            }
        }

        return $chosen;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    protected function alreadySentBucket(array $metadata, int $bucket): bool
    {
        $sent = $metadata['renewal_nudge_buckets'] ?? [];

        return in_array($bucket, $sent, true);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    protected function markBucketSent(array $metadata, int $bucket): array
    {
        $sent = $metadata['renewal_nudge_buckets'] ?? [];
        if (!in_array($bucket, $sent, true)) {
            $sent[] = $bucket;
        }
        $metadata['renewal_nudge_buckets'] = $sent;
        $metadata['renewal_nudge_last_at'] = now()->toIso8601String();

        return $metadata;
    }

    protected function sendCompanyEmail(ClientSubscription $subscription, int $bucket): bool
    {
        $company = $subscription->company;
        if (!$company) {
            return false;
        }

        $admins = $company->users()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();

        if ($admins->isEmpty()) {
            return false;
        }

        $expires = $subscription->expires_at->format('d M Y');
        $ok = false;
        foreach ($admins as $user) {
            $sent = $this->emails->sendToUser('company_renewal_reminder', $user, [
                'company_name' => $company->name,
                'plan_name' => $subscription->plan?->plan_name ?? 'your package',
                'expires_at' => $expires,
                'days_left' => (string) max(0, (int) now()->startOfDay()->diffInDays($subscription->expires_at->copy()->startOfDay(), false)),
                'request_url' => url('/subscriptions/request-package'),
                'billing_url' => url('/subscriptions/billing'),
                'help_email' => config('mail.addresses.help.address', 'help@menetzero.com'),
                'app_name' => config('app.name', 'MENetZero'),
            ]);
            $ok = $ok || $sent;
        }

        if ($ok) {
            $subscription->update([
                'metadata' => $this->markBucketSent($subscription->metadata ?? [], $bucket),
            ]);
            Log::info('Company renewal nudge sent', [
                'subscription_id' => $subscription->id,
                'bucket' => $bucket,
            ]);
        }

        return $ok;
    }

    protected function sendConsultantEmail(ConsultantSubscription $subscription, int $bucket): bool
    {
        $org = $subscription->consultantCompany;
        if (!$org) {
            return false;
        }

        $users = $org->users()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();

        if ($users->isEmpty()) {
            return false;
        }

        $expires = Carbon::parse($subscription->expires_at)->format('d M Y');
        $nextYear = (int) $subscription->contract_year + 1;
        $ok = false;
        foreach ($users as $user) {
            $sent = $this->emails->sendToUser('consultant_renewal_reminder', $user, [
                'company_name' => $org->name,
                'plan_name' => $subscription->plan?->plan_name ?? 'agency capacity',
                'expires_at' => $expires,
                'contract_year' => (string) $subscription->contract_year,
                'next_year' => (string) $nextYear,
                'days_left' => (string) max(0, (int) now()->startOfDay()->diffInDays(Carbon::parse($subscription->expires_at)->startOfDay(), false)),
                'request_url' => url('/consultant/packs'),
                'renewal_url' => url('/consultant/renewal'),
                'help_email' => config('mail.addresses.help.address', 'help@menetzero.com'),
                'app_name' => config('app.name', 'MENetZero'),
            ]);
            $ok = $ok || $sent;
        }

        if ($ok) {
            $subscription->update([
                'metadata' => $this->markBucketSent($subscription->metadata ?? [], $bucket),
            ]);
            Log::info('Consultant renewal nudge sent', [
                'subscription_id' => $subscription->id,
                'bucket' => $bucket,
            ]);
        }

        return $ok;
    }
}
