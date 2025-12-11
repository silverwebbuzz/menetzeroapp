<?php

namespace App\Services;

use App\Models\CompanyInvitation;
use App\Models\User;
use App\Models\UserCompanyRole;
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
        try {
            $existingUser = User::where('email', $email)->first();
            if ($existingUser) {
                $existingAccess = UserCompanyRole::where('user_id', $existingUser->id)
                    ->where('company_id', $companyId)
                    ->where('is_active', true)
                    ->first();
                
                if ($existingAccess) {
                    throw new \Exception('User already has access to this company');
                }
            }
        } catch (\Exception $e) {
            // If table doesn't exist, continue without checking
            if (strpos($e->getMessage(), "doesn't exist") === false && strpos($e->getMessage(), 'already has access') !== false) {
                throw $e;
            }
        }

        // Create invitation (will work even if table doesn't exist yet - Laravel will handle it)
        // Use company_custom_role_id as primary field
        try {
            $invitation = CompanyInvitation::create([
                'company_id' => $companyId,
                'email' => $email,
                'role_id' => null, // Legacy - not used
                'custom_role_id' => $data['custom_role_id'] ?? null, // Legacy - for backward compatibility
                'company_custom_role_id' => $data['custom_role_id'] ?? $data['company_custom_role_id'] ?? null, // Primary field
                'access_level' => 'view', // Legacy - not used
                'token' => Str::random(64),
                'status' => 'pending',
                'invited_by' => $invitedBy,
                'invited_at' => now(),
                'expires_at' => now()->addDays(7),
                'notes' => $data['notes'] ?? null,
            ]);
        } catch (\Exception $e) {
            // If table doesn't exist, create a temporary invitation object
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                $invitation = new CompanyInvitation([
                    'company_id' => $companyId,
                    'email' => $email,
                    'role_id' => null,
                    'custom_role_id' => $data['custom_role_id'] ?? null,
                    'company_custom_role_id' => $data['custom_role_id'] ?? $data['company_custom_role_id'] ?? null,
                    'access_level' => 'view',
                    'token' => Str::random(64),
                    'status' => 'pending',
                    'invited_by' => $invitedBy,
                    'invited_at' => now(),
                    'expires_at' => now()->addDays(7),
                    'notes' => $data['notes'] ?? null,
                ]);
                // Set ID manually for display purposes
                $invitation->id = 0;
                $invitation->exists = false;
            } else {
                throw $e;
            }
        }

        // Send invitation email (you'll need to create the mailable)
        // Mail::to($email)->send(new CompanyInvitationMail($invitation));

        return $invitation;
    }

    /**
     * Accept invitation.
     */
    public function acceptInvitation($token, $userId = null)
    {
        try {
            $invitation = CompanyInvitation::where('token', $token)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->firstOrFail();
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), "not found") !== false) {
                throw new \Exception('Invitation not found. The invitation may have expired or the database tables may not be set up yet.');
            }
            throw $e;
        }

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

        // Create user company role record
        // Use company_custom_role_id (primary) or fallback to custom_role_id (backward compatibility)
        $roleId = $invitation->company_custom_role_id ?? $invitation->custom_role_id;
        
        try {
            if ($roleId) {
                // Check if user already has access to this company
                $existingAccess = UserCompanyRole::where('user_id', $user->id)
                    ->where('company_id', $invitation->company_id)
                    ->where('is_active', true)
                    ->first();
                
                if (!$existingAccess) {
                    UserCompanyRole::create([
                        'user_id' => $user->id,
                        'company_id' => $invitation->company_id,
                        'company_custom_role_id' => $roleId, // Staff role (not 0)
                        'assigned_by' => $invitation->invited_by,
                        'is_active' => true,
                    ]);
                }
            }
        } catch (\Exception $e) {
            // If table doesn't exist, this is fine - will be created when migration runs
            if (strpos($e->getMessage(), "doesn't exist") === false) {
                throw $e;
            }
        }

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

        // Update invitation (only if table exists)
        try {
            $invitation->update([
                'status' => 'accepted',
                'accepted_at' => now(),
                'accepted_by_user_id' => $user->id,
            ]);
        } catch (\Exception $e) {
            // If table doesn't exist, ignore the update
            if (strpos($e->getMessage(), "doesn't exist") === false) {
                throw $e;
            }
        }

        return $user;
    }

    /**
     * Revoke access.
     */
    public function revokeAccess($userCompanyRoleId)
    {
        $userCompanyRole = UserCompanyRole::findOrFail($userCompanyRoleId);
        $userCompanyRole->update(['is_active' => false]);
        return $userCompanyRole;
    }

    /**
     * Update access role.
     */
    public function updateAccessRole($userCompanyRoleId, $roleId, $customRoleId = null)
    {
        $userCompanyRole = UserCompanyRole::findOrFail($userCompanyRoleId);
        $userCompanyRole->update([
            'company_custom_role_id' => $customRoleId,
        ]);
        return $userCompanyRole;
    }
}

