<?php

namespace App\Http\Controllers\Admin;

use App\Data\ConsultantOptions;
use App\Http\Controllers\Controller;
use App\Models\Consultant;
use App\Services\ConsultantAccountService;
use App\Services\OrganisationDeletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ConsultantController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $consultants = Consultant::query()
            ->withCount(['documents', 'introRequests', 'orders'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByRaw("FIELD(status, 'pending_review', 'draft', 'approved', 'rejected', 'suspended')")
            ->orderByDesc('submitted_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.consultants.index', [
            'consultants' => $consultants,
            'status' => $status,
            'statuses' => ConsultantOptions::STATUS_LABELS,
        ]);
    }

    public function show(Consultant $consultant)
    {
        $consultant->load(['documents', 'introRequests.company', 'orders.company', 'reviewedBy', 'agencyCompany']);

        $consultantPacks = \App\Models\SubscriptionPlan::where('plan_category', 'consultant_agency')
            ->where(function ($q) {
                $q->where('is_active', true)
                    ->orWhere('plan_code', \App\Data\ConsultantAgencyPlanMatrix::DEMO_PACK_CODE);
            })
            ->orderBy('sort_order')
            ->get();

        $activeSubscription = null;
        $packageAssignments = collect();

        if ($consultant->agency_company_id) {
            $activeSubscription = app(\App\Services\ConsultantAgencySubscriptionService::class)
                ->getActiveSubscription($consultant->agency_company_id);

            $packageAssignments = \App\Models\AdminPackageAssignment::with(['plan', 'admin'])
                ->where('company_id', $consultant->agency_company_id)
                ->where('target_type', 'consultant')
                ->latest()
                ->get();
        }

        return view('admin.consultants.show', [
            'consultant' => $consultant,
            'documentTypes' => ConsultantOptions::DOCUMENT_TYPES,
            'specialties' => ConsultantOptions::SPECIALTIES,
            'emirates' => ConsultantOptions::EMIRATES,
            'consultantPacks' => $consultantPacks,
            'activeSubscription' => $activeSubscription,
            'packageAssignments' => $packageAssignments,
        ]);
    }

    public function approve(Consultant $consultant)
    {
        $consultant->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by_admin_id' => Auth::guard('admin')->id(),
            'rejection_reason' => null,
        ]);

        app(ConsultantAccountService::class)->ensureLinked($consultant);

        return back()->with('success', 'Consultant approved — listed in directory and agency hub linked.');
    }

    public function reject(Request $request, Consultant $consultant)
    {
        $data = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $consultant->update([
            'status' => 'rejected',
            'rejection_reason' => $data['rejection_reason'],
            'reviewed_at' => now(),
            'reviewed_by_admin_id' => Auth::guard('admin')->id(),
        ]);

        return back()->with('success', 'Application rejected. Consultant can revise and resubmit.');
    }

    public function suspend(Consultant $consultant)
    {
        $consultant->update([
            'status' => 'suspended',
            'is_active' => false,
            'reviewed_at' => now(),
            'reviewed_by_admin_id' => Auth::guard('admin')->id(),
        ]);

        return back()->with('success', 'Consultant suspended and removed from directory.');
    }

    public function toggleFeatured(Consultant $consultant)
    {
        $consultant->update(['is_featured' => !$consultant->is_featured]);

        return back()->with('success', $consultant->is_featured ? 'Marked as featured consultant.' : 'Removed featured flag.');
    }

    public function downloadDocument(Consultant $consultant, int $documentId)
    {
        $document = $consultant->documents()->findOrFail($documentId);

        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404);
        }

        return Storage::disk('local')->download($document->file_path, $document->original_filename);
    }

    public function updateNotes(Request $request, Consultant $consultant)
    {
        $data = $request->validate([
            'admin_notes' => 'nullable|string|max:5000',
        ]);

        $consultant->update($data);

        return back()->with('success', 'Admin notes saved.');
    }

    /**
     * Permanently delete a consultant and the agency company behind it.
     *
     * The profile and its agency company are two rows describing one
     * organisation; deleting only the profile would strand the company and
     * every workspace under it. Typed-name confirmation, as for companies.
     */
    public function destroy(Request $request, Consultant $consultant, OrganisationDeletionService $deletions)
    {
        $request->validate(['confirm_name' => 'required|string']);

        if (trim($request->input('confirm_name')) !== trim((string) $consultant->name)) {
            return back()->with('error', 'The name you typed does not match. Nothing was deleted.');
        }

        try {
            $summary = $deletions->deleteConsultant($consultant, (int) Auth::guard('admin')->id());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.consultants.index')->with(
            'success',
            "Deleted {$summary['name']} permanently — {$summary['users_deleted']} user(s) removed, "
            . "{$summary['users_detached']} kept (member of another company), "
            . "{$summary['invoices_deleted']} invoice(s) removed."
        );
    }
}
