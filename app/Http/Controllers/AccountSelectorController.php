<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserCompanyRole;
use App\Models\Company;

class AccountSelectorController extends Controller
{
    /**
     * Show account selector page (for users with multiple company access).
     */
    public function index()
    {
        // Get user from web guard
        $user = auth('web')->user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Get all accessible companies (owned + staff)
        $accessibleCompanies = $user->getAccessibleCompanies();
        
        // If only one company, auto-select and redirect
        if ($accessibleCompanies->count() === 1) {
            $company = $accessibleCompanies->first();
            $user->switchToCompany($company['id']);
            return redirect()->route('client.dashboard');
        }
        
        // If no companies, redirect to dashboard (which will show company setup form)
        if ($accessibleCompanies->count() === 0) {
            return redirect()->route('client.dashboard');
        }

        return view('account-selector', compact('accessibleCompanies'));
    }
    
    /**
     * Switch active account.
     */
    public function switch(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id'
        ]);
        
        // Get user from web guard
        $user = auth('web')->user();
        
        // Verify user has access
        if (!$user->hasAccessToCompany($request->company_id)) {
            abort(403, 'You do not have access to this company');
        }
        
        $user->switchToCompany($request->company_id);
        
        return redirect()->route('client.dashboard');
    }
}

