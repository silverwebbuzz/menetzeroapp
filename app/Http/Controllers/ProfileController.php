<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $company = $user->company;
        
        return view('profile.index', compact('user', 'company'));
    }

    public function updatePersonal(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'designation' => 'nullable|string|max:100',
            'current_password' => 'nullable|string',
            'new_password' => 'nullable|string|min:8|confirmed',
        ]);

        // Update basic info
        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'designation' => $request->designation,
        ]);

        // Handle password change if provided
        if ($request->filled('current_password') && $request->filled('new_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }
            
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);
        }

        return back()->with('success', 'Personal information updated successfully!');
    }

    public function updateCompany(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->company_id) {
            return back()->withErrors(['company' => 'No company associated with this account.']);
        }

        $request->validate([
            'company_name' => 'required|string|max:255',
            'business_email' => 'nullable|email|max:255',
            'business_website' => 'nullable|url|max:255',
            'business_address' => 'nullable|string|max:500',
            'country' => 'nullable|string|max:100',
            'business_category' => 'nullable|string|max:100',
            'business_subcategory' => 'nullable|string|max:100',
            'business_description' => 'nullable|string|max:1000',
            'contact_person' => 'nullable|string|max:255',
            'license_no' => 'nullable|string|max:100',
            'emirate' => 'nullable|string|max:100',
            'sector' => 'nullable|string|max:100',
        ]);

        $user->company->update([
            'name' => $request->company_name,
            'email' => $request->business_email,
            'website' => $request->business_website,
            'address' => $request->business_address,
            'country' => $request->country,
            'industry' => $request->business_category,
            'description' => $request->business_description,
            'contact_person' => $request->contact_person,
            'license_no' => $request->license_no,
            'emirate' => $request->emirate,
            'sector' => $request->sector,
        ]);

        return back()->with('success', 'Company information updated successfully!');
    }
}
