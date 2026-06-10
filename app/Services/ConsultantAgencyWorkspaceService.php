<?php

namespace App\Services;

use App\Models\Company;
use App\Models\ConsultantClientEngagement;
use App\Models\User;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Consultant user "acting as" a managed client workspace (P17).
 *
 * Session key holds managed company id; consultant home org comes from the user's owned company.
 */
class ConsultantAgencyWorkspaceService
{
    public const SESSION_KEY = 'consultant_acting_company_id';

    public const READ_ONLY_KEY = 'consultant_acting_read_only';

    public function getConsultantHomeCompany(User $user): ?Company
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('user_company_roles')) {
                $role = $user->companyRoles()
                    ->where('is_active', true)
                    ->whereNull('company_custom_role_id')
                    ->whereHas('company', fn ($q) => $q->where('company_type', 'consultant'))
                    ->first();

                if ($role) {
                    return Company::find($role->company_id);
                }
            }
        } catch (\Throwable) {
            // fall through
        }

        $owned = $user->getOwnedCompany();

        return ($owned && $owned->isConsultantOrg()) ? $owned : null;
    }

    public function isConsultantOrgUser(User $user): bool
    {
        return $this->getConsultantHomeCompany($user) !== null;
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

        $consultantOrg = $this->getConsultantHomeCompany($user);

        if (!$consultantOrg) {
            return null;
        }

        $managed = Company::find($actingId);

        if (!$managed || !$managed->isManagedClient() || (int) $managed->consultant_id !== (int) $consultantOrg->id) {
            return null;
        }

        return $managed;
    }

    public function isReadOnlyWorkspace(): bool
    {
        return (bool) session(self::READ_ONLY_KEY, false);
    }

    public function engagementForActing(User $user): ?ConsultantClientEngagement
    {
        $managed = $this->resolveActingCompany($user);

        if (!$managed) {
            return null;
        }

        if ($this->isReadOnlyWorkspace()) {
            return ConsultantClientEngagement::query()
                ->where('managed_company_id', $managed->id)
                ->where('consultant_company_id', $managed->consultant_id)
                ->orderByDesc('id')
                ->first();
        }

        return app(ConsultantAgencyEntitlementService::class)->getActiveEngagement($managed->id);
    }

    /**
     * @return array{allowed: bool, message: string|null}
     */
    public function canWriteReportingYear(User $user, int $reportingYear): array
    {
        if (!$this->isActingAsManagedClient($user)) {
            return ['allowed' => true, 'message' => null];
        }

        if ($this->isReadOnlyWorkspace()) {
            return [
                'allowed' => false,
                'message' => 'This archived client workspace is read-only.',
            ];
        }

        $managed = $this->resolveActingCompany($user);

        return app(PlanEntitlementService::class)->canWriteForReportingYear((int) $managed->id, $reportingYear);
    }

    public function purgeInvalidActingSession(User $user): void
    {
        if ($this->getActingCompanyId() && !$this->resolveActingCompany($user)) {
            $this->exitWorkspace();
        }
    }

    /**
     * @return Collection<int, ConsultantClientEngagement>
     */
    public function switchableEngagements(User $user): Collection
    {
        $consultantOrg = $this->getConsultantHomeCompany($user);

        if (!$consultantOrg) {
            return collect();
        }

        return ConsultantClientEngagement::query()
            ->with('managedCompany')
            ->forConsultant($consultantOrg->id)
            ->active()
            ->orderByDesc('id')
            ->get();
    }

    public function enterWorkspace(User $user, int $managedCompanyId): Company
    {
        $consultantOrg = $this->getConsultantHomeCompany($user);

        if (!$consultantOrg) {
            throw new RuntimeException('Your account is not linked to a consultant organisation.');
        }

        $managed = Company::query()
            ->where('id', $managedCompanyId)
            ->where('consultant_id', $consultantOrg->id)
            ->where('is_direct_client', false)
            ->first();

        if (!$managed) {
            throw new RuntimeException('Managed client not found for this consultant organisation.');
        }

        $engagement = app(ConsultantAgencyEntitlementService::class)->getActiveEngagement($managed->id);

        if (!$engagement || !$engagement->isActive()) {
            throw new RuntimeException('This client engagement is archived. Open it read-only from the agency hub or renew.');
        }

        session([
            self::SESSION_KEY => $managed->id,
            self::READ_ONLY_KEY => false,
        ]);

        return $managed;
    }

    public function enterReadOnlyWorkspace(User $user, ConsultantClientEngagement $engagement): Company
    {
        $consultantOrg = $this->getConsultantHomeCompany($user);

        if (!$consultantOrg || (int) $engagement->consultant_company_id !== (int) $consultantOrg->id) {
            throw new RuntimeException('You do not have access to this client engagement.');
        }

        $managed = $engagement->managedCompany;

        if (!$managed) {
            throw new RuntimeException('Managed client not found.');
        }

        session([
            self::SESSION_KEY => $managed->id,
            self::READ_ONLY_KEY => true,
        ]);

        return $managed;
    }

    public function enterWorkspaceFromEngagement(User $user, ConsultantClientEngagement $engagement): Company
    {
        $consultantOrg = $this->getConsultantHomeCompany($user);

        if (!$consultantOrg || (int) $engagement->consultant_company_id !== (int) $consultantOrg->id) {
            throw new RuntimeException('You do not have access to this client engagement.');
        }

        return $this->enterWorkspace($user, (int) $engagement->managed_company_id);
    }

    public function exitWorkspace(): void
    {
        session()->forget([self::SESSION_KEY, self::READ_ONLY_KEY]);
    }

    public function canActOnManagedClient(User $user, Company $company): bool
    {
        if (!$company->isManagedClient()) {
            return false;
        }

        $consultantOrg = $this->getConsultantHomeCompany($user);

        if (!$consultantOrg || (int) $company->consultant_id !== (int) $consultantOrg->id) {
            return false;
        }

        return (int) $this->resolveActingCompany($user)?->id === (int) $company->id;
    }
}
