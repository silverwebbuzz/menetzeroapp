<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CompanySetupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        
        // If user already has a company, redirect to dashboard
        if ($user->company_id) {
            return redirect()->route('dashboard');
        }

        return view('company.setup');
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

        $user = Auth::user();

        // Create company
        $company = Company::create([
            'name' => $request->company_name,
            'email' => $request->business_email ?? $user->email,
            'website' => $request->business_website,
            'address' => $request->business_address,
            'country' => $request->country,
            'industry' => $request->business_category,
            'description' => $request->business_description,
            'is_active' => true,
        ]);

        // Update user with company
        $user->update([
            'company_id' => $company->id,
            'role' => 'company_admin',
        ]);

        return redirect()->route('dashboard')->with('success', 'Business profile completed successfully!');
    }

    public function skip()
    {
        return redirect()->route('dashboard')->with('info', 'You can complete your business profile later from the dashboard.');
    }
}
