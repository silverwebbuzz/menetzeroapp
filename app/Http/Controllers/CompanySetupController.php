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
    /**
     * Store company information (called from dashboard form).
     * Handles both creating new companies and updating existing incomplete company info.
     */
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

        // Get active company (if exists) - for updating incomplete info
        // Check if user already has a company via user_company_roles (more reliable)
        $existingCompany = null;
        try {
            $existingUserCompanyRole = \App\Models\UserCompanyRole::where('user_id', $user->id)
                ->where('is_active', true)
                ->whereNull('company_custom_role_id') // Owner role
                ->first();
            
            if ($existingUserCompanyRole) {
                $existingCompany = \App\Models\Company::find($existingUserCompanyRole->company_id);
            }
        } catch (\Exception $e) {
            // Fallback to getActiveCompany if table doesn't exist
            $existingCompany = $user->getActiveCompany();
        }
        
        if ($existingCompany) {
            // Update existing company information
            $existingCompany->update([
                'name' => $request->company_name,
                'email' => $request->business_email ?? $existingCompany->email ?? $user->email,
                'website' => $request->business_website,
                'address' => $request->business_address,
                'country' => $request->country,
                'industry' => $request->business_category,
                'business_subcategory' => $request->business_subcategory,
                'description' => $request->business_description,
            ]);
            
            // Refresh the model to get updated data
            $company = $existingCompany->fresh();
            
            // Also update users.company_id for backward compatibility (in case it wasn't set)
            if ($user->company_id != $company->id) {
                $user->update([
                    'company_id' => $company->id,
                ]);
            }
            
            // Set active company context to ensure dashboard recognizes it
            try {
                $user->switchToCompany($company->id);
            } catch (\Exception $e) {
                // If switchToCompany fails, that's okay - getActiveCompany() has fallback logic
                \Log::warning('Failed to set active company context after update', [
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            // Create new company (always client type)
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

            // Create user_company_roles entry with company_custom_role_id = NULL (Owner)
            // NULL = Owner (0 causes FK constraint violation, so we use NULL)
            // This makes the user the owner of this company
            try {
                // Check if entry already exists (prevent duplicates)
                $existingRole = \App\Models\UserCompanyRole::where('user_id', $user->id)
                    ->where('company_id', $company->id)
                    ->first();
                
                if (!$existingRole) {
                    $userCompanyRole = \App\Models\UserCompanyRole::create([
                        'user_id' => $user->id,
                        'company_id' => $company->id,
                        'company_custom_role_id' => null, // NULL = Owner (0 causes FK constraint violation)
                        'assigned_by' => $user->id, // Self-assigned
                        'is_active' => true,
                    ]);
                    
                    // Verify it was created
                    if (!$userCompanyRole || !$userCompanyRole->id) {
                        throw new \Exception('UserCompanyRole was not created successfully');
                    }
                }
                
                // Also update users.company_id for backward compatibility
                // This ensures fallback logic in User model works correctly
                $user->update([
                    'company_id' => $company->id,
                ]);
                
                // Set active company context to ensure dashboard recognizes it
                try {
                    $user->switchToCompany($company->id);
                } catch (\Exception $e) {
                    // If switchToCompany fails, that's okay - getActiveCompany() has fallback logic
                    \Log::warning('Failed to set active company context after creation', [
                        'user_id' => $user->id,
                        'company_id' => $company->id,
                        'error' => $e->getMessage()
                    ]);
                }
            } catch (\Exception $e) {
                // Log the error for debugging
                \Log::error('Failed to create user_company_roles entry', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Don't silently fail - only fallback if it's a table doesn't exist error
                if (strpos($e->getMessage(), "doesn't exist") !== false || 
                    strpos($e->getMessage(), "not found") !== false ||
                    strpos($e->getMessage(), "Unknown table") !== false) {
                    // Table doesn't exist yet, fallback to old method
                    $user->update([
                        'company_id' => $company->id,
                        'role' => 'company_admin',
                    ]);
                } else {
                    // Re-throw other errors so we can see what's wrong
                    throw $e;
                }
            }
        }

        // Only create subscription and roles if this is a NEW company
        // Also check if user already owns a company (1 user = 1 owned company only)
        if (!$existingCompany) {
            // Check if user already owns a company
            $userOwnsCompany = \App\Models\UserCompanyRole::where('user_id', $user->id)
                ->where('is_active', true)
                ->whereNull('company_custom_role_id')
                ->exists();
            
            if ($userOwnsCompany) {
                return back()->withErrors(['error' => 'You already own a company. Each user can only own one company.'])->withInput();
            }
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
            try {
                $roleTemplates = RoleTemplate::where('is_active', true)
                    ->where('is_system_template', true)
                    ->orderBy('sort_order')
                    ->get();

                \Log::info('Creating default roles for company', [
                    'company_id' => $company->id,
                    'templates_found' => $roleTemplates->count()
                ]);

                if ($roleTemplates->isEmpty()) {
                    \Log::warning('No role templates found to create default roles', [
                        'company_id' => $company->id
                    ]);
                }

                foreach ($roleTemplates as $template) {
                    try {
                        // Check if role already exists for this company
                        $existingRole = CompanyCustomRole::where('company_id', $company->id)
                            ->where('based_on_template', $template->template_code)
                            ->first();
                        
                        if (!$existingRole) {
                            // Create company custom role
                            $customRole = CompanyCustomRole::create([
                                'company_id' => $company->id,
                                'role_name' => $template->template_name,
                                'description' => $template->description,
                                'based_on_template' => $template->template_code,
                                'is_active' => true,
                            ]);

                            \Log::info('Created company custom role', [
                                'company_id' => $company->id,
                                'role_id' => $customRole->id,
                                'role_name' => $customRole->role_name,
                                'template_code' => $template->template_code
                            ]);

                            // Copy permissions from template to company custom role
                            // Use syncWithoutDetaching to avoid duplicate entry errors
                            $templatePermissions = $template->permissions()->pluck('permissions.id')->toArray();
                            
                            \Log::info('Copying permissions from template', [
                                'template_id' => $template->id,
                                'template_code' => $template->template_code,
                                'permissions_count' => count($templatePermissions)
                            ]);

                            if (!empty($templatePermissions)) {
                                $customRole->permissions()->syncWithoutDetaching($templatePermissions);
                                
                                \Log::info('Permissions copied successfully', [
                                    'role_id' => $customRole->id,
                                    'permissions_count' => count($templatePermissions)
                                ]);
                            } else {
                                \Log::warning('Template has no permissions to copy', [
                                    'template_id' => $template->id,
                                    'template_code' => $template->template_code
                                ]);
                            }
                        } else {
                            \Log::info('Role already exists, skipping', [
                                'company_id' => $company->id,
                                'template_code' => $template->template_code,
                                'existing_role_id' => $existingRole->id
                            ]);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error creating role from template', [
                            'company_id' => $company->id,
                            'template_id' => $template->id,
                            'template_code' => $template->template_code,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        // Continue with next template even if one fails
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error creating default roles from templates', [
                    'company_id' => $company->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Don't fail the entire company setup if roles fail
            }
        }

        return redirect()->route('client.dashboard')->with('success', 'Business profile completed successfully!');
    }

}
