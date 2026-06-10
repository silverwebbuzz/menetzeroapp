<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;

class OnboardingService
{
    /**
     * Steps: none | business | location | complete
     */
    public function currentStep(User $user): string
    {
        if ($user->isAdmin()) {
            return 'complete';
        }

        // Staff joining an existing company skip owner onboarding.
        if ($user->isStaffInAnyCompany() && !$user->ownsCompany()) {
            return 'complete';
        }

        $company = $user->getActiveCompany();

        if (!$company) {
            return 'business';
        }

        if (!$this->isBusinessProfileComplete($company)) {
            return 'business';
        }

        if (!$this->hasActiveLocation($company)) {
            return 'location';
        }

        return 'complete';
    }

    public function isBusinessProfileComplete(?Company $company): bool
    {
        if (!$company) {
            return false;
        }

        $name = trim((string) $company->name);

        if ($name === '' || $name === 'New Company' || !filled($company->country)) {
            return false;
        }

        if ($company->isManagedClient()) {
            return true;
        }

        return filled($company->sector) && filled($company->industry);
    }

    public function hasActiveLocation(Company $company): bool
    {
        return $company->locations()->where('is_active', true)->exists();
    }

    public function isOnboardingComplete(User $user): bool
    {
        return $this->currentStep($user) === 'complete';
    }
}
