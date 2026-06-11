<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyCustomRole;
use App\Models\RoleTemplate;

/**
 * Team & access (roles + invites) for direct client companies and consultant agencies.
 * Uses the same user_company_roles / company_custom_roles tables for both contexts.
 */
class TeamAccessService
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected ConsultantAgencySubscriptionService $consultantSubscriptions,
        protected RoleManagementService $roleManagementService,
    ) {
    }

    public function menuLabel(): string
    {
        return 'Team & Access';
    }

    public function isConsultantContext(?Company $company): bool
    {
        return $company !== null && $company->isConsultantOrg();
    }

    /**
     * @return array<string, string>
     */
    public function routesFor(?Company $company): array
    {
        if ($this->isConsultantContext($company)) {
            return [
                'index' => 'consultant.team.index',
                'roles.create' => 'consultant.team.roles.create',
                'roles.store' => 'consultant.team.roles.store',
                'roles.edit' => 'consultant.team.roles.edit',
                'roles.update' => 'consultant.team.roles.update',
                'roles.destroy' => 'consultant.team.roles.destroy',
                'staff.store' => 'consultant.team.invites.store',
                'staff.destroy' => 'consultant.team.members.destroy',
                'staff.update_role' => 'consultant.team.members.update-role',
                'staff.invitation_success' => 'consultant.team.invites.success',
                'staff.resend_invitation' => 'consultant.team.invites.resend',
                'staff.cancel_invitation' => 'consultant.team.invites.cancel',
            ];
        }

        return [
            'index' => 'roles.index',
            'roles.create' => 'roles.create',
            'roles.store' => 'roles.store',
            'roles.edit' => 'roles.edit',
            'roles.update' => 'roles.update',
            'roles.destroy' => 'roles.destroy',
            'staff.store' => 'staff.store',
            'staff.destroy' => 'staff.destroy',
            'staff.update_role' => 'staff.update-role',
            'staff.invitation_success' => 'staff.invitation-success',
            'staff.resend_invitation' => 'staff.resend-invitation',
            'staff.cancel_invitation' => 'staff.cancel-invitation',
        ];
    }

    public function layoutFor(?Company $company): string
    {
        return $this->isConsultantContext($company) ? 'consultant.layouts.app' : 'layouts.app';
    }

    public function indexRouteName(?Company $company): string
    {
        return $this->routesFor($company)['index'];
    }

    public function upgradeRouteName(?Company $company): string
    {
        return $this->isConsultantContext($company)
            ? 'consultant.packs.index'
            : 'subscriptions.upgrade';
    }

    /**
     * Whether the organisation may invite another team member (plan + consultant trial rules).
     *
     * @return array{allowed: bool, message: ?string, reason: ?string}
     */
    public function canInviteTeamMember(int $companyId): array
    {
        $company = Company::find($companyId);

        if (!$company) {
            return [
                'allowed' => false,
                'message' => 'Company not found.',
                'reason' => 'no_company',
            ];
        }

        if ($company->isConsultantOrg()) {
            $subscription = $this->consultantSubscriptions->getActiveSubscription($companyId);

            if (!$subscription || $subscription->isFreeTrial()) {
                return [
                    'allowed' => false,
                    'message' => 'Team invites are included with paid agency packs (Consultant 5 and above). Upgrade your pack to add colleagues to your practice.',
                    'reason' => 'consultant_free_trial',
                ];
            }
        }

        $limitCheck = $this->subscriptionService->canPerformAction($companyId, 'users', 1);

        return [
            'allowed' => $limitCheck['allowed'],
            'message' => $limitCheck['message'],
            'reason' => $limitCheck['allowed'] ? null : 'plan_limit',
        ];
    }

    public function isConsultantFreeTrial(int $companyId): bool
    {
        $company = Company::find($companyId);

        if (!$company?->isConsultantOrg()) {
            return false;
        }

        $subscription = $this->consultantSubscriptions->getActiveSubscription($companyId);

        return !$subscription || $subscription->isFreeTrial();
    }

    /**
     * Seed default roles from system templates when missing (consultant orgs skip company setup).
     */
    public function ensureDefaultRoles(int $companyId): void
    {
        if (CompanyCustomRole::where('company_id', $companyId)->where('is_active', true)->exists()) {
            return;
        }

        $templates = RoleTemplate::where('is_active', true)
            ->where('is_system_template', true)
            ->orderBy('sort_order')
            ->get();

        foreach ($templates as $template) {
            $existing = CompanyCustomRole::where('company_id', $companyId)
                ->where('based_on_template', $template->template_code)
                ->first();

            if ($existing) {
                continue;
            }

            $permissionIds = $template->permissions()->pluck('permissions.id')->toArray();

            $this->roleManagementService->createCustomRole(
                $companyId,
                $template->template_name,
                $permissionIds,
                [
                    'description' => $template->description,
                    'based_on_template' => $template->template_code,
                ],
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function viewShared(Company $company): array
    {
        $canAddUser = $this->canInviteTeamMember($company->id);
        $routes = $this->routesFor($company);

        return [
            'teamLayout' => $this->layoutFor($company),
            'teamContext' => $this->isConsultantContext($company) ? 'consultant' : 'client',
            'teamRoutes' => $routes,
            'teamMenuLabel' => $this->menuLabel(),
            'canAddUser' => $canAddUser,
            'userLimitMessage' => $canAddUser['message'],
            'teamUpgradeRoute' => $this->upgradeRouteName($company),
            'showConsultantTrialNotice' => $this->isConsultantFreeTrial($company->id),
        ];
    }
}
