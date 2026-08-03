<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyPackageRequest;
use App\Services\AdminRequestActivationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyPackageRequestController extends Controller
{
    public function __construct(
        protected AdminRequestActivationService $activations,
    ) {
    }

    public function index(Request $request)
    {
        $status = $request->query('status');

        $requests = CompanyPackageRequest::query()
            ->with(['company', 'user'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $suggestions = [];
        foreach ($requests as $req) {
            $suggestions[$req->id] = $this->activations->suggestCompanyQuote($req);
        }

        return view('admin.package-requests.index', compact('requests', 'status', 'suggestions'));
    }

    public function update(Request $request, CompanyPackageRequest $packageRequest)
    {
        $data = $request->validate([
            'status' => 'required|in:new,contacted,quoted,activated,closed',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        if ($data['status'] === 'activated' && $packageRequest->status !== 'activated') {
            return back()->with('error', 'Use Activate to grant the package — do not set status to activated manually.');
        }

        $packageRequest->update($data);

        return back()->with('success', 'Package request updated.');
    }

    public function saveQuote(Request $request, CompanyPackageRequest $packageRequest)
    {
        $data = $request->validate([
            'quote_amount_aed' => 'nullable|numeric|min:0|max:9999999',
            'quote_breakdown' => 'nullable|string|max:2000',
            'duration_months' => 'required|integer|min:1|max:60',
            'use_suggestion' => 'sometimes|boolean',
        ]);

        $amount = isset($data['quote_amount_aed']) ? (float) $data['quote_amount_aed'] : null;
        $breakdown = $data['quote_breakdown'] ?? null;

        if ($request->boolean('use_suggestion')) {
            $suggestion = $this->activations->suggestCompanyQuote($packageRequest);
            if ($suggestion['amount_aed'] !== null) {
                $amount = $suggestion['amount_aed'];
            }
            $breakdown = $suggestion['breakdown'];
        }

        $this->activations->saveCompanyQuote(
            $packageRequest,
            $amount,
            $breakdown,
            (int) $data['duration_months'],
            Auth::guard('admin')->id(),
        );

        return back()->with('success', 'Quote saved.');
    }

    public function markPaid(CompanyPackageRequest $packageRequest)
    {
        $this->activations->markCompanyPaid($packageRequest);

        return back()->with('success', 'Marked as paid (offline).');
    }

    public function activate(Request $request, CompanyPackageRequest $packageRequest)
    {
        $data = $request->validate([
            'duration_months' => 'nullable|integer|min:1|max:60',
            'note' => 'nullable|string|max:500',
        ]);

        $months = (int) ($data['duration_months'] ?? $packageRequest->duration_months ?? 12);
        $note = $data['note']
            ?: ('Activated from package request #' . $packageRequest->id . ' · ' . $packageRequest->packageLabel());

        try {
            $this->activations->activateCompanyRequest(
                $packageRequest,
                $months,
                $note,
                Auth::guard('admin')->id(),
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Package activated and audit logged.');
    }
}
