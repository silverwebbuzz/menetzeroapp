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
            // Use companyCustomRole relationship instead of customRole
            // Don't filter by status here - we'll check it later to show proper error messages
            $invitation = CompanyInvitation::with(['company', 'companyCustomRole'])
                ->where('token', $token)
                ->first();
            
            // Check if invitation exists
            if (!$invitation) {
                return view('invitations.invalid');
            }
            
            // Check if invitation is already accepted or rejected
            if ($invitation->status !== 'pending') {
                if ($invitation->status === 'accepted') {
                    return view('invitations.invalid', ['message' => 'This invitation has already been accepted.']);
                } elseif ($invitation->status === 'rejected') {
                    return view('invitations.invalid', ['message' => 'This invitation has been cancelled.']);
                } else {
                    return view('invitations.invalid', ['message' => 'This invitation is no longer valid.']);
                }
            }
            
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
                // Load custom role (use companyCustomRole if available, fallback to custom_role_id)
                $roleId = $invitation->company_custom_role_id ?? $invitation->custom_role_id;
                if ($roleId) {
                    $invitation->setRelation('companyCustomRole', \App\Models\CompanyCustomRole::find($roleId));
                    $invitation->setRelation('customRole', \App\Models\CompanyCustomRole::find($roleId));
                }
            } else {
                // Set customRole for backward compatibility
                if ($invitation->companyCustomRole) {
                    $invitation->setRelation('customRole', $invitation->companyCustomRole);
                } else {
                    // Fallback to custom_role_id
                    $roleId = $invitation->custom_role_id;
                    if ($roleId) {
                        $invitation->setRelation('customRole', \App\Models\CompanyCustomRole::find($roleId));
                    }
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
                $loggedInUser = Auth::guard('web')->user();
                
                // Get invitation to check email
                $invitation = CompanyInvitation::where('token', $token)->first();
                if (!$invitation) {
                    $invitation = session('invitation');
                }
                
                if (!$invitation) {
                    return back()->withErrors(['error' => 'Invitation not found.'])->withInput();
                }
                
                // Check if user already exists before accepting
                $userBeforeAccept = \App\Models\User::where('email', $invitation->email)->first();
                $userId = $loggedInUser ? $loggedInUser->id : null;

                $newUser = $this->invitationService->acceptInvitation($token, $userId);

                // If user didn't exist before accepting, redirect to password setup (Slack-style)
                if (!$userBeforeAccept) {
                    // New user - redirect to password setup
                    $passwordToken = \Illuminate\Support\Facades\Password::createToken($newUser);
                    return redirect()->route('invitations.setup-password', ['token' => $token, 'password_token' => $passwordToken])
                        ->with('user', $newUser);
                } else {
                // Existing user - log them in if not already logged in
                if (!$loggedInUser) {
                    Auth::guard('web')->login($newUser);
                }
                
                // Set active company context from invitation
                try {
                    if ($invitation && $invitation->company_id) {
                        $newUser->switchToCompany($invitation->company_id);
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to set active company context after invitation acceptance', [
                        'user_id' => $newUser->id,
                        'company_id' => $invitation->company_id ?? null,
                        'error' => $e->getMessage()
                    ]);
                }
                
                // Check if user has multiple company access - show workspace selector
                if ($newUser->hasMultipleCompanyAccess()) {
                    return redirect()->route('account.selector')
                        ->with('success', 'Invitation accepted successfully! Select a company to continue.');
                }
                
                // Single company access - go to dashboard
                return redirect()->route('client.dashboard')
                    ->with('success', 'Invitation accepted successfully!');
                }
            } else {
                // Decline invitation
                try {
                    $invitation = CompanyInvitation::where('token', $token)->firstOrFail();
                    $invitation->update(['status' => 'rejected']);
                } catch (\Exception $e) {
                    // Table might not exist
                }

                return redirect()->route('home')
                    ->with('info', 'Invitation declined.');
            }
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Show password setup page for new users after invitation acceptance.
     */
    public function setupPassword($token, $passwordToken)
    {
        try {
            $invitation = CompanyInvitation::where('token', $token)->first();
            if (!$invitation) {
                $invitation = session('invitation');
            }
            
            if (!$invitation) {
                return redirect()->route('home')->with('error', 'Invalid invitation.');
            }

            // Verify password token
            $user = \App\Models\User::where('email', $invitation->email)->first();
            if (!$user) {
                return redirect()->route('home')->with('error', 'User not found.');
            }

            return view('invitations.setup-password', [
                'invitation' => $invitation,
                'user' => $user,
                'token' => $token,
                'passwordToken' => $passwordToken,
                'email' => $invitation->email,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('home')->with('error', 'Invalid invitation.');
        }
    }

    /**
     * Process password setup.
     */
    public function processSetupPassword(Request $request, $token, $passwordToken)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        try {
            $invitation = CompanyInvitation::where('token', $token)->first();
            if (!$invitation) {
                $invitation = session('invitation');
            }

            if (!$invitation) {
                return back()->withErrors(['error' => 'Invalid invitation.'])->withInput();
            }

            $user = \App\Models\User::where('email', $invitation->email)->first();
            if (!$user) {
                return back()->withErrors(['error' => 'User not found.'])->withInput();
            }

            // Reset password using Laravel's password reset
            $status = \Illuminate\Support\Facades\Password::reset(
                [
                    'email' => $user->email,
                    'password' => $request->password,
                    'password_confirmation' => $request->password_confirmation,
                    'token' => $passwordToken,
                ],
                function ($user, $password) {
                    $user->forceFill([
                        'password' => \Illuminate\Support\Facades\Hash::make($password)
                    ])->save();
                }
            );

            if ($status === \Illuminate\Support\Facades\Password::PASSWORD_RESET) {
                // Log the user in
                Auth::guard('web')->login($user);
                
                // Set active company context from invitation
                try {
                    if ($invitation && $invitation->company_id) {
                        $user->switchToCompany($invitation->company_id);
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to set active company context after password setup', [
                        'user_id' => $user->id,
                        'company_id' => $invitation->company_id ?? null,
                        'error' => $e->getMessage()
                    ]);
                }
                
                // Check if user has multiple company access - show workspace selector
                if ($user->hasMultipleCompanyAccess()) {
                    return redirect()->route('account.selector')
                        ->with('success', 'Password set successfully! Select a company to continue.');
                }
                
                return redirect()->route('client.dashboard')
                    ->with('success', 'Password set successfully! Welcome to the platform.');
            } else {
                return back()->withErrors(['password' => __($status)])->withInput();
            }
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }
}

