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

        // Always send invitation - users will set password when accepting
        // Remove direct user creation flow - all staff must be invited
        if (false && $request->filled('name') && $request->filled('password')) {
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
            // Send invitation (always use invitation flow - no direct user creation)
            $request->validate([
                'email' => 'required|email|max:255',
                'custom_role_id' => 'required|exists:company_custom_roles,id',
                'notes' => 'nullable|string|max:1000',
            ]);

            $limitCheck = $this->subscriptionService->canPerformAction($company->id, 'users', 1);
            if (!$limitCheck['allowed']) {
                return back()->withErrors(['email' => $limitCheck['message']])->withInput();
            }

            try {
                \Log::info('Creating invitation for staff', [
                    'company_id' => $company->id,
                    'email' => $request->email,
                    'custom_role_id' => $request->custom_role_id,
                    'invited_by' => Auth::id()
                ]);

                $invitation = $this->invitationService->inviteUser(
                    $company->id,
                    $request->email,
                    null,
                    Auth::id(),
                    [
                        'custom_role_id' => $request->custom_role_id,
                        'company_custom_role_id' => $request->custom_role_id, // Ensure both are set
                        'notes' => $request->notes,
                    ]
                );

                \Log::info('Invitation created successfully', [
                    'invitation_id' => $invitation->id ?? 'N/A',
                    'token' => $invitation->token ?? 'N/A',
                    'email' => $request->email
                ]);

                // Store in session as backup
                session(['invitation' => $invitation]);
                $invitationId = $invitation->id ?? 0;
                
                // Redirect to invitation success page
                // Use invitation ID if available, otherwise use token
                if ($invitationId > 0) {
                    return redirect()->route('staff.invitation-success', ['invitation' => $invitationId])
                        ->with('invitation', $invitation)
                        ->with('success', 'Invitation sent successfully!');
                } else {
                    // If invitation ID is 0 (table doesn't exist), use token in session
                    return redirect()->route('staff.invitation-success', ['invitation' => 0])
                        ->with('invitation', $invitation)
                        ->with('success', 'Invitation sent successfully!');
                }
            } catch (\Exception $e) {
                \Log::error('Failed to create invitation', [
                    'company_id' => $company->id,
                    'email' => $request->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
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
     * Displays email and invitation link for testing (since email is not configured).
     */
    public function invitationSuccess($invitation)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Please complete your company setup first.');
        }

        // $invitation can be ID (int) from route parameter
        $invitationId = is_numeric($invitation) ? (int)$invitation : (is_object($invitation) ? $invitation->id : 0);
        
        // Try to get invitation from database first
        $invitationModel = null;
        try {
            if ($invitationId > 0) {
                // Load invitation with proper relationships
                // Note: customRole is an attribute accessor, not a relationship, so don't eager load it
                $invitationModel = CompanyInvitation::with(['company', 'inviter', 'companyCustomRole'])
                    ->where('id', $invitationId)
                    ->where('company_id', $company->id)
                    ->first();
                
                // Set customRole for backward compatibility (use companyCustomRole if available)
                if ($invitationModel) {
                    if ($invitationModel->companyCustomRole) {
                        $invitationModel->setRelation('customRole', $invitationModel->companyCustomRole);
                    } else {
                        // Fallback to custom_role_id if company_custom_role_id is null
                        $roleId = $invitationModel->custom_role_id;
                        if ($roleId) {
                            try {
                                $invitationModel->setRelation('customRole', CompanyCustomRole::find($roleId));
                            } catch (\Exception $e) {
                                // Ignore
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Error fetching invitation from database', [
                'error' => $e->getMessage(),
                'invitation_id' => $invitationId,
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        // If not found in DB, get from session
        if (!$invitationModel) {
            $invitationModel = session('invitation');
            if (!$invitationModel) {
                return redirect()->route('roles.index')
                    ->with('error', 'Invitation not found. Please try sending the invitation again.');
            }
            
            // Load relationships manually
            $invitationModel->company = $company;
            $invitationModel->inviter = Auth::user();
            
            // Get custom role (check both company_custom_role_id and custom_role_id)
            $roleId = $invitationModel->company_custom_role_id ?? $invitationModel->custom_role_id;
            if ($roleId) {
                try {
                    $invitationModel->customRole = CompanyCustomRole::find($roleId);
                } catch (\Exception $e) {
                    $invitationModel->customRole = null;
                }
            }
        }

        // Ensure token exists
        if (empty($invitationModel->token)) {
            \Log::error('Invitation missing token', [
                'invitation_id' => $invitationModel->id ?? 'N/A'
            ]);
            return redirect()->route('roles.index')
                ->with('error', 'Invitation is missing required information. Please try again.');
        }

        // Generate invitation acceptance URL
        $acceptUrl = route('invitations.accept', ['token' => $invitationModel->token]);

        \Log::info('Displaying invitation success page', [
            'invitation_id' => $invitationModel->id ?? 'N/A',
            'email' => $invitationModel->email,
            'token' => $invitationModel->token,
            'accept_url' => $acceptUrl
        ]);

        return view('staff.invitation-success', [
            'invitation' => $invitationModel,
            'acceptUrl' => $acceptUrl
        ]);
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

