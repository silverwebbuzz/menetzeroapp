<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultantEntityRequest;
use App\Services\AdminRequestActivationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConsultantEntityRequestController extends Controller
{
    public function __construct(
        protected AdminRequestActivationService $activations,
    ) {
    }

    public function index(Request $request)
    {
        $status = $request->query('status');

        $requests = ConsultantEntityRequest::query()
            ->with(['consultantCompany', 'user'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $suggestions = [];
        foreach ($requests as $req) {
            $suggestions[$req->id] = $this->activations->suggestConsultantQuote($req);
        }

        return view('admin.entity-requests.index', compact('requests', 'status', 'suggestions'));
    }

    public function update(Request $request, ConsultantEntityRequest $entityRequest)
    {
        $data = $request->validate([
            'status' => 'required|in:new,contacted,quoted,activated,closed',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        if ($data['status'] === 'activated' && $entityRequest->status !== 'activated') {
            return back()->with('error', 'Use Activate to grant slots — do not set status to activated manually.');
        }

        $entityRequest->update($data);

        return back()->with('success', 'Client request updated.');
    }

    public function saveQuote(Request $request, ConsultantEntityRequest $entityRequest)
    {
        $data = $request->validate([
            'quote_amount_aed' => 'nullable|numeric|min:0|max:9999999',
            'quote_breakdown' => 'nullable|string|max:2000',
            'use_suggestion' => 'sometimes|boolean',
        ]);

        $amount = isset($data['quote_amount_aed']) ? (float) $data['quote_amount_aed'] : null;
        $breakdown = $data['quote_breakdown'] ?? null;

        if ($request->boolean('use_suggestion')) {
            $suggestion = $this->activations->suggestConsultantQuote($entityRequest);
            if ($suggestion['amount_aed'] !== null) {
                $amount = $suggestion['amount_aed'];
            }
            $breakdown = $suggestion['breakdown'];
        }

        $this->activations->saveConsultantQuote(
            $entityRequest,
            $amount,
            $breakdown,
            Auth::guard('admin')->id(),
        );

        return back()->with('success', 'Quote saved.');
    }

    public function markPaid(ConsultantEntityRequest $entityRequest)
    {
        $this->activations->markConsultantPaid($entityRequest);

        return back()->with('success', 'Marked as paid (offline).');
    }

    public function activate(Request $request, ConsultantEntityRequest $entityRequest)
    {
        $data = $request->validate([
            'note' => 'nullable|string|max:500',
            'contract_year' => 'nullable|integer|min:2024|max:2100',
        ]);

        $note = $data['note']
            ?: ('Activated from entity request #' . $entityRequest->id . ' · ' . $entityRequest->packageLabel());

        try {
            $this->activations->activateConsultantRequest(
                $entityRequest,
                $note,
                Auth::guard('admin')->id(),
                isset($data['contract_year']) ? (int) $data['contract_year'] : null,
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Managed clients activated and audit logged.');
    }
}
