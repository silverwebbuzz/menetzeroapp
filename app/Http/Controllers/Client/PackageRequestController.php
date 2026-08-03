<?php

namespace App\Http\Controllers\Client;

use App\Data\CompanyPackageOptions;
use App\Http\Controllers\Controller;
use App\Models\CompanyPackageRequest;
use App\Services\ContactInquiryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PackageRequestController extends Controller
{
    public function create()
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isClient()) {
            return redirect()->route('client.dashboard')->with('error', 'Access denied.');
        }

        $packages = CompanyPackageOptions::packages();
        $extraOptions = CompanyPackageOptions::extraOptions();
        $matrix = CompanyPackageOptions::comparisonMatrix();
        $recent = CompanyPackageRequest::query()
            ->where('company_id', $company->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('client.subscriptions.request-package', compact(
            'company',
            'packages',
            'extraOptions',
            'matrix',
            'recent',
        ));
    }

    public function store(Request $request, ContactInquiryService $inquiries)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isClient()) {
            return redirect()->route('client.dashboard')->with('error', 'Access denied.');
        }

        $validExtras = array_keys(CompanyPackageOptions::extraOptions());

        $data = $request->validate([
            'package_code' => 'required|in:' . implode(',', CompanyPackageOptions::CODES),
            'extras' => 'nullable|array',
            'extras.*' => 'string|in:' . implode(',', $validExtras),
            'message' => 'nullable|string|max:2000',
        ]);

        $extras = array_values(array_unique($data['extras'] ?? []));
        $user = Auth::user();
        $packageLabel = CompanyPackageOptions::label($data['package_code']);

        $record = CompanyPackageRequest::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'package_code' => $data['package_code'],
            'extras' => $extras,
            'message' => $data['message'] ?? null,
            'status' => 'new',
        ]);

        $extraLabels = [];
        foreach ($extras as $key) {
            $extraLabels[] = CompanyPackageOptions::extraOptions()[$key] ?? $key;
        }

        $body = "Package: {$packageLabel} ({$data['package_code']})\n"
            . 'Company: ' . $company->name . " (ID {$company->id})\n"
            . 'Requested by: ' . $user->name . ' <' . $user->email . ">\n"
            . 'Extras: ' . ($extraLabels ? implode('; ', $extraLabels) : 'none') . "\n"
            . 'Request ID: ' . $record->id . "\n\n"
            . trim((string) ($data['message'] ?? ''));

        $result = $inquiries->submit('sales', [
            'name' => $user->name ?: $company->name,
            'email' => $user->email,
            'phone' => $user->phone ?? $company->phone ?? null,
            'subject' => 'Package request: ' . $packageLabel,
            'message' => $body,
            'source' => 'in-app package request',
            'company' => $company->name,
        ]);

        if (!$result['ok']) {
            Log::warning('Package request saved but sales email failed', [
                'request_id' => $record->id,
                'error' => $result['error'] ?? null,
            ]);
        }

        return redirect()
            ->route('subscriptions.request-package')
            ->with('success', 'Request submitted. MENetZero will confirm package details and pricing offline, then activate after payment.');
    }
}
