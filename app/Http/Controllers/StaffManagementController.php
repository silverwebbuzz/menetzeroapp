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
use Illuminate\Support\Facades\Schema;

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

        try {
            $customRoles = $company->customRoles()->where('is_active', true)->get();
        } catch (\Exception $e) {
            $customRoles = collect([]);
        }
        
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
            'custom_role_id' => 'required|exists:company_custom_roles,id',
            'notes' => 'nullable|string',
        ]);

        try {
            $invitation = $this->invitationService->inviteUser(
                $company->id,
                $request->email,
                null, // role_id removed
                Auth::id(),
                [
                    'custom_role_id' => $request->custom_role_id,
                    'notes' => $request->notes,
                ]
            );

            // Store invitation in session in case it wasn't saved to DB
            session(['invitation' => $invitation]);

            // Redirect to success page with invitation details
            $invitationId = $invitation->id ?? 0;
            return redirect()->route('staff.invitation-success', $invitationId)
                ->with('invitation', $invitation);
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
            'custom_role_id' => 'required|exists:company_custom_roles,id',
        ]);

        $this->invitationService->updateAccessRole(
            $access->id,
            null, // role_id removed
            $request->custom_role_id
        );

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

        $invitation->update(['status' => 'cancelled']);

        return redirect()->route('staff.index')
            ->with('success', 'Invitation cancelled successfully.');
    }
}

