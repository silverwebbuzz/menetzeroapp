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

        // Check invitation rules:
        // 1. User can own 1 company only (company_custom_role_id = NULL)
        // 2. User can be staff in multiple companies (company_custom_role_id > 0)
        // 3. If inviting as staff, allow even if user owns a company
        // 4. If user already has access to this company, block
        try {
            $existingUser = User::where('email', $email)->first();
            if ($existingUser) {
                // Check if user already has access to this specific company
                $existingAccess = UserCompanyRole::where('user_id', $existingUser->id)
                    ->where('company_id', $companyId)
                    ->where('is_active', true)
                    ->first();
                
                if ($existingAccess) {
                    throw new \Exception('User already has access to this company');
                }
                
                // Check if this is a staff invitation (has company_custom_role_id)
                $isStaffInvitation = !empty($data['custom_role_id']) || !empty($data['company_custom_role_id']);
                
                if ($isStaffInvitation) {
                    // Staff invitation: Allow even if user owns a company
                    // User can be staff in multiple companies
                    // No additional checks needed
                } else {
                    // Owner invitation: Check if user already owns a company
                    $ownsCompany = UserCompanyRole::where('user_id', $existingUser->id)
                        ->where('is_active', true)
                        ->whereNull('company_custom_role_id')
                        ->exists();
                    
                    if ($ownsCompany) {
                        throw new \Exception('User already owns a company. Each user can only own one company.');
                    }
                }
            }
        } catch (\Exception $e) {
            // If table doesn't exist, continue without checking
            if (strpos($e->getMessage(), "doesn't exist") === false) {
                throw $e;
            }
        }

        // Create invitation - DO NOT create user yet (user will be created when accepting invitation)
        // Use company_custom_role_id as primary field
        try {
            \Log::info('Attempting to create invitation record', [
                'company_id' => $companyId,
                'email' => $email,
                'custom_role_id' => $data['custom_role_id'] ?? null,
                'company_custom_role_id' => $data['company_custom_role_id'] ?? $data['custom_role_id'] ?? null,
            ]);

            $invitation = CompanyInvitation::create([
                'company_id' => $companyId,
                'email' => $email,
                'role_id' => null, // Legacy - not used
                'custom_role_id' => $data['custom_role_id'] ?? null, // Legacy - for backward compatibility
                'company_custom_role_id' => $data['company_custom_role_id'] ?? $data['custom_role_id'] ?? null, // Primary field
                'access_level' => 'view', // Legacy - not used
                'token' => Str::random(64),
                'status' => 'pending',
                'invited_by' => $invitedBy,
                'invited_at' => now(),
                'expires_at' => now()->addDays(7),
                'notes' => $data['notes'] ?? null,
            ]);

            \Log::info('Invitation created successfully in database', [
                'invitation_id' => $invitation->id,
                'token' => $invitation->token,
                'email' => $email
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create invitation in database', [
                'company_id' => $companyId,
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw the error so we can see what's wrong
            throw new \Exception('Failed to create invitation: ' . $e->getMessage());
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

        // Create or reactivate user company role record
        // Use company_custom_role_id (primary) or fallback to custom_role_id (backward compatibility)
        $roleId = $invitation->company_custom_role_id ?? $invitation->custom_role_id;
        
        try {
            if ($roleId) {
                // Check if user already has (or had) access to this company (including inactive records)
                $existingAccess = UserCompanyRole::where('user_id', $user->id)
                    ->where('company_id', $invitation->company_id)
                    ->first();
                
                if ($existingAccess) {
                    // Reactivate existing access and update role if needed
                    $existingAccess->update([
                        'company_custom_role_id' => $roleId,
                        'assigned_by' => $invitation->invited_by,
                        'is_active' => true,
                    ]);
                    \Log::info('Reactivated existing UserCompanyRole for re-invited staff', [
                        'user_id' => $user->id,
                        'company_id' => $invitation->company_id,
                        'user_company_role_id' => $existingAccess->id
                    ]);
                } else {
                    // Create new access record
                    UserCompanyRole::create([
                        'user_id' => $user->id,
                        'company_id' => $invitation->company_id,
                        'company_custom_role_id' => $roleId, // Staff role (not 0)
                        'assigned_by' => $invitation->invited_by,
                        'is_active' => true,
                    ]);
                    \Log::info('Created new UserCompanyRole for invited staff', [
                        'user_id' => $user->id,
                        'company_id' => $invitation->company_id
                    ]);
                }
            }
        } catch (\Exception $e) {
            // If table doesn't exist, this is fine - will be created when migration runs
            if (strpos($e->getMessage(), "doesn't exist") === false) {
                \Log::error('Failed to create/reactivate UserCompanyRole', [
                    'user_id' => $user->id,
                    'company_id' => $invitation->company_id,
                    'error' => $e->getMessage()
                ]);
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

        // Set active company context for the user
        try {
            $user->switchToCompany($invitation->company_id);
        } catch (\Exception $e) {
            // Log but don't fail - context will be set on next login
            \Log::warning('Failed to set active company context after invitation acceptance', [
                'user_id' => $user->id,
                'company_id' => $invitation->company_id,
                'error' => $e->getMessage()
            ]);
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

