<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Company;
use App\Models\MasterIndustryCategory;
use App\Mail\PasswordChangedEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ProfileController extends Controller
{
    public function index()
    {
        // Get user from web guard
        $user = Auth::guard('web')->user();
        $company = $user->getActiveCompany();
        
        if (!$company) {
            return redirect()->route('client.dashboard')->with('error', 'No active company found.');
        }
        
        // Get industry categories for dropdowns
        $sectors = MasterIndustryCategory::getSectors();
        $industries = collect();
        $subcategories = collect();

        if ($company->sector) {
            $sectorCategory = MasterIndustryCategory::findByNameAndLevel($company->sector, 1);
            if ($sectorCategory) {
                $industries = MasterIndustryCategory::getIndustriesBySector($sectorCategory->id);
                
                if ($company->industry) {
                    $industryCategory = MasterIndustryCategory::findByNameAndLevel($company->industry, 2, $sectorCategory->id);
                    if ($industryCategory) {
                        $subcategories = MasterIndustryCategory::getSubcategoriesByIndustry($industryCategory->id);
                    }
                }
            }
        }
        
        return view('profile.index', compact('user', 'company', 'sectors', 'industries', 'subcategories'));
    }

    public function updatePersonal(Request $request)
    {
        // Get user from web guard
        $user = Auth::guard('web')->user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'designation' => 'nullable|string|max:100',
        ]);

        // Update basic info
        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'designation' => $request->designation,
        ]);

        return back()->with('success', 'Personal information updated successfully!');
    }

    public function updatePassword(Request $request)
    {
        // Get user from web guard
        $user = Auth::guard('web')->user();
        
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.'])->withInput();
        }

        // Check if new password is different from current
        if (Hash::check($request->new_password, $user->password)) {
            return back()->withErrors(['new_password' => 'New password must be different from your current password.'])->withInput();
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        // Send password changed email notification
        try {
            Mail::to($user->email)->send(new PasswordChangedEmail($user));
        } catch (\Exception $e) {
            // Log the error but don't fail password update if email fails
            \Log::error('Failed to send password changed email: ' . $e->getMessage());
        }

        return back()->with('success', 'Password updated successfully!');
    }

    public function updateCompany(Request $request)
    {
        // Get user from web guard
        $user = Auth::guard('web')->user();
        
        if (!$user->company_id) {
            return back()->withErrors(['company' => 'No company associated with this account.']);
        }

        $request->validate([
            'company_name' => 'required|string|max:255',
            'business_email' => 'nullable|email|max:255',
            'business_website' => 'nullable|url|max:255',
            'business_address' => 'nullable|string|max:500',
            'country' => 'nullable|string|max:100',
            'sector' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'business_subcategory' => 'nullable|string|max:255',
            'business_description' => 'nullable|string|max:1000',
            'contact_person' => 'nullable|string|max:255',
            'license_no' => 'nullable|string|max:100',
            'emirate' => 'nullable|string|max:100',
        ]);

        $company = $user->getActiveCompany();
        if (!$company) {
            return back()->withErrors(['company' => 'No active company found.']);
        }

        $company->update([
            'name' => $request->company_name,
            'email' => $request->business_email,
            'website' => $request->business_website,
            'address' => $request->business_address,
            'country' => $request->country,
            'sector' => $request->sector,
            'industry' => $request->industry,
            'business_subcategory' => $request->business_subcategory,
            'description' => $request->business_description,
            'contact_person' => $request->contact_person,
            'license_no' => $request->license_no,
            'emirate' => $request->emirate,
        ]);

        return back()->with('success', 'Company information updated successfully!');
    }
}
