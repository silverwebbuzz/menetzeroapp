<?php

namespace App\Http\Controllers;

use App\Models\Consultant;
use App\Models\ConsultantPublicInquiry;
use App\Services\ConsultantDirectoryService;
use Illuminate\Http\Request;

class PublicConsultantDirectoryController extends Controller
{
    public function __construct(
        protected ConsultantDirectoryService $directory,
    ) {}

    public function index(Request $request)
    {
        $emirate = $request->query('emirate');
        $specialty = $request->query('specialty');

        $query = $this->directory->listedConsultantsQuery();

        if ($emirate && ($key = $this->emirateKeyForLabel($emirate))) {
            $query->whereJsonContains('emirates', $key);
        }

        if ($specialty && ($key = $this->specialtyKeyForLabel($specialty))) {
            $query->whereJsonContains('specialties', $key);
        }

        $paginated = $query->paginate(12)->withQueryString();
        $presented = $paginated->getCollection()->map(
            fn (Consultant $c) => $this->directory->presentForPublic($c)
        );
        $paginated->setCollection($presented);

        return view('public.consultant-list.index', [
            'consultants' => $paginated,
            'consultantCount' => $this->directory->approvedCount(),
            'emirateFilters' => $this->directory->availableEmirateFilters(),
            'specialtyFilters' => $this->directory->availableSpecialtyFilters(),
            'activeEmirate' => $emirate,
            'activeSpecialty' => $specialty,
        ]);
    }

    public function show(Consultant $consultant)
    {
        if (!$consultant->isListed()) {
            abort(404);
        }

        return view('public.consultant-list.show', [
            'consultant' => $this->directory->presentForPublic($consultant),
        ]);
    }

    public function inquire(Request $request, Consultant $consultant)
    {
        if (!$consultant->isListed()) {
            abort(404);
        }

        $data = $request->validate([
            'requester_name' => 'required|string|max:120',
            'requester_email' => 'required|email|max:190',
            'requester_phone' => 'required|string|max:30',
            'requester_company' => 'nullable|string|max:190',
            'message' => 'nullable|string|max:2000',
        ]);

        ConsultantPublicInquiry::create([
            'consultant_id' => $consultant->id,
            'requester_name' => $data['requester_name'],
            'requester_email' => $data['requester_email'],
            'requester_phone' => $data['requester_phone'],
            'requester_company' => $data['requester_company'] ?? null,
            'message' => $data['message'] ?? null,
            'status' => 'new',
            'ip_address' => $request->ip(),
        ]);

        return redirect()
            ->route('consultant-list.show', $consultant)
            ->with('success', 'Your request has been sent. The consultant will be notified through MenetZero — we do not display their direct contact details publicly.');
    }

    protected function emirateKeyForLabel(string $label): ?string
    {
        foreach (\App\Data\ConsultantOptions::EMIRATES as $key => $name) {
            if ($name === $label) {
                return $key;
            }
        }

        return null;
    }

    protected function specialtyKeyForLabel(string $label): ?string
    {
        foreach (\App\Data\ConsultantOptions::SPECIALTIES as $key => $name) {
            if ($name === $label) {
                return $key;
            }
        }

        return null;
    }
}
