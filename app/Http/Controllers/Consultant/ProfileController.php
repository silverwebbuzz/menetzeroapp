<?php

namespace App\Http\Controllers\Consultant;

use App\Data\ConsultantOptions;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function edit()
    {
        $consultant = Auth::guard('consultant')->user();

        return view('consultant.profile', [
            'consultant' => $consultant,
            'emirates' => ConsultantOptions::EMIRATES,
            'languages' => ConsultantOptions::LANGUAGES,
            'specialties' => ConsultantOptions::SPECIALTIES,
        ]);
    }

    public function update(Request $request)
    {
        $consultant = Auth::guard('consultant')->user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'company_name' => 'required|string|max:255',
            'trade_license_number' => 'nullable|string|max:80',
            'bio' => 'nullable|string|max:2000',
            'emirates' => 'nullable|array',
            'emirates.*' => 'in:' . implode(',', array_keys(ConsultantOptions::EMIRATES)),
            'languages' => 'nullable|array',
            'languages.*' => 'in:' . implode(',', array_keys(ConsultantOptions::LANGUAGES)),
            'specialties' => 'nullable|array',
            'specialties.*' => 'in:' . implode(',', array_keys(ConsultantOptions::SPECIALTIES)),
            'experience_years' => 'nullable|integer|min:0|max:50',
            'website' => 'nullable|url|max:255',
            'linkedin' => 'nullable|url|max:255',
            'has_moccae_experience' => 'sometimes|boolean',
        ]);

        $data['has_moccae_experience'] = $request->boolean('has_moccae_experience');

        $consultant->update($data);

        return redirect()->route('consultant.profile.edit')
            ->with('success', 'Profile updated.');
    }

    public function submitForReview()
    {
        $consultant = Auth::guard('consultant')->user();

        if (!$consultant->canSubmitForReview()) {
            return redirect()->route('consultant.dashboard')
                ->with('error', 'Complete your profile and upload required documents (trade license + CV) before submitting.');
        }

        $consultant->update([
            'status' => 'pending_review',
            'submitted_at' => now(),
            'rejection_reason' => null,
        ]);

        return redirect()->route('consultant.dashboard')
            ->with('success', 'Application submitted. Our team will review your documents within 2–3 business days.');
    }
}
