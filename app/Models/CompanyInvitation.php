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
        'company_type',
        'email',
        'role_id',
        'custom_role_id',
        'access_level',
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

