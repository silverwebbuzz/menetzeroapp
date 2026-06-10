<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Consultant;
use App\Models\User;
use App\Models\UserCompanyRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * P15 — Consultant = Partner: one account, optional directory listing + agency packs.
 *
 * Every consultant gets a linked partner organisation (company_type = partner) and
 * a web User so /consultant agency routes work from the same login session.
 */
class ConsultantPartnerLinkService
{
    /**
     * @return array{user: User, company: Company}
     */
    public function ensureLinked(Consultant $consultant): array
    {
        return DB::transaction(function () use ($consultant) {
            $company = $consultant->partnerCompany;

            if (!$company) {
                $company = Company::create([
                    'name' => $consultant->company_name ?: $consultant->name,
                    'email' => $consultant->email,
                    'phone' => $consultant->phone,
                    'company_type' => 'partner',
                    'is_direct_client' => true,
                    'is_active' => true,
                    'license_no' => $consultant->trade_license_number,
                    'description' => $consultant->bio,
                ]);

                $consultant->update(['partner_company_id' => $company->id]);
            } elseif ($company->company_type !== 'partner') {
                $company->update(['company_type' => 'partner']);
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
        $linked = $this->ensureLinked($consultant);
        $user = $linked['user'];
        $company = $linked['company'];

        Auth::guard('web')->login($user, Auth::guard('consultant')->viaRemember());
        $user->switchToCompany($company->id);

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
            $ownedPartner = UserCompanyRole::query()
                ->where('user_id', $user->id)
                ->whereNull('company_custom_role_id')
                ->where('is_active', true)
                ->whereHas('company', fn ($q) => $q->where('company_type', 'partner'))
                ->exists();

            if (!$ownedPartner || (int) $user->company_id === 0) {
                $user->update(['company_id' => $company->id]);
            }
        }
    }
}
