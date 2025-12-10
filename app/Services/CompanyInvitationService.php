<?php

namespace App\Services;

use App\Models\CompanyInvitation;
use App\Models\User;
use App\Models\UserCompanyAccess;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class CompanyInvitationService
{
    /**
     * Invite user to company.
     */
    public function inviteUser($companyId, $email, $roleId, $invitedBy, $data = [])
    {
        $company = \App\Models\Company::findOrFail($companyId);

        // Check if user already has access
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            $existingAccess = UserCompanyAccess::where('user_id', $existingUser->id)
                ->where('company_id', $companyId)
                ->where('status', 'active')
                ->first();
            
            if ($existingAccess) {
                throw new \Exception('User already has access to this company');
            }
        }

        // Create invitation
        // Access level is determined by the custom role's permissions, so we set a default
        $invitation = CompanyInvitation::create([
            'company_id' => $companyId,
            'email' => $email,
            'role_id' => null, // System role removed - using custom roles only
            'custom_role_id' => $data['custom_role_id'] ?? null,
            'access_level' => 'view', // Default, permissions come from custom role
            'token' => Str::random(64),
            'status' => 'pending',
            'invited_by' => $invitedBy,
            'invited_at' => now(),
            'expires_at' => now()->addDays(7),
            'notes' => $data['notes'] ?? null,
        ]);

        // Send invitation email (you'll need to create the mailable)
        // Mail::to($email)->send(new CompanyInvitationMail($invitation));

        return $invitation;
    }

    /**
     * Accept invitation.
     */
    public function acceptInvitation($token, $userId = null)
    {
        $invitation = CompanyInvitation::where('token', $token)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        // Get or create user
        if ($userId) {
            $user = User::findOrFail($userId);
        } else {
            $user = User::where('email', $invitation->email)->first();
            if (!$user) {
                // Create new user account
                $user = User::create([
                    'email' => $invitation->email,
                    'name' => $invitation->email, // Will be updated later
                    'password' => bcrypt(Str::random(16)), // Temporary password
                ]);
            }
        }

        // Create access record
        UserCompanyAccess::create([
            'user_id' => $user->id,
            'company_id' => $invitation->company_id,
            'role_id' => $invitation->role_id,
            'custom_role_id' => $invitation->custom_role_id,
            'access_level' => $invitation->access_level,
            'status' => 'active',
            'invited_by' => $invitation->invited_by,
            'invited_at' => $invitation->invited_at,
        ]);

        // Assign Spatie role if role_id is set
        if ($invitation->role_id) {
            try {
                $role = \Spatie\Permission\Models\Role::find($invitation->role_id);
                if ($role) {
                    $user->assignRole($role);
                }
            } catch (\Exception $e) {
                // Spatie not configured or role doesn't exist
            }
        }

        // Update invitation
        $invitation->update([
            'status' => 'accepted',
            'accepted_at' => now(),
            'accepted_by_user_id' => $user->id,
        ]);

        return $user;
    }

    /**
     * Revoke access.
     */
    public function revokeAccess($userCompanyAccessId)
    {
        $access = UserCompanyAccess::findOrFail($userCompanyAccessId);
        $access->update(['status' => 'revoked']);
        return $access;
    }

    /**
     * Update access role.
     */
    public function updateAccessRole($userCompanyAccessId, $roleId, $customRoleId = null)
    {
        $access = UserCompanyAccess::findOrFail($userCompanyAccessId);
        $access->update([
            'role_id' => $roleId,
            'custom_role_id' => $customRoleId,
        ]);
        return $access;
    }
}

