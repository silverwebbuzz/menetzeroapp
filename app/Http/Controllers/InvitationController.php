<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CompanyInvitation;
use App\Services\CompanyInvitationService;
use Illuminate\Support\Facades\Auth;

class InvitationController extends Controller
{
    protected $invitationService;

    public function __construct(CompanyInvitationService $invitationService)
    {
        $this->invitationService = $invitationService;
    }

    /**
     * Show invitation acceptance page.
     */
    public function accept($token)
    {
        try {
            $invitation = CompanyInvitation::with(['company', 'customRole'])
                ->where('token', $token)
                ->where('status', 'pending')
                ->first();
            
            // If not found in DB, check session (for testing without migrations)
            if (!$invitation) {
                $invitation = session('invitation');
                if (!$invitation || $invitation->token !== $token) {
                    return view('invitations.invalid');
                }
                // Load relationships manually
                if ($invitation->company_id) {
                    $invitation->company = \App\Models\Company::find($invitation->company_id);
                }
                if ($invitation->custom_role_id) {
                    $invitation->customRole = \App\Models\CompanyCustomRole::find($invitation->custom_role_id);
                }
            }

            // Check if invitation is expired
            if ($invitation->expires_at && $invitation->expires_at < now()) {
                return view('invitations.expired', compact('invitation'));
            }

            // Check if user is already logged in
            $user = Auth::guard('web')->user();
            if ($user && $user->email === $invitation->email) {
                // User is logged in and email matches, can auto-accept
                return view('invitations.accept', compact('invitation', 'user'));
            }

            return view('invitations.accept', compact('invitation'));
        } catch (\Exception $e) {
            return view('invitations.invalid');
        }
    }

    /**
     * Process invitation acceptance.
     */
    public function processAccept(Request $request, $token)
    {
        $request->validate([
            'action' => 'required|in:accept,decline',
        ]);

        try {
            if ($request->action === 'accept') {
                $user = Auth::guard('web')->user();
                $userId = $user ? $user->id : null;

                $this->invitationService->acceptInvitation($token, $userId);

                return redirect()->route('login')
                    ->with('success', 'Invitation accepted successfully! Please log in to access your account.');
            } else {
                // Decline invitation
                $invitation = CompanyInvitation::where('token', $token)->firstOrFail();
                $invitation->update(['status' => 'rejected']);

                return redirect()->route('home')
                    ->with('info', 'Invitation declined.');
            }
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }
}

