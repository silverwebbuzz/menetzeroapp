<?php

namespace App\Http\View\Composers;

use App\Services\SubscriptionRenewalNudgeService;
use App\Support\PlanGate;
use Illuminate\View\View;

class PlanGateComposer
{
    public function __construct(
        protected SubscriptionRenewalNudgeService $renewalNudges,
    ) {
    }

    public function compose(View $view): void
    {
        $user = auth('web')->user();
        $view->with('gate', PlanGate::forUser($user));
        $view->with('companyRenewalNudge', $this->renewalNudges->companyNudgeForUser($user));
    }
}
