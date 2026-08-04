<?php

namespace App\Services;

use App\Data\ConsultantAgencyPlanMatrix;
use App\Models\Company;
use App\Models\ConsultantClientEngagement;
use App\Models\ConsultantSubscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ConsultantAgencyClientService
{
    public function __construct(
        protected ConsultantAgencySubscriptionService $subscriptions,
    ) {
    }

    /**
     * @return Collection<int, ConsultantClientEngagement>
     */
    public function listForConsultant(int $consultantCompanyId, bool $includeArchived = true): Collection
    {
        $query = ConsultantClientEngagement::query()
            ->with(['managedCompany', 'subscription.plan'])
            ->forConsultant($consultantCompanyId)
            ->orderByDesc('id');

        if (!$includeArchived) {
            $query->active();
        }

        return $query->get();
    }

    public function findForConsultant(int $consultantCompanyId, int $engagementId): ConsultantClientEngagement
    {
        return ConsultantClientEngagement::query()
            ->with(['managedCompany', 'subscription.plan'])
            ->forConsultant($consultantCompanyId)
            ->findOrFail($engagementId);
    }

    /**
     * @param  array{
     *   name: string,
     *   display_name?: string|null,
     *   primary_reporting_year: int,
     *   consultant_subscription_id?: int|null,
     *   country?: string|null,
     *   emirate?: string|null,
     *   sector?: string|null,
     *   industry?: string|null,
     *   contact_person?: string|null,
     *   description?: string|null,
     * }  $data
     */
    public function create(Company $consultantOrg, array $data): ConsultantClientEngagement
    {
        $this->assertConsultantOrg($consultantOrg);

        $reportingYear = (int) $data['primary_reporting_year'];

        return DB::transaction(function () use ($consultantOrg, $data, $reportingYear) {
            $this->subscriptions->ensureFreeTrialSubscription($consultantOrg);

            $subscription = $this->resolveSubscriptionForCreate(
                $consultantOrg->id,
                isset($data['consultant_subscription_id']) ? (int) $data['consultant_subscription_id'] : null,
            );

            $managed = Company::create([
                'name' => $data['name'],
                'email' => $this->uniqueManagedEmail($consultantOrg, $data['name']),
                'country' => $data['country'] ?? 'United Arab Emirates',
                'emirate' => $data['emirate'] ?? null,
                'sector' => $data['sector'] ?? null,
                'industry' => $data['industry'] ?? null,
                'contact_person' => $data['contact_person'] ?? null,
                'description' => $data['description'] ?? null,
                'company_type' => 'client',
                'is_direct_client' => false,
                'consultant_id' => $consultantOrg->id,
                'is_active' => true,
            ]);

            return ConsultantClientEngagement::create([
                'consultant_company_id' => $consultantOrg->id,
                'managed_company_id' => $managed->id,
                'consultant_subscription_id' => $subscription->id,
                'primary_reporting_year' => $reportingYear,
                'status' => 'active',
                'display_name' => $data['display_name'] ?? null,
            ]);
        });
    }

    /**
     * Lock and pick a capacity row with remaining slots.
     */
    protected function resolveSubscriptionForCreate(int $consultantCompanyId, ?int $subscriptionId): ConsultantSubscription
    {
        if ($subscriptionId) {
            $subscription = ConsultantSubscription::forConsultant($consultantCompanyId)
                ->with('plan')
                ->active()
                ->lockForUpdate()
                ->where('id', $subscriptionId)
                ->first();

            if (!$subscription) {
                throw new RuntimeException('Selected capacity package was not found or is not active.');
            }

            $used = ConsultantClientEngagement::query()
                ->where('consultant_subscription_id', $subscription->id)
                ->active()
                ->count();

            if ($used >= (int) $subscription->slot_limit) {
                throw new RuntimeException(
                    'No remaining places on that package. Choose another depth or request more clients.'
                );
            }

            return $subscription;
        }

        $available = $this->subscriptions->availableCapacityBuckets($consultantCompanyId);

        if ($available === []) {
            throw new RuntimeException(
                'No client slots available. Your free trial may already be used — request paid capacity from MENetZero.'
            );
        }

        if (count($available) > 1) {
            throw new RuntimeException(
                'You have capacity on more than one package. Choose which package depth this client should use.'
            );
        }

        $pickedId = (int) $available[0]['subscription_id'];

        return ConsultantSubscription::forConsultant($consultantCompanyId)
            ->with('plan')
            ->active()
            ->lockForUpdate()
            ->where('id', $pickedId)
            ->firstOrFail();
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
    public function update(ConsultantClientEngagement $engagement, array $data): ConsultantClientEngagement
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

    public function archive(ConsultantClientEngagement $engagement): ConsultantClientEngagement
    {
        if (!$engagement->isActive()) {
            return $engagement;
        }

        return $this->subscriptions->archiveEngagement($engagement);
    }

    protected function uniqueManagedEmail(Company $consultantOrg, string $clientName): string
    {
        $base = Str::slug($consultantOrg->slug . '-' . Str::slug($clientName));
        $email = "managed.{$base}@consultant.menetzero.local";
        $counter = 1;

        while (Company::where('email', $email)->exists()) {
            $email = "managed.{$base}-{$counter}@consultant.menetzero.local";
            $counter++;
        }

        return $email;
    }

    protected function assertConsultantOrg(Company $company): void
    {
        if (!$company->isConsultantOrg()) {
            throw new RuntimeException('Active company must be a consultant organisation.');
        }
    }
}
