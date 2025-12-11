<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\User;
use App\Models\ClientSubscription;
use App\Models\SubscriptionPlan;
use App\Models\RoleTemplate;
use App\Models\CompanyCustomRole;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CompanySetupController extends Controller
{
    public function index()
    {
        // Get user from web guard
        $user = Auth::guard('web')->user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // If user already has a company, redirect to dashboard
        if ($user->company_id) {
            return redirect()->route('client.dashboard');
        }
        
        return view('company.setup', ['isPartner' => false]);
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

        // Get user from web guard
        $user = Auth::guard('web')->user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Create company (always client type)
        $company = Company::create([
            'name' => $request->company_name,
            'email' => $request->business_email ?? $user->email,
            'website' => $request->business_website,
            'address' => $request->business_address,
            'country' => $request->country,
            'industry' => $request->business_category,
            'business_subcategory' => $request->business_subcategory,
            'description' => $request->business_description,
            'is_active' => true,
        ]);

        // Update user with company
        $user->update([
            'company_id' => $company->id,
            'role' => 'company_admin',
        ]);

        // Create free subscription for the company
        $freePlan = SubscriptionPlan::where('plan_category', 'client')
            ->where(function($query) {
                $query->where('plan_code', 'free')
                      ->orWhere('plan_code', 'FREE')
                      ->orWhere('price_annual', 0);
            })
            ->where('is_active', true)
            ->first();

        if ($freePlan) {
            $startedAt = now();
            $expiresAt = Carbon::parse($startedAt)->addYear();

            ClientSubscription::create([
                'company_id' => $company->id,
                'subscription_plan_id' => $freePlan->id,
                'status' => 'active',
                'billing_cycle' => 'annual',
                'started_at' => $startedAt,
                'expires_at' => $expiresAt,
                'auto_renew' => true,
            ]);
        }

        // Create default custom roles from role templates
        $roleTemplates = RoleTemplate::where('is_active', true)
            ->where('is_system_template', true)
            ->orderBy('sort_order')
            ->get();

        foreach ($roleTemplates as $template) {
            // Create company custom role
            $customRole = CompanyCustomRole::create([
                'company_id' => $company->id,
                'role_name' => $template->template_name,
                'description' => $template->description,
                'based_on_template' => $template->template_code,
                'is_active' => true,
            ]);

            // Copy permissions from template to company custom role
            $templatePermissions = $template->permissions()->pluck('permissions.id')->toArray();
            if (!empty($templatePermissions)) {
                $customRole->permissions()->attach($templatePermissions);
            }
        }

        return redirect()->route('client.dashboard')->with('success', 'Business profile completed successfully!');
    }

}
