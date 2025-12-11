<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CompanyInvitationService;
use App\Services\SubscriptionService;
use App\Models\User;
use App\Models\UserCompanyRole;
use App\Models\CompanyInvitation;
use App\Models\CompanyCustomRole;
use App\Models\RoleTemplate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class StaffManagementController extends Controller
{
    protected $invitationService;
    protected $subscriptionService;

    public function __construct(CompanyInvitationService $invitationService, SubscriptionService $subscriptionService)
    {
        $this->invitationService = $invitationService;
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Display a listing of staff members.
     */
    public function index()
    {
        $this->requirePermission('staff_management', 'view');
        
        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Please complete your company setup first.');
        }

        // Get all users with access to this company
        try {
            $staffMembers = UserCompanyRole::where('company_id', $company->id)
                ->where('is_active', true)
                ->with(['user', 'companyCustomRole'])
                ->get();
        } catch (\Exception $e) {
            // Table doesn't exist yet, use empty collection
            $staffMembers = collect([]);
        }

        // Also include users directly assigned to company
        $directStaff = User::where('company_id', $company->id)
            ->where('id', '!=', Auth::id()) // Exclude current user
            ->get();

        // Get pending invitations
        try {
            $pendingInvitations = CompanyInvitation::where('company_id', $company->id)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->with('inviter')
                ->get();
        } catch (\Exception $e) {
            // Table doesn't exist yet, use empty collection
            $pendingInvitations = collect([]);
        }

        return view('staff.index', compact('staffMembers', 'directStaff', 'pendingInvitations'));
    }

    /**
     * Show the form for inviting a new staff member.
     */
    public function create()
    {
        $this->requirePermission('staff_management', 'view');
        
        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Please complete your company setup first.');
        }

        try {
            $customRoles = $company->customRoles()->where('is_active', true)->get();
        } catch (\Exception $e) {
            $customRoles = collect([]);
        }
        
        // No templates needed - using company custom roles only

        return view('staff.create', compact('customRoles'));
    }

    /**
     * Store a newly created or invited staff member.
     */
    public function store(Request $request)
    {
        $this->requirePermission('staff_management', 'add');
        
        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Please complete your company setup first.');
        }

        // If name and password provided, create user directly; otherwise send invitation
        if ($request->filled('name') && $request->filled('password')) {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'password' => 'required|string|min:8',
                'confirm_password' => 'required|same:password',
                'phone' => 'nullable|string|max:20',
                'custom_role_id' => 'required|exists:company_custom_roles,id',
            ]);

            // Check user limit
            $limitCheck = $this->subscriptionService->canPerformAction($company->id, 'users', 1);
            if (!$limitCheck['allowed']) {
                return back()->withErrors(['email' => $limitCheck['message']])->withInput();
            }

            // Check if user already exists
            $user = User::where('email', $request->email)->first();
            
            if (!$user) {
                // Create new user (company_id is null, access is via UserCompanyRole)
                // No role field - all roles managed in user_company_roles
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => \Illuminate\Support\Facades\Hash::make($request->password),
                    'phone' => $request->phone ?: null,
                    'company_id' => null, // Access is managed via UserCompanyRole
                    'is_active' => true, // New users are active by default
                ]);
            } else {
                // User exists, check if they already have access to this company
                $existingAccess = UserCompanyRole::where('user_id', $user->id)
                    ->where('company_id', $company->id)
                    ->where('is_active', true)
                    ->first();
                
                if ($existingAccess) {
                    return back()->withErrors(['email' => 'This user already has access to this company.'])->withInput();
                }
            }

            // Assign role via UserCompanyRole
            try {
                UserCompanyRole::create([
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'company_custom_role_id' => $request->custom_role_id,
                    'assigned_by' => Auth::id(),
                    'is_active' => true, // New role assignments are active by default
                ]);

                return redirect()->route('roles.index')
                    ->with('success', 'User created successfully.');
            } catch (\Exception $e) {
                \Log::error('Error creating UserCompanyRole', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                ]);
                
                return back()->withErrors(['error' => 'Failed to assign role: ' . $e->getMessage()])->withInput();
            }
        } else {
            // Send invitation (existing flow)
            $request->validate([
                'email' => 'required|email|max:255',
                'custom_role_id' => 'required|exists:company_custom_roles,id',
                'notes' => 'nullable|string',
            ]);

            $limitCheck = $this->subscriptionService->canPerformAction($company->id, 'users', 1);
            if (!$limitCheck['allowed']) {
                return back()->withErrors(['email' => $limitCheck['message']])->withInput();
            }

            try {
                $invitation = $this->invitationService->inviteUser(
                    $company->id,
                    $request->email,
                    null,
                    Auth::id(),
                    [
                        'custom_role_id' => $request->custom_role_id,
                        'notes' => $request->notes,
                    ]
                );

                session(['invitation' => $invitation]);
                $invitationId = $invitation->id ?? 0;
                return redirect()->route('staff.invitation-success', $invitationId)
                    ->with('invitation', $invitation);
            } catch (\Exception $e) {
                return back()->withErrors(['email' => $e->getMessage()])->withInput();
            }
        }
    }

    /**
     * Update staff member role.
     */
    public function updateRole(Request $request, UserCompanyRole $userCompanyRole)
    {
        $this->requirePermission('staff_management', 'edit');
        
        $company = Auth::user()->getActiveCompany();
        if (!$company || $userCompanyRole->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'company_custom_role_id' => 'required|exists:company_custom_roles,id',
        ]);

        $userCompanyRole->update([
            'company_custom_role_id' => $request->company_custom_role_id,
            'assigned_by' => Auth::id(),
        ]);

        return redirect()->route('roles.index')
            ->with('success', 'Staff role updated successfully.')
            ->with('active_tab', 'staff');
    }

    /**
     * Remove staff member access.
     */
    public function destroy(UserCompanyRole $userCompanyRole)
    {
        $this->requirePermission('staff_management', 'delete');
        
        $company = Auth::user()->getActiveCompany();
        if (!$company || $userCompanyRole->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }

        $userCompanyRole->update(['is_active' => false]);

        return redirect()->route('staff.index')
            ->with('success', 'Staff access revoked successfully.');
    }

    /**
     * Show invitation success page with details.
     */
    public function invitationSuccess($invitationId)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Please complete your company setup first.');
        }

        try {
            $invitation = CompanyInvitation::with(['company', 'inviter', 'customRole'])
                ->where('id', $invitationId)
                ->where('company_id', $company->id)
                ->first();
            
            // If invitation not found in DB (table doesn't exist), get from session
            if (!$invitation) {
                $invitation = session('invitation');
                if (!$invitation) {
                    return redirect()->route('staff.index')
                        ->with('error', 'Invitation not found.');
                }
                // Load relationships manually
                $invitation->company = $company;
                $invitation->inviter = Auth::user();
                if ($invitation->custom_role_id) {
                    $invitation->customRole = CompanyCustomRole::find($invitation->custom_role_id);
                }
            }
        } catch (\Exception $e) {
            // If table doesn't exist, get from session
            $invitation = session('invitation');
            if (!$invitation) {
                return redirect()->route('staff.index')
                    ->with('error', 'Invitation not found.');
            }
            // Load relationships manually
            $invitation->company = $company;
            $invitation->inviter = Auth::user();
            if ($invitation->custom_role_id) {
                try {
                    $invitation->customRole = CompanyCustomRole::find($invitation->custom_role_id);
                } catch (\Exception $e) {
                    $invitation->customRole = null;
                }
            }
        }

        // Generate invitation acceptance URL
        $acceptUrl = route('invitations.accept', ['token' => $invitation->token]);

        return view('staff.invitation-success', compact('invitation', 'acceptUrl'));
    }

    /**
     * Cancel pending invitation.
     */
    public function cancelInvitation(CompanyInvitation $invitation)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || $invitation->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }

        // Use 'rejected' status as 'cancelled' is not a valid enum value
        // Valid values are: 'pending', 'accepted', 'rejected', 'expired'
        $invitation->update(['status' => 'rejected']);

        return redirect()->route('staff.index')
            ->with('success', 'Invitation cancelled successfully.');
    }
}

