<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CompanyInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'email',
        'role_id', // Legacy - not used
        'custom_role_id', // Legacy - kept for backward compatibility
        'company_custom_role_id', // NEW - primary field for role assignment
        'access_level', // Legacy - not used
        'token',
        'status',
        'invited_by',
        'invited_at',
        'expires_at',
        'accepted_at',
        'accepted_by_user_id',
        'notes',
    ];

    protected $casts = [
        'invited_at' => 'datetime',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invitation) {
            if (empty($invitation->token)) {
                $invitation->token = Str::random(64);
            }
            if (empty($invitation->invited_at)) {
                $invitation->invited_at = now();
            }
            if (empty($invitation->expires_at)) {
                $invitation->expires_at = now()->addDays(7);
            }
        });
    }

    /**
     * Get the company.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who invited.
     */
    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Get the user who accepted.
     */
    public function accepter()
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }

    /**
     * Get the custom role (using company_custom_role_id).
     */
    public function customRole()
    {
        // Use company_custom_role_id if available, fallback to custom_role_id for backward compatibility
        $roleId = $this->company_custom_role_id ?? $this->custom_role_id;
        if ($roleId) {
            return CompanyCustomRole::find($roleId);
        }
        return null;
    }

    /**
     * Get the company custom role (primary method).
     */
    public function companyCustomRole()
    {
        $roleId = $this->company_custom_role_id ?? $this->custom_role_id;
        if ($roleId) {
            return $this->belongsTo(CompanyCustomRole::class, 'company_custom_role_id');
        }
        return null;
    }

    /**
     * Check if invitation is expired.
     */
    public function isExpired()
    {
        return $this->expires_at < now();
    }

    /**
     * Check if invitation is pending.
     */
    public function isPending()
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * Scope for pending invitations.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>', now());
    }
}

