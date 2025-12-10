<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\User;
use App\Models\PartnerUser;
use Illuminate\Support\Facades\Auth;

class CompanySetupController extends Controller
{
    public function index()
    {
        // Get user from either guard
        $user = Auth::guard('partner')->user() ?? Auth::guard('web')->user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // If user already has a company, redirect to appropriate dashboard
        if ($user->company_id) {
            $company = $user->company;
            if ($company && $company->company_type === 'partner') {
                return redirect()->route('partner.dashboard');
            }
            return redirect()->route('client.dashboard');
        }

        // Check if user came from partner registration or is using partner guard
        $isPartner = session('registering_as_partner', false) || Auth::guard('partner')->check();
        
        return view('company.setup', compact('isPartner'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'business_email' => 'nullable|email|max:255',
            'business_website' => 'nullable|url|max:255',
            'business_address' => 'nullable|string|max:500',
            'country' => 'nullable|string|max:100',
            'business_category' => 'nullable|string|max:100',
            'business_subcategory' => 'nullable|string|max:100',
            'business_description' => 'nullable|string|max:1000',
        ]);

        // Get user from either guard
        $user = Auth::guard('partner')->user() ?? Auth::guard('web')->user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Determine company type from session or guard
        $isPartner = session('registering_as_partner', false) || Auth::guard('partner')->check();
        $companyType = $isPartner ? 'partner' : 'client';
        
        // Create company
        $company = Company::create([
            'name' => $request->company_name,
            'email' => $request->business_email ?? $user->email,
            'website' => $request->business_website,
            'address' => $request->business_address,
            'country' => $request->country,
            'industry' => $request->business_category,
            'business_subcategory' => $request->business_subcategory,
            'description' => $request->business_description,
            'company_type' => $companyType,
            'is_direct_client' => $companyType === 'client',
            'is_active' => true,
        ]);
        
        // Clear the session flag
        session()->forget('registering_as_partner');

        // Update user with company
        $user->update([
            'company_id' => $company->id,
            'role' => 'company_admin',
        ]);

        // Redirect to appropriate dashboard based on company type
        if ($company->company_type === 'partner') {
            return redirect()->route('partner.dashboard')->with('success', 'Business profile completed successfully!');
        }
        return redirect()->route('client.dashboard')->with('success', 'Business profile completed successfully!');
    }

}
