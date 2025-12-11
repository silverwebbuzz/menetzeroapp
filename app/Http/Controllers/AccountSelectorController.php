<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserCompanyRole;
use App\Models\Company;

class AccountSelectorController extends Controller
{
    /**
     * Show account selector page.
     */
    public function index()
    {
        // Get user from web guard
        $user = auth('web')->user();
        
        if (!$user->hasMultipleCompanyAccess()) {
            // Single company access - redirect to dashboard
            $company = $user->getActiveCompany();
            if ($company) {
                return redirect()->route('client.dashboard');
            }
        }

        $accessibleCompanies = collect();
        
        // Add companies from user_company_roles
        $userCompanyRoles = $user->companyRoles()->where('is_active', true)->with('companyCustomRole')->get();
        foreach ($userCompanyRoles as $userCompanyRole) {
            $company = Company::find($userCompanyRole->company_id);
            if ($company) {
                $accessibleCompanies->push([
                    'id' => $company->id,
                    'name' => $company->name,
                    'type' => $company->company_type ?? 'client',
                    'role' => $userCompanyRole->companyCustomRole ? $userCompanyRole->companyCustomRole->role_name : 'N/A',
                    'last_accessed' => null,
                ]);
            }
        }
        
        // Add own company if set (for standard staff)
        if ($user->company_id) {
            $company = Company::find($user->company_id);
            if ($company) {
                $accessibleCompanies->push([
                    'id' => $company->id,
                    'name' => $company->name,
                    'type' => $company->company_type,
                    'role' => $user->role ?? 'N/A',
                    'last_accessed' => null,
                ]);
            }
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
        if (!$user->hasAccessToCompany($request->company_id) && $user->company_id != $request->company_id) {
            abort(403, 'You do not have access to this company');
        }
        
        $user->switchToCompany($request->company_id);
        
        // Update last accessed (if needed in future)
        // UserCompanyRole doesn't have last_accessed_at, but we can add it if needed
        
        return redirect()->route('client.dashboard');
    }
}

