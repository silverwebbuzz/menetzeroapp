<?php

namespace App\Http\View\Composers;

use App\Services\ConsultantAgencyRenewalService;
use App\Services\ConsultantAgencyWorkspaceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ConsultantAgencyComposer
{
    public function __construct(
        protected ConsultantAgencyRenewalService $renewals,
        protected ConsultantAgencyWorkspaceService $workspace,
    ) {
    }

    public function compose(View $view): void
    {
        $showRenewalNav = false;

        $user = Auth::guard('web')->user();

        if ($user) {
            $consultantOrg = $this->workspace->getConsultantHomeCompany($user);

            if ($consultantOrg) {
                $showRenewalNav = $this->renewals->needsRenewalFlow($consultantOrg->id);
            }
        }

        $view->with('showRenewalNav', $showRenewalNav);
    }
}
