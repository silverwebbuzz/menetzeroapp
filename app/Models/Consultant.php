<?php

namespace App\Models;

use App\Data\ConsultantOptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Consultant extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'google_id',
        'avatar',
        'provider',
        'password',
        'phone',
        'company_name',
        'trade_license_number',
        'bio',
        'emirates',
        'languages',
        'specialties',
        'experience_years',
        'website',
        'linkedin',
        'has_moccae_experience',
        'is_featured',
        'status',
        'admin_notes',
        'rejection_reason',
        'submitted_at',
        'reviewed_at',
        'reviewed_by_admin_id',
        'is_active',
        'agency_company_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'emirates' => 'array',
            'languages' => 'array',
            'specialties' => 'array',
            'has_moccae_experience' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Send the password reset notification using the admin-editable template.
     */
    public function sendPasswordResetNotification($token): void
    {
        $resetUrl = url(route('consultant.password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ], false));

        app(\App\Services\EmailTemplateService::class)->sendToConsultant('consultant_password_reset', $this, [
            'reset_url' => $resetUrl,
        ]);
    }

    /**
     * ConsultantAccountService mirrors the consultant credential onto a web User
     * row (same email) for client workspaces. After a reset, push the new hash
     * across so both logins accept the same password.
     */
    public function syncLinkedUserPassword(): void
    {
        $user = \App\Models\User::where('email', $this->email)->first();

        if (!$user) {
            return;
        }

        $user->forceFill(['password' => $this->getAttributes()['password']])->save();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ConsultantDocument::class);
    }

    public function introRequests(): HasMany
    {
        return $this->hasMany(ConsultantIntroRequest::class);
    }

    public function publicInquiries(): HasMany
    {
        return $this->hasMany(ConsultantPublicInquiry::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ConsultantOrder::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by_admin_id');
    }

    public function agencyCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'agency_company_id');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved' && $this->is_active;
    }

    public function isListed(): bool
    {
        return in_array($this->status, ['approved'], true) && $this->is_active;
    }

    public function canSubmitForReview(): bool
    {
        if (!in_array($this->status, ['draft', 'rejected'], true)) {
            return false;
        }

        foreach (ConsultantOptions::REQUIRED_DOCUMENT_TYPES as $type) {
            if (!$this->documents()->where('document_type', $type)->exists()) {
                return false;
            }
        }

        return filled($this->bio)
            && is_array($this->emirates) && count($this->emirates) > 0
            && is_array($this->specialties) && count($this->specialties) > 0;
    }

    public function statusLabel(): string
    {
        return ConsultantOptions::labelFor('status', $this->status);
    }

    public function specialtyLabels(): array
    {
        return array_map(
            fn (string $key) => ConsultantOptions::labelFor('specialty', $key),
            $this->specialties ?? []
        );
    }

    public function emirateLabels(): array
    {
        return array_map(
            fn (string $key) => ConsultantOptions::labelFor('emirate', $key),
            $this->emirates ?? []
        );
    }
}
