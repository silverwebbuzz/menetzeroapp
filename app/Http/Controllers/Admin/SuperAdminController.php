<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\RoleTemplate;
use App\Models\Permission;
use App\Models\ClientSubscription;
use App\Models\UsageTracking;
use App\Services\SubscriptionService;

class SuperAdminController extends Controller
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->middleware('auth:admin');
        
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Super Admin Dashboard
     */
    public function dashboard()
    {
        $stats = [
            'total_companies' => Company::count(),
            'total_clients' => Company::where('company_type', 'client')->orWhereNull('company_type')->count(),
            'total_users' => User::count(),
            'active_client_subscriptions' => ClientSubscription::where('status', 'active')->where('expires_at', '>', now())->count(),
        ];

        $recentCompanies = Company::with(['clientSubscriptions.plan'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentCompanies'));
    }

    /**
     * Manage Companies
     */
    public function companies(Request $request)
    {
        $query = Company::query();

        // Filters
        if ($request->filled('type')) {
            $query->where('company_type', $request->type);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $companies = $query->with(['clientSubscriptions.plan'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.companies.index', compact('companies'));
    }

    /**
     * View Company Details
     */
    public function showCompany($id)
    {
        $company = Company::with([
            'users',
            'clientSubscriptions.plan',
            'locations',
            'featureFlags',
        ])->findOrFail($id);

        return view('admin.companies.show', compact('company'));
    }

    /**
     * Manage Subscription Plans
     */
    public function subscriptionPlans()
    {
        $plans = SubscriptionPlan::orderBy('plan_category')->orderBy('sort_order')->get();
        
        return view('admin.subscription-plans.index', compact('plans'));
    }

    /**
     * Create Subscription Plan
     */
    public function createSubscriptionPlan()
    {
        return view('admin.subscription-plans.create');
    }

    /**
     * Store Subscription Plan
     */
    public function storeSubscriptionPlan(Request $request)
    {
        $request->validate([
            'plan_code' => 'required|string|max:50|unique:subscription_plans,plan_code',
            'plan_name' => 'required|string|max:255',
            'plan_category' => 'required|in:client',
            'price_annual' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'billing_cycle' => 'required|in:annual,monthly',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'limits' => 'nullable|array',
        ]);

        SubscriptionPlan::create([
            'plan_code' => $request->plan_code,
            'plan_name' => $request->plan_name,
            'plan_category' => $request->plan_category,
            'price_annual' => $request->price_annual,
            'currency' => $request->currency,
            'billing_cycle' => $request->billing_cycle,
            'description' => $request->description,
            'features' => $request->features ?? [],
            'limits' => $request->limits ?? [],
            'is_active' => $request->has('is_active'),
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return redirect()->route('admin.subscription-plans')
            ->with('success', 'Subscription plan created successfully');
    }

    /**
     * Edit Subscription Plan
     */
    public function editSubscriptionPlan($id)
    {
        $plan = SubscriptionPlan::findOrFail($id);
        return view('admin.subscription-plans.edit', compact('plan'));
    }

    /**
     * Update Subscription Plan
     */
    public function updateSubscriptionPlan(Request $request, $id)
    {
        $plan = SubscriptionPlan::findOrFail($id);

        $request->validate([
            'plan_code' => 'required|string|max:50|unique:subscription_plans,plan_code,' . $id,
            'plan_name' => 'required|string|max:255',
            'plan_category' => 'required|in:client',
            'price_annual' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'billing_cycle' => 'required|in:annual,monthly',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'limits' => 'nullable|array',
        ]);

        $plan->update([
            'plan_code' => $request->plan_code,
            'plan_name' => $request->plan_name,
            'plan_category' => $request->plan_category,
            'price_annual' => $request->price_annual,
            'currency' => $request->currency,
            'billing_cycle' => $request->billing_cycle,
            'description' => $request->description,
            'features' => $request->features ?? [],
            'limits' => $request->limits ?? [],
            'is_active' => $request->has('is_active'),
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return redirect()->route('admin.subscription-plans')
            ->with('success', 'Subscription plan updated successfully');
    }

    /**
     * Delete Subscription Plan
     */
    public function destroySubscriptionPlan($id)
    {
        $plan = SubscriptionPlan::findOrFail($id);
        
        // Check if plan has active subscriptions
        if ($plan->clientSubscriptions()->where('status', 'active')->exists()) {
            return redirect()->route('admin.subscription-plans')
                ->with('error', 'Cannot delete subscription plan with active subscriptions.');
        }

        $plan->delete();

        return redirect()->route('admin.subscription-plans')
            ->with('success', 'Subscription plan deleted successfully');
    }

    /**
     * Manage Role Templates
     */
    public function roleTemplates()
    {
        // In current deployment we only have client-side templates, so no category column is needed
        $templates = RoleTemplate::orderBy('sort_order')->get();
        return view('admin.role-templates.index', compact('templates'));
    }

    /**
     * Create Role Template
     */
    public function createRoleTemplate()
    {
        $permissions = Permission::active()->orderBy('module')->orderBy('sort_order')->get()->groupBy('module');
        return view('admin.role-templates.create', compact('permissions'));
    }

    /**
     * Store Role Template
     */
    public function storeRoleTemplate(Request $request)
    {
        $request->validate([
            'template_code' => 'required|string|max:50|unique:role_templates,template_code',
            'template_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'sort_order' => 'nullable|integer',
        ]);

        $template = RoleTemplate::create([
            'template_code' => $request->template_code,
            'template_name' => $request->template_name,
            'description' => $request->description,
            'is_system_template' => $request->has('is_system_template'),
            'is_active' => $request->has('is_active'),
            'sort_order' => $request->sort_order ?? 0,
        ]);

        // Attach permissions
        if ($request->has('permissions') && is_array($request->permissions)) {
            $template->permissions()->attach($request->permissions);
        }

        return redirect()->route('admin.role-templates')
            ->with('success', 'Role template created successfully');
    }

    /**
     * Edit Role Template
     */
    public function editRoleTemplate($id)
    {
        $template = RoleTemplate::with('permissions')->findOrFail($id);
        $permissions = Permission::active()->orderBy('module')->orderBy('sort_order')->get()->groupBy('module');
        return view('admin.role-templates.edit', compact('template', 'permissions'));
    }

    /**
     * Update Role Template
     */
    public function updateRoleTemplate(Request $request, $id)
    {
        $template = RoleTemplate::findOrFail($id);

        $request->validate([
            'template_code' => 'required|string|max:50|unique:role_templates,template_code,' . $id,
            'template_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'sort_order' => 'nullable|integer',
        ]);

        $template->update([
            'template_code' => $request->template_code,
            'template_name' => $request->template_name,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
            'sort_order' => $request->sort_order ?? 0,
        ]);

        // Sync permissions
        if ($request->has('permissions') && is_array($request->permissions)) {
            $template->permissions()->sync($request->permissions);
        } else {
            $template->permissions()->detach();
        }

        return redirect()->route('admin.role-templates')
            ->with('success', 'Role template updated successfully');
    }

    /**
     * Delete Role Template
     */
    public function destroyRoleTemplate($id)
    {
        $template = RoleTemplate::findOrFail($id);
        
        // Check if it's a system template (optional: prevent deletion of system templates)
        if ($template->is_system_template) {
            return redirect()->route('admin.role-templates')
                ->with('error', 'Cannot delete system templates. Deactivate them instead.');
        }

        // Detach permissions before deleting
        $template->permissions()->detach();
        $template->delete();

        return redirect()->route('admin.role-templates')
            ->with('success', 'Role template deleted successfully');
    }

    /**
     * Manage Users
     */
    public function users(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->with(['company', 'accessibleCompanies.company'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    /**
     * View User Details
     */
    public function showUser($id)
    {
        $user = User::with([
            'company',
            'accessibleCompanies.company',
            'activeContext.activeCompany',
        ])->findOrFail($id);

        return view('admin.users.show', compact('user'));
    }

    /**
     * System Statistics
     */
    public function statistics()
    {
        $stats = [
            'companies' => [
                'total' => Company::count(),
                'clients' => Company::where('company_type', 'client')->count(),
                'partners' => Company::where('company_type', 'partner')->count(),
                'active' => Company::where('is_active', true)->count(),
            ],
            'users' => [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'admins' => User::where('role', 'admin')->count(),
            ],
            'subscriptions' => [
                'client_active' => ClientSubscription::where('status', 'active')->where('expires_at', '>', now())->count(),
                'total_revenue' => $this->calculateTotalRevenue(),
            ],
        ];

        return view('admin.statistics', compact('stats'));
    }

    /**
     * Calculate total revenue from active subscriptions
     */
    private function calculateTotalRevenue()
    {
        return ClientSubscription::where('status', 'active')
            ->where('expires_at', '>', now())
            ->with('plan')
            ->get()
            ->sum(function($sub) {
                return $sub->plan->price_annual ?? 0;
            });
    }
}

