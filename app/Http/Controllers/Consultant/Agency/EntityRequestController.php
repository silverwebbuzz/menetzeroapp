<?php

namespace App\Http\Controllers\Consultant\Agency;

use App\Data\CompanyPackageOptions;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Consultant\Agency\Concerns\ResolvesConsultantAgency;
use App\Models\ConsultantEntityRequest;
use App\Services\ConsultantAgencySubscriptionService;
use App\Services\ContactInquiryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class EntityRequestController extends Controller
{
    use ResolvesConsultantAgency;

    public function __construct(
        protected ConsultantAgencySubscriptionService $consultantSubscriptions,
    ) {
    }

    public function store(Request $request, ContactInquiryService $inquiries)
    {
        $consultantOrg = $this->consultantCompany();
        $user = Auth::user();

        $data = $request->validate([
            'package_code' => ['required', 'string', Rule::in(CompanyPackageOptions::CODES)],
            'entity_count' => 'required|integer|min:1|max:500',
            'extras' => 'nullable|array',
            'extras.*' => ['string', Rule::in(array_keys(CompanyPackageOptions::extraOptions()))],
            'message' => 'nullable|string|max:2000',
        ]);

        $packageCode = $data['package_code'];
        $wantsEnterprise = $packageCode === 'client_enterprise';
        $extras = $data['extras'] ?? [];
        $needsSitesOver5 = in_array('extra_sites', $extras, true);

        $record = ConsultantEntityRequest::create([
            'consultant_company_id' => $consultantOrg->id,
            'user_id' => $user->id,
            'entity_count' => (int) $data['entity_count'],
            'package_code' => $packageCode,
            'needs_sites_over_5' => $needsSitesOver5,
            'extras' => $extras,
            'wants_enterprise' => $wantsEnterprise,
            'message' => $data['message'] ?? null,
            'status' => 'new',
        ]);

        $subscription = $this->consultantSubscriptions->getActiveSubscription($consultantOrg->id);
        $slotSummary = $this->consultantSubscriptions->slotSummary($consultantOrg->id, $subscription);

        $profile = CompanyPackageOptions::label($packageCode);
        $extrasLabel = $extras
            ? implode(', ', array_map(
                fn ($k) => CompanyPackageOptions::extraOptions()[$k] ?? $k,
                $extras
            ))
            : 'none';

        $body = "Managed client request (Consultant · {$profile})\n"
            . 'Consultant org: ' . $consultantOrg->name . " (ID {$consultantOrg->id})\n"
            . 'Requested by: ' . $user->name . ' <' . $user->email . ">\n"
            . 'Managed clients requested: ' . $record->entity_count . "\n"
            . 'Current capacity: ' . ($slotSummary['used'] ?? 0) . '/' . ($slotSummary['limit'] ?? 0) . "\n"
            . 'Package: ' . $profile . " ({$packageCode})\n"
            . 'Extras: ' . $extrasLabel . "\n"
            . 'Request ID: ' . $record->id . "\n\n"
            . trim((string) ($data['message'] ?? ''));

        $result = $inquiries->submit('sales', [
            'name' => $user->name ?: $consultantOrg->name,
            'email' => $user->email,
            'phone' => $user->phone ?? $consultantOrg->phone ?? null,
            'subject' => "Consultant {$profile} request ×{$record->entity_count} clients",
            'message' => $body,
            'source' => 'in-app consultant client request',
            'company' => $consultantOrg->name,
        ]);

        if (!$result['ok']) {
            Log::warning('Consultant client request saved but sales email failed', [
                'request_id' => $record->id,
                'error' => $result['error'] ?? null,
            ]);
        }

        return redirect()
            ->route('consultant.packs.index')
            ->with('success', 'Request submitted. MENetZero will confirm rates offline and activate managed clients after payment.');
    }
}
