<?php

namespace App\Http\Controllers\Admin;

use App\Data\ConsultantOptions;
use App\Http\Controllers\Controller;
use App\Models\Consultant;
use App\Services\ConsultantAccountService;
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
        $consultant->load(['documents', 'introRequests.company', 'orders.company', 'reviewedBy']);

        return view('admin.consultants.show', [
            'consultant' => $consultant,
            'documentTypes' => ConsultantOptions::DOCUMENT_TYPES,
            'specialties' => ConsultantOptions::SPECIALTIES,
            'emirates' => ConsultantOptions::EMIRATES,
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

        return back()->with('success', 'Partner approved — listed in directory and agency hub linked.');
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

        return back()->with('success', $consultant->is_featured ? 'Marked as featured partner.' : 'Removed featured flag.');
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
}
