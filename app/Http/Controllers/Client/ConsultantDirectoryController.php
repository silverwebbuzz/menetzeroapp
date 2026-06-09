<?php

namespace App\Http\Controllers\Client;

use App\Data\CommercialPlanComparison;
use App\Data\ConsultantOptions;
use App\Http\Controllers\Controller;
use App\Models\Consultant;
use App\Models\ConsultantIntroRequest;
use App\Services\ConsultantDirectoryService;
use App\Support\PlanGate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConsultantDirectoryController extends Controller
{
    public function __construct(
        protected ConsultantDirectoryService $directory,
    ) {}

    public function index(PlanGate $gate)
    {
        $companyId = $gate->companyId();
        if (!$companyId) {
            return redirect()->route('client.dashboard');
        }

        $level = $this->directory->directoryLevel($companyId);
        $consultants = $this->directory->listedConsultants();
        $presented = $consultants->map(
            fn (Consultant $c) => $this->directory->presentForClient($c, $companyId)
        );

        return view('client.consultants.index', [
            'level' => $level,
            'partnerCount' => $this->directory->approvedCount(),
            'consultants' => $presented,
            'canRequestIntro' => $this->directory->canRequestIntro($companyId),
            'consultantAddOns' => CommercialPlanComparison::consultantAddOns(),
            'directoryLabel' => $gate->consultantDirectoryLabel(),
        ]);
    }

    public function show(PlanGate $gate, Consultant $consultant)
    {
        $companyId = $gate->companyId();
        if (!$companyId || !$consultant->isListed()) {
            abort(404);
        }

        $presented = $this->directory->presentForClient($consultant, $companyId);

        return view('client.consultants.show', [
            'consultant' => $presented,
            'raw' => $consultant,
            'canRequestIntro' => $this->directory->canRequestIntro($companyId),
            'packTypes' => ConsultantOptions::PACK_TYPES,
            'consultantAddOns' => CommercialPlanComparison::consultantAddOns(),
            'level' => $this->directory->directoryLevel($companyId),
        ]);
    }

    public function requestIntro(Request $request, PlanGate $gate, Consultant $consultant)
    {
        $companyId = $gate->companyId();
        if (!$companyId || !$consultant->isListed()) {
            abort(404);
        }

        if (!$this->directory->canRequestIntro($companyId)) {
            return back()->with('error', 'Upgrade to Starter or above to request an introduction to verified partners.');
        }

        $data = $request->validate([
            'pack_type' => 'nullable|in:' . implode(',', array_keys(ConsultantOptions::PACK_TYPES)),
            'message' => 'nullable|string|max:2000',
        ]);

        ConsultantIntroRequest::create([
            'company_id' => $companyId,
            'user_id' => Auth::id(),
            'consultant_id' => $consultant->id,
            'pack_type' => $data['pack_type'] ?? null,
            'message' => $data['message'] ?? null,
            'status' => 'new',
        ]);

        return redirect()->route('client.consultants.index')
            ->with('success', 'Introduction request sent. Our team will connect you with ' . $consultant->company_name . ' shortly.');
    }
}
