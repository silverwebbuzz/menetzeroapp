<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class PartnerUser extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The table associated with the model.
     */
    protected $table = 'users_partner';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'designation',
        'company_id',
        'role',
        'is_active',
        'google_id',
        'avatar',
        'provider',
        'custom_role_id',
        'external_company_name',
        'notes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the company that the user belongs to.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all companies this user has access to (multi-account access).
     */
    public function accessibleCompanies()
    {
        return $this->hasMany(UserCompanyAccess::class, 'user_id', 'id')
            ->where('user_type', 'partner');
    }

    /**
     * Get active company context.
     */
    public function activeContext()
    {
        return $this->hasOne(UserActiveContext::class, 'user_id', 'id')
            ->where('user_type', 'partner');
    }

    /**
     * Get custom role.
     */
    public function customRole()
    {
        return $this->belongsTo(CompanyCustomRole::class, 'custom_role_id');
    }

    /**
     * Check if user has access to a company.
     */
    public function hasAccessToCompany($companyId)
    {
        try {
            return $this->accessibleCompanies()
                ->where('company_id', $companyId)
                ->where('status', 'active')
                ->exists();
        } catch (\Exception $e) {
            // If table doesn't exist, fall back to company_id check
            return $this->company_id == $companyId;
        }
    }

    /**
     * Get current active company.
     */
    public function getActiveCompany()
    {
        // Super admin doesn't have active company
        if ($this->isAdmin()) {
            return null;
        }

        try {
            $context = $this->activeContext;
            if ($context && $context->active_company_id) {
                return Company::find($context->active_company_id);
            }
            
            // Fallback: If only one company access, use that
            $access = $this->accessibleCompanies()->where('status', 'active')->first();
            if ($access) {
                return Company::find($access->company_id);
            }
        } catch (\Exception $e) {
            // If tables don't exist yet, fall back to company_id
        }
        
        // Fallback: Internal user's company
        return $this->company;
    }

    /**
     * Check if user has multiple company access.
     */
    public function hasMultipleCompanyAccess()
    {
        // Super admin doesn't need company access
        if ($this->isAdmin()) {
            return false;
        }

        try {
            $accessCount = $this->accessibleCompanies()->where('status', 'active')->count();
            
            // If user has company_id set, count that too
            if ($this->company_id) {
                $accessCount++;
            }
            
            return $accessCount > 1;
        } catch (\Exception $e) {
            // If table doesn't exist yet, just check company_id
            return false;
        }
    }

    /**
     * Switch active company.
     */
    public function switchToCompany($companyId)
    {
        if (!$this->hasAccessToCompany($companyId) && $this->company_id != $companyId) {
            throw new \Exception('User does not have access to this company');
        }
        
        UserActiveContext::updateOrCreate(
            [
                'user_id' => $this->id,
                'user_type' => 'partner',
            ],
            [
                'active_company_id' => $companyId,
                'active_company_type' => Company::find($companyId)->company_type ?? 'partner',
                'last_switched_at' => now(),
            ]
        );
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a company admin.
     */
    public function isCompanyAdmin()
    {
        return $this->role === 'company_admin';
    }

    /**
     * Check if user is a company user.
     */
    public function isCompanyUser()
    {
        return $this->role === 'company_user';
    }
}

