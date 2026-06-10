<?php

namespace App\Http\Controllers\Consultant;

use App\Data\ConsultantOptions;
use App\Http\Controllers\Controller;
use App\Services\ConsultantAccountService;
use App\Services\ConsultantAgencyClientService;
use App\Services\ConsultantAgencyRenewalService;
use App\Services\ConsultantAgencySubscriptionService;
use App\Services\ConsultantDashboardService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $consultant = Auth::guard('consultant')->user();
        $consultant->load('documents');

        $requiredDocs = ConsultantOptions::REQUIRED_DOCUMENT_TYPES;
        $uploadedTypes = $consultant->documents->pluck('document_type')->all();
        $missingDocs = array_diff($requiredDocs, $uploadedTypes);

        $introCount = $consultant->introRequests()->where('status', 'new')->count();
        $orderCount = $consultant->orders()->count();

        $consultant->loadMissing('agencyCompany');

        if (!$consultant->agencyCompany) {
            app(ConsultantAccountService::class)->ensureLinked($consultant);
            $consultant->loadMissing('agencyCompany');
        }

        $consultantOrg = $consultant->agencyCompany;

        $subscriptionService = app(ConsultantAgencySubscriptionService::class);
        $subscriptionService->ensureFreeTrialSubscription($consultantOrg);
        $subscription = $subscriptionService->getActiveSubscription($consultantOrg->id);
        $slotSummary = $subscriptionService->slotSummary($consultantOrg->id, $subscription);
        $activeClients = app(ConsultantAgencyClientService::class)
            ->listForConsultant($consultantOrg->id, false)
            ->loadMissing(['managedCompany', 'subscription.plan']);
        $renewalService = app(ConsultantAgencyRenewalService::class);
        $needsRenewal = $renewalService->needsRenewalFlow($consultantOrg->id);
        $renewalSubscription = $needsRenewal ? $subscription : null;

        $dashboardService = app(ConsultantDashboardService::class);
        $directoryProgress = $dashboardService->directoryProgress($consultant, $missingDocs);
        $portfolio = $dashboardService->portfolioStats($activeClients);
        $quickActions = $dashboardService->quickActions(
            ($slotSummary['remaining'] ?? 0) > 0,
            !empty($slotSummary['is_trial']),
            $needsRenewal,
        );

        $slotUsedPercent = ($slotSummary['limit'] ?? 0) > 0
            ? (int) round((($slotSummary['used'] ?? 0) / $slotSummary['limit']) * 100)
            : 0;

        return view('consultant.dashboard', compact(
            'consultant',
            'missingDocs',
            'introCount',
            'orderCount',
            'consultantOrg',
            'subscription',
            'slotSummary',
            'activeClients',
            'needsRenewal',
            'renewalSubscription',
            'directoryProgress',
            'portfolio',
            'quickActions',
            'slotUsedPercent',
        ));
    }
}
