<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CompanyInvitationService;
use App\Models\User;
use App\Models\UserCompanyAccess;
use App\Models\CompanyInvitation;
use App\Models\CompanyCustomRole;
use App\Models\RoleTemplate;
use Illuminate\Support\Facades\Auth;

class StaffManagementController extends Controller
{
    protected $invitationService;

    public function __construct(CompanyInvitationService $invitationService)
    {
        $this->invitationService = $invitationService;
    }

    /**
     * Display a listing of staff members.
     */
    public function index()
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Please complete your company setup first.');
        }

        // Get all users with access to this company
        try {
            $staffMembers = UserCompanyAccess::where('company_id', $company->id)
                ->where('status', 'active')
                ->with(['user', 'customRole'])
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
        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Please complete your company setup first.');
        }

        $customRoles = $company->customRoles()->where('is_active', true)->get();
        $templates = RoleTemplate::where('is_active', true)
            ->where(function($query) use ($company) {
                $query->where('category', $company->company_type)
                      ->orWhere('category', 'both');
            })
            ->get();

        return view('staff.create', compact('customRoles', 'templates'));
    }

    /**
     * Store a newly invited staff member.
     */
    public function store(Request $request)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Please complete your company setup first.');
        }

        $request->validate([
            'email' => 'required|email|max:255',
            'role_id' => 'nullable|exists:roles,id',
            'custom_role_id' => 'nullable|exists:company_custom_roles,id',
            'access_level' => 'required|in:view,edit,full',
            'notes' => 'nullable|string',
        ]);

        try {
            // Check if tables exist
            if (!\Schema::hasTable('user_company_accesses') || !\Schema::hasTable('company_invitations')) {
                return back()->withErrors(['email' => 'Staff management features require database migration. Please run the migration first.'])->withInput();
            }
            
            $this->invitationService->inviteUser(
                $company->id,
                $request->email,
                $request->role_id,
                Auth::id(),
                [
                    'custom_role_id' => $request->custom_role_id,
                    'access_level' => $request->access_level,
                    'notes' => $request->notes,
                ]
            );

            return redirect()->route('staff.index')
                ->with('success', 'Invitation sent successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['email' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Update staff member role.
     */
    public function updateRole(Request $request, UserCompanyAccess $access)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || $access->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'role_id' => 'nullable|exists:roles,id',
            'custom_role_id' => 'nullable|exists:company_custom_roles,id',
            'access_level' => 'required|in:view,edit,full',
        ]);

        $this->invitationService->updateAccessRole(
            $access->id,
            $request->role_id,
            $request->custom_role_id
        );

        $access->update(['access_level' => $request->access_level]);

        return redirect()->route('staff.index')
            ->with('success', 'Staff role updated successfully.');
    }

    /**
     * Remove staff member access.
     */
    public function destroy(UserCompanyAccess $access)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || $access->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }

        $this->invitationService->revokeAccess($access->id);

        return redirect()->route('staff.index')
            ->with('success', 'Staff access revoked successfully.');
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

        $invitation->update(['status' => 'cancelled']);

        return redirect()->route('staff.index')
            ->with('success', 'Invitation cancelled successfully.');
    }
}

