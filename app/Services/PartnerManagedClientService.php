<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PartnerClientEngagement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PartnerManagedClientService
{
    public function __construct(
        protected PartnerSubscriptionService $subscriptions,
    ) {
    }

    /**
     * @return Collection<int, PartnerClientEngagement>
     */
    public function listForPartner(int $partnerCompanyId, bool $includeArchived = true): Collection
    {
        $query = PartnerClientEngagement::query()
            ->with(['managedCompany', 'subscription.plan'])
            ->forPartner($partnerCompanyId)
            ->orderByDesc('id');

        if (!$includeArchived) {
            $query->active();
        }

        return $query->get();
    }

    public function findForPartner(int $partnerCompanyId, int $engagementId): PartnerClientEngagement
    {
        return PartnerClientEngagement::query()
            ->with(['managedCompany', 'subscription.plan'])
            ->forPartner($partnerCompanyId)
            ->findOrFail($engagementId);
    }

    /**
     * @param  array{
     *   name: string,
     *   display_name?: string|null,
     *   primary_reporting_year: int,
     *   country?: string|null,
     *   emirate?: string|null,
     *   sector?: string|null,
     *   industry?: string|null,
     *   contact_person?: string|null,
     *   description?: string|null,
     * }  $data
     */
    public function create(Company $partner, array $data): PartnerClientEngagement
    {
        $this->assertPartnerOrg($partner);

        $subscription = $this->subscriptions->getActiveSubscription($partner->id);

        if (!$subscription) {
            throw new RuntimeException(
                'No active partner pack for this contract year. Purchase or renew an agency pack to add clients.'
            );
        }

        if (!$this->subscriptions->canConsumeSlot($partner->id)) {
            throw new RuntimeException(
                'All client slots are in use. Add an extra slot or archive a finished engagement.'
            );
        }

        $reportingYear = (int) $data['primary_reporting_year'];

        return DB::transaction(function () use ($partner, $data, $subscription, $reportingYear) {
            $managed = Company::create([
                'name' => $data['name'],
                'email' => $this->uniqueManagedEmail($partner, $data['name']),
                'country' => $data['country'] ?? 'United Arab Emirates',
                'emirate' => $data['emirate'] ?? null,
                'sector' => $data['sector'] ?? null,
                'industry' => $data['industry'] ?? null,
                'contact_person' => $data['contact_person'] ?? null,
                'description' => $data['description'] ?? null,
                'company_type' => 'client',
                'is_direct_client' => false,
                'partner_id' => $partner->id,
                'is_active' => true,
            ]);

            return PartnerClientEngagement::create([
                'partner_company_id' => $partner->id,
                'managed_company_id' => $managed->id,
                'partner_subscription_id' => $subscription->id,
                'primary_reporting_year' => $reportingYear,
                'status' => 'active',
                'display_name' => $data['display_name'] ?? null,
            ]);
        });
    }

    /**
     * @param  array{
     *   name?: string,
     *   display_name?: string|null,
     *   country?: string|null,
     *   emirate?: string|null,
     *   sector?: string|null,
     *   industry?: string|null,
     *   contact_person?: string|null,
     *   description?: string|null,
     * }  $data
     */
    public function update(PartnerClientEngagement $engagement, array $data): PartnerClientEngagement
    {
        $managed = $engagement->managedCompany;

        if (!$managed) {
            throw new RuntimeException('Managed client company not found.');
        }

        $managed->update(array_filter([
            'name' => $data['name'] ?? null,
            'country' => $data['country'] ?? null,
            'emirate' => $data['emirate'] ?? null,
            'sector' => $data['sector'] ?? null,
            'industry' => $data['industry'] ?? null,
            'contact_person' => $data['contact_person'] ?? null,
            'description' => $data['description'] ?? null,
        ], fn ($value) => $value !== null));

        if (array_key_exists('display_name', $data)) {
            $engagement->update(['display_name' => $data['display_name']]);
        }

        return $engagement->fresh(['managedCompany', 'subscription.plan']);
    }

    public function archive(PartnerClientEngagement $engagement): PartnerClientEngagement
    {
        if (!$engagement->isActive()) {
            return $engagement;
        }

        return $this->subscriptions->archiveEngagement($engagement);
    }

    protected function uniqueManagedEmail(Company $partner, string $clientName): string
    {
        $base = Str::slug($partner->slug . '-' . Str::slug($clientName));
        $email = "managed.{$base}@partner.menetzero.local";
        $counter = 1;

        while (Company::where('email', $email)->exists()) {
            $email = "managed.{$base}-{$counter}@partner.menetzero.local";
            $counter++;
        }

        return $email;
    }

    protected function assertPartnerOrg(Company $company): void
    {
        if (!$company->isPartner()) {
            throw new RuntimeException('Active company must be a partner organisation.');
        }
    }
}
