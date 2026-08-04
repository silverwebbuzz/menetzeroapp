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
use Illuminate\Validation\ValidationException;

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
            'lines' => 'required|array',
            'lines.*' => 'nullable|integer|min:0|max:500',
            'extras' => 'nullable|array',
            'extras.*' => ['string', Rule::in(array_keys(CompanyPackageOptions::extraOptions()))],
            'message' => 'nullable|string|max:2000',
        ]);

        $lines = [];
        foreach (CompanyPackageOptions::CODES as $code) {
            $qty = (int) ($data['lines'][$code] ?? 0);
            if ($qty > 0) {
                $lines[] = [
                    'package_code' => $code,
                    'entity_count' => $qty,
                ];
            }
        }

        if ($lines === []) {
            throw ValidationException::withMessages([
                'lines' => 'Enter how many managed clients you need for at least one package.',
            ]);
        }

        $total = array_sum(array_column($lines, 'entity_count'));
        $primary = $lines[0];
        $wantsEnterprise = collect($lines)->contains(
            fn (array $l) => $l['package_code'] === 'client_enterprise'
        );
        $extras = $data['extras'] ?? [];
        $needsSitesOver5 = in_array('extra_sites', $extras, true);

        $record = ConsultantEntityRequest::create([
            'consultant_company_id' => $consultantOrg->id,
            'user_id' => $user->id,
            'entity_count' => $total,
            'lines' => $lines,
            'package_code' => $primary['package_code'],
            'needs_sites_over_5' => $needsSitesOver5,
            'extras' => $extras,
            'wants_enterprise' => $wantsEnterprise,
            'message' => $data['message'] ?? null,
            'status' => 'new',
        ]);

        $subscription = $this->consultantSubscriptions->getActiveSubscription($consultantOrg->id);
        $slotSummary = $this->consultantSubscriptions->slotSummary($consultantOrg->id, $subscription);

        $linesLabel = $record->packageLabel();
        $extrasLabel = $extras
            ? implode(', ', array_map(
                fn ($k) => CompanyPackageOptions::extraOptions()[$k] ?? $k,
                $extras
            ))
            : 'none';

        $body = "Managed client request (Consultant · multi-package)\n"
            . 'Consultant org: ' . $consultantOrg->name . " (ID {$consultantOrg->id})\n"
            . 'Requested by: ' . $user->name . ' <' . $user->email . ">\n"
            . 'Lines: ' . $linesLabel . "\n"
            . 'Total managed clients: ' . $total . "\n"
            . 'Current capacity: ' . ($slotSummary['used'] ?? 0) . '/' . ($slotSummary['limit'] ?? 0) . "\n"
            . 'Extras: ' . $extrasLabel . "\n"
            . 'Request ID: ' . $record->id . "\n\n"
            . trim((string) ($data['message'] ?? ''));

        $result = $inquiries->submit('sales', [
            'name' => $user->name ?: $consultantOrg->name,
            'email' => $user->email,
            'phone' => $user->phone ?? $consultantOrg->phone ?? null,
            'subject' => "Consultant multi-package request ×{$total} clients",
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
