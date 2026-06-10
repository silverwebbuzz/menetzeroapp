<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Consultant;
use App\Models\User;
use App\Models\UserCompanyRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Links consultant directory account to agency organisation + web User session.
 */
class ConsultantAccountService
{
    /**
     * @return array{user: User, company: Company}
     */
    public function ensureLinked(Consultant $consultant): array
    {
        $consultant->loadMissing('agencyCompany');

        if ($consultant->agency_company_id && $consultant->agencyCompany) {
            $user = User::where('email', $consultant->email)->first();

            if ($user) {
                $this->ensureOwnerRole($user, $consultant->agencyCompany);

                return ['user' => $user, 'company' => $consultant->agencyCompany];
            }
        }

        return DB::transaction(function () use ($consultant) {
            $company = $consultant->agencyCompany;

            if (!$company) {
                $company = Company::create([
                    'name' => $consultant->company_name ?: $consultant->name,
                    'email' => $consultant->email,
                    'phone' => $consultant->phone,
                    'company_type' => 'consultant',
                    'is_direct_client' => true,
                    'is_active' => true,
                    'license_no' => $consultant->trade_license_number,
                    'description' => $consultant->bio,
                ]);

                $consultant->update(['agency_company_id' => $company->id]);
            } elseif ($company->company_type !== 'consultant') {
                $company->update(['company_type' => 'consultant']);
            }

            $user = User::where('email', $consultant->email)->first();

            if (!$user) {
                $user = new User([
                    'name' => $consultant->name,
                    'email' => $consultant->email,
                    'role' => 'company_admin',
                    'is_active' => true,
                ]);
                $user->forceFill(['password' => $consultant->getAttributes()['password']]);
                $user->save();
            }

            $this->ensureOwnerRole($user, $company);

            return ['user' => $user->fresh(), 'company' => $company->fresh()];
        });
    }

    public function syncWebSession(Consultant $consultant): User
    {
        $webUser = Auth::guard('web')->user();

        if ($webUser && $webUser->email === $consultant->email && $consultant->agency_company_id) {
            if (!app(ConsultantAgencyWorkspaceService::class)->isActingAsManagedClient($webUser)) {
                $webUser->switchToCompany((int) $consultant->agency_company_id);
            }

            return $webUser;
        }

        $linked = $this->ensureLinked($consultant);
        $user = $linked['user'];
        $company = $linked['company'];

        Auth::guard('web')->login($user, Auth::guard('consultant')->viaRemember());

        if (!app(ConsultantAgencyWorkspaceService::class)->isActingAsManagedClient($user)) {
            $user->switchToCompany($company->id);
        }

        return $user;
    }

    protected function ensureOwnerRole(User $user, Company $company): void
    {
        $role = UserCompanyRole::query()
            ->where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->first();

        if (!$role) {
            UserCompanyRole::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'company_custom_role_id' => null,
                'assigned_by' => $user->id,
                'is_active' => true,
            ]);
        } elseif (!$role->is_active || $role->company_custom_role_id !== null) {
            $role->update([
                'company_custom_role_id' => null,
                'is_active' => true,
            ]);
        }

        if ((int) $user->company_id !== (int) $company->id) {
            $ownedConsultantOrg = UserCompanyRole::query()
                ->where('user_id', $user->id)
                ->whereNull('company_custom_role_id')
                ->where('is_active', true)
                ->whereHas('company', fn ($q) => $q->where('company_type', 'consultant'))
                ->exists();

            if (!$ownedConsultantOrg || (int) $user->company_id === 0) {
                $user->update(['company_id' => $company->id]);
            }
        }
    }
}
