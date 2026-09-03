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
use App\Services\OrganisationDeletionService;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
    /**
     * Organisation list: direct companies, or consultants.
     *
     * Companies a consultant manages are NOT a top-level tab -- they belong to
     * their agency and are listed on its detail page. "Direct" therefore means
     * any client company with no consultant behind it at all.
     *
     * The consultant_id test is used ALONE here, deliberately. Company::
     * isManagedClient() additionally requires is_direct_client = false, but
     * OrganisationDeletionService::blockerFor() blocks on consultant_id alone.
     * Using the stricter test in the list produced a company that blocked a
     * deletion while appearing in no tab -- see redesign.md §87.
     */
    public function companies(Request $request)
    {
        $tab = $request->query('tab') === 'consultant' ? 'consultant' : 'direct';

        $query = Company::query();

        if ($tab === 'consultant') {
            $query->where('company_type', 'consultant');
        } else {
            $query->where('company_type', '!=', 'consultant')
                ->whereNull('consultant_id');
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        // Dormant = free plan, and nothing entered for N days. Judged on real
        // data (emissions + locations) as well as last login, because signing
        // in and entering nothing is exactly the case being looked for.
        $dormantDays = (int) $request->query('dormant_days', 30);
        if ($request->boolean('dormant')) {
            $cutoff = now()->subDays($dormantDays);

            $query->whereDoesntHave('measurements', fn ($q) => $q->where('created_at', '>=', $cutoff))
                ->whereDoesntHave('locations', fn ($q) => $q->where('created_at', '>=', $cutoff))
                ->where('created_at', '<', $cutoff)
                ->whereDoesntHave('clientSubscriptions.plan', fn ($p) => $p->where('price_annual', '>', 0));
        }

        // Both subscription kinds eager-loaded: currentPlanName() reads
        // whichever matches the company type, and without this it would fire
        // two queries per row.
        $companies = $query->with(['clientSubscriptions.plan', 'consultantSubscriptions.plan'])
            ->withCount(['users', 'measurements', 'locations', 'managedClients'])
            ->withMax('users', 'last_login_at')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'direct' => Company::where('company_type', '!=', 'consultant')
                ->whereNull('consultant_id')
                ->count(),
            'consultant' => Company::where('company_type', 'consultant')->count(),
        ];

        return view('admin.companies.index', compact('companies', 'tab', 'counts', 'dormantDays'));
    }

    /**
     * View Company Details
     */
    /**
     * Where "back" goes from a company detail page.
     *
     * The `from` query parameter records which list the admin arrived from:
     * a tab name, or "agency:<id>" when they came from an agency's client
     * table. Without it every action returned to the default companies tab,
     * so opening a consultant and acting on it dumped the admin into the
     * direct-companies list.
     *
     * Whitelisted rather than trusted: `from` is user input, and turning it
     * straight into a redirect would be an open-redirect hole.
     */
    protected function resolveBackUrl(?string $from): array
    {
        if (is_string($from) && str_starts_with($from, 'agency:')) {
            $agencyId = (int) substr($from, 7);

            if ($agencyId > 0 && Company::whereKey($agencyId)->exists()) {
                return [
                    'url' => route('admin.companies.show', ['id' => $agencyId, 'from' => 'consultant']),
                    'label' => 'Back to agency',
                ];
            }
        }

        $tab = $from === 'consultant' ? 'consultant' : 'direct';

        return [
            'url' => route('admin.companies.index', ['tab' => $tab]),
            'label' => $tab === 'consultant' ? 'All consultants' : 'All companies',
        ];
    }

    public function showCompany(Request $request, $id)
    {
        $back = $this->resolveBackUrl($request->query('from'));

        $company = Company::with([
            'users',
            'clientSubscriptions.plan',
            'locations',
            'featureFlags',
        ])->findOrFail($id);

        $people = $this->peopleFor($company);

        $grantPlans = SubscriptionPlan::where('plan_category', 'client')
            ->where('is_active', true)
            ->where('price_annual', '>', 0)
            ->orderBy('sort_order')
            ->get();

        $consultantPacks = SubscriptionPlan::where('plan_category', 'consultant_agency')
            ->where(function ($q) {
                $q->where('is_active', true)
                    ->orWhere('plan_code', \App\Data\ConsultantAgencyPlanMatrix::DEMO_PACK_CODE);
            })
            ->orderBy('sort_order')
            ->get();

        $packageAssignments = \App\Models\AdminPackageAssignment::with(['plan', 'admin'])
            ->where('company_id', $company->id)
            ->latest()
            ->get();

        return view('admin.companies.show', compact('company', 'grantPlans', 'consultantPacks', 'packageAssignments', 'back', 'people'));
    }

    /**
     * Everyone who can sign in to this organisation, as flat rows for the
     * detail page.
     *
     * Two different things are collapsed here on purpose, because from an
     * admin's point of view they are the same question -- "who has access?":
     *
     *   users        the normal workspace accounts (the `web` guard)
     *   consultants  the agency's own login, a SEPARATE auth table with its own
     *                email and password. Nothing on this page used to show it,
     *                so an agency's primary account was invisible to admin.
     *
     * Roles come from user_company_roles, not users.role: that column is legacy
     * and is no longer what the application authorises against. A membership row
     * with a null/0 company_custom_role_id means owner, not "no role".
     */
    protected function peopleFor(Company $company): array
    {
        $rows = [];

        $memberships = DB::table('user_company_roles')
            ->leftJoin(
                'company_custom_roles',
                'user_company_roles.company_custom_role_id',
                '=',
                'company_custom_roles.id'
            )
            ->where('user_company_roles.company_id', $company->id)
            ->select(
                'user_company_roles.user_id',
                'user_company_roles.is_active',
                'user_company_roles.company_custom_role_id',
                'company_custom_roles.role_name'
            )
            ->get()
            ->keyBy('user_id');

        // A user may belong to more than one company; the count tells the admin
        // whether deleting this company would remove the account or just detach it.
        $otherMemberships = DB::table('user_company_roles')
            ->whereIn('user_id', $company->users->pluck('id'))
            ->where('company_id', '!=', $company->id)
            ->where('is_active', true)
            ->select('user_id', DB::raw('COUNT(*) as total'))
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        foreach ($company->users as $user) {
            $membership = $memberships->get($user->id);

            $rows[] = [
                'kind' => 'User',
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? null,
                'designation' => $user->designation ?? null,
                'role' => $membership
                    ? ($membership->role_name ?: 'Owner')
                    : '—',
                'active' => $membership ? (bool) $membership->is_active : (bool) $user->is_active,
                'verified' => $user->email_verified_at !== null,
                'last_login_at' => $user->last_login_at,
                'last_login_ip' => $user->last_login_ip,
                'login_count' => $user->login_count ?? 0,
                'created_at' => $user->created_at,
                'other_companies' => (int) ($otherMemberships[$user->id] ?? 0),
            ];
        }

        foreach ($this->consultantLoginsFor($company) as $consultant) {
            $rows[] = [
                'kind' => 'Consultant login',
                'name' => $consultant->name,
                'email' => $consultant->email,
                'phone' => $consultant->phone,
                'designation' => $consultant->company_name,
                'role' => 'Agency owner',
                'active' => (bool) $consultant->is_active,
                'verified' => null,
                'last_login_at' => $consultant->last_login_at ?? null,
                'last_login_ip' => $consultant->last_login_ip ?? null,
                'login_count' => $consultant->login_count ?? 0,
                'created_at' => $consultant->created_at,
                'other_companies' => 0,
            ];
        }

        return $rows;
    }

    /**
     * The consultant profile(s) whose agency workspace IS this company.
     * Guarded because agency_company_id is added by a later migration.
     */
    protected function consultantLoginsFor(Company $company)
    {
        if (!Schema::hasTable('consultants') || !Schema::hasColumn('consultants', 'agency_company_id')) {
            return collect();
        }

        return DB::table('consultants')->where('agency_company_id', $company->id)->get();
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
        // Accept features/limits from JSON textareas and force annual billing.
        $request->merge([
            'features' => $this->normalizeJsonArray($request->input('features')),
            'limits' => $this->normalizeJsonArray($request->input('limits')),
            'billing_cycle' => 'annual',
        ]);

        $request->validate([
            'plan_code' => 'required|string|max:50|unique:subscription_plans,plan_code',
            'plan_name' => 'required|string|max:255',
            'plan_category' => 'required|in:client',
            'price_annual' => 'required|numeric|min:0',
            'price_inr' => 'nullable|numeric|min:0',
            'currency' => 'required|string|max:3',
            'billing_cycle' => 'required|in:annual',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'limits' => 'nullable|array',
        ]);

        SubscriptionPlan::create([
            'plan_code' => $request->plan_code,
            'plan_name' => $request->plan_name,
            'plan_category' => $request->plan_category,
            'price_annual' => $request->price_annual,
            'price_inr' => $request->price_inr,
            'currency' => $request->currency,
            'billing_cycle' => 'annual',
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

        // Accept features/limits from JSON textareas and force annual billing.
        $request->merge([
            'features' => $this->normalizeJsonArray($request->input('features')),
            'limits' => $this->normalizeJsonArray($request->input('limits')),
            'entitlements' => $this->normalizeJsonArray($request->input('entitlements')),
            'billing_cycle' => 'annual',
        ]);

        $request->validate([
            'plan_code' => 'required|string|max:50|unique:subscription_plans,plan_code,' . $id,
            'plan_name' => 'required|string|max:255',
            'plan_category' => 'required|in:client',
            'price_annual' => 'required|numeric|min:0',
            'price_inr' => 'nullable|numeric|min:0',
            'currency' => 'required|string|max:3',
            'billing_cycle' => 'required|in:annual',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'limits' => 'nullable|array',
            'entitlements' => 'nullable|array',
        ]);

        $payload = [
            'plan_code' => $request->plan_code,
            'plan_name' => $request->plan_name,
            'plan_category' => $request->plan_category,
            'price_annual' => $request->price_annual,
            'price_inr' => $request->price_inr,
            'currency' => $request->currency,
            'billing_cycle' => 'annual',
            'description' => $request->description,
            'features' => $request->features ?? [],
            'limits' => $request->limits ?? [],
            'is_active' => $request->has('is_active'),
            'sort_order' => $request->sort_order ?? 0,
        ];

        if ($request->filled('entitlements')) {
            $payload['entitlements'] = $request->entitlements;
        }

        $plan->update($payload);

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
     * Normalize a features/limits value coming from the admin form. The form
     * sends a JSON string (textarea); decode it to an array so validation and
     * the JSON column both receive an array.
     */
    private function normalizeJsonArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
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
                'consultants' => Company::where('company_type', 'consultant')->count(),
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

    /**
     * Permanently delete a company and everything belonging to it.
     *
     * Guarded by a typed-name confirmation: the admin must retype the exact
     * company name. A misclick must not be able to erase an organisation, and
     * typing the name forces them to read which one they are on.
     */
    public function destroyCompany(Request $request, $id, OrganisationDeletionService $deletions)
    {
        $company = Company::findOrFail($id);

        $request->validate(['confirm_name' => 'required|string']);

        if (trim($request->input('confirm_name')) !== trim((string) $company->name)) {
            return back()->with('error', 'The name you typed does not match. Nothing was deleted.');
        }

        try {
            $summary = $deletions->deleteCompany($company, (int) auth('admin')->id());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        // Back to the list the admin came from, not the default tab.
        // input(), not query(): the delete form POSTs `from` in the body.
        return redirect()->to($this->resolveBackUrl($request->input('from'))['url'])->with(
            'success',
            "Deleted {$summary['name']} permanently — {$summary['users_deleted']} user(s) removed, "
            . "{$summary['users_detached']} kept (member of another company), "
            . "{$summary['invoices_deleted']} invoice(s) removed"
            . ($summary['consultants_deleted'] > 0
                ? ", {$summary['consultants_deleted']} consultant login(s) removed."
                : '.')
        );
    }
}

