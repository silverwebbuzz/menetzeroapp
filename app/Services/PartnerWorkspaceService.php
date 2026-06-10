<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PartnerClientEngagement;
use App\Models\User;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Partner user "acting as" a managed client workspace (P17).
 *
 * Session key holds managed company id; partner home org comes from the user's owned company.
 */
class PartnerWorkspaceService
{
    public const SESSION_KEY = 'partner_acting_company_id';

    public function getPartnerHomeCompany(User $user): ?Company
    {
        $owned = $user->getOwnedCompany();

        return ($owned && $owned->isPartner()) ? $owned : null;
    }

    public function isPartnerUser(User $user): bool
    {
        return $this->getPartnerHomeCompany($user) !== null;
    }

    public function getActingCompanyId(): ?int
    {
        $id = session(self::SESSION_KEY);

        return $id ? (int) $id : null;
    }

    public function isActingAsManagedClient(?User $user = null): bool
    {
        $user ??= auth('web')->user();

        return $user !== null && $this->resolveActingCompany($user) !== null;
    }

    public function resolveActingCompany(User $user): ?Company
    {
        $actingId = $this->getActingCompanyId();

        if (!$actingId) {
            return null;
        }

        $partner = $this->getPartnerHomeCompany($user);

        if (!$partner) {
            return null;
        }

        $managed = Company::find($actingId);

        if (!$managed || !$managed->isManagedClient() || (int) $managed->partner_id !== (int) $partner->id) {
            return null;
        }

        return $managed;
    }

    public function activeEngagementForActing(User $user): ?PartnerClientEngagement
    {
        $managed = $this->resolveActingCompany($user);

        if (!$managed) {
            return null;
        }

        return app(PartnerEntitlementService::class)->getActiveEngagement($managed->id);
    }

    /**
     * @return Collection<int, PartnerClientEngagement>
     */
    public function switchableEngagements(User $user): Collection
    {
        $partner = $this->getPartnerHomeCompany($user);

        if (!$partner) {
            return collect();
        }

        return PartnerClientEngagement::query()
            ->with('managedCompany')
            ->forPartner($partner->id)
            ->active()
            ->orderByDesc('id')
            ->get();
    }

    public function enterWorkspace(User $user, int $managedCompanyId): Company
    {
        $partner = $this->getPartnerHomeCompany($user);

        if (!$partner) {
            throw new RuntimeException('Your account is not linked to a partner organisation.');
        }

        $managed = Company::query()
            ->where('id', $managedCompanyId)
            ->where('partner_id', $partner->id)
            ->where('is_direct_client', false)
            ->first();

        if (!$managed) {
            throw new RuntimeException('Managed client not found for this partner.');
        }

        $engagement = app(PartnerEntitlementService::class)->getActiveEngagement($managed->id);

        if (!$engagement || !$engagement->isActive()) {
            throw new RuntimeException('This client engagement is archived. Open it read-only from the agency hub or renew.');
        }

        session([self::SESSION_KEY => $managed->id]);

        return $managed;
    }

    public function enterWorkspaceFromEngagement(User $user, PartnerClientEngagement $engagement): Company
    {
        $partner = $this->getPartnerHomeCompany($user);

        if (!$partner || (int) $engagement->partner_company_id !== (int) $partner->id) {
            throw new RuntimeException('You do not have access to this client engagement.');
        }

        return $this->enterWorkspace($user, (int) $engagement->managed_company_id);
    }

    public function exitWorkspace(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function canActOnManagedClient(User $user, Company $company): bool
    {
        if (!$company->isManagedClient()) {
            return false;
        }

        $partner = $this->getPartnerHomeCompany($user);

        if (!$partner || (int) $company->partner_id !== (int) $partner->id) {
            return false;
        }

        if ($this->isActingAsManagedClient($user)) {
            return (int) $this->resolveActingCompany($user)?->id === (int) $company->id;
        }

        return false;
    }
}
