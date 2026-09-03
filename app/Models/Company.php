<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'email', 'phone',
        'address', 'city', 'state', 'country', 'postal_code', 'website', 'logo_path',
        'description', 'industry', 'business_subcategory', 'employee_count', 'annual_revenue', 'is_active', 'settings',
        // UAE additions
        'emirate', 'sector', 'license_no', 'contact_person',
        // Type / channel
        'company_type', 'is_direct_client', 'consultant_id',
    ];

    /**
     * MENetZero 2.0 (Phase 6): the per-company theme opt-in.
     *
     * Stored inside the existing `settings` JSON column rather than a new
     * column, per requirement 8 (do not modify DB structures unnecessarily).
     * The column was already present, cast to array, and unused — verified:
     * nothing in the app reads or writes companies.settings today.
     *
     * Returns null when the company has expressed no preference, which is
     * what lets ThemeResolver fall through to the config default.
     */
    public function themePreference(): ?string
    {
        $theme = data_get($this->settings, 'theme');

        return is_string($theme) && $theme !== '' ? $theme : null;
    }

    /**
     * Set (or clear, with null) this company's theme opt-in.
     *
     * Merges into `settings` rather than replacing it, so any future keys
     * stored alongside survive.
     */
    public function setThemePreference(?string $theme): void
    {
        $settings = (array) ($this->settings ?? []);

        if ($theme === null) {
            unset($settings['theme']);
        } else {
            $settings['theme'] = $theme;
        }

        $this->settings = $settings;
    }

    protected $casts = [
        'is_active' => 'boolean',
        'is_direct_client' => 'boolean',
        'settings' => 'array',
        'annual_revenue' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($company) {
            if (empty($company->slug)) {
                $company->slug = static::generateUniqueSlug($company->name);
            }
        });
    }

    /**
     * Generate a unique slug for the company.
     */
    public static function generateUniqueSlug($name)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get the users for the company.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the carbon emissions for the company.
     *
     * LEGACY. carbon_emissions is empty and nothing writes to it -- emissions
     * are recorded in measurements/measurement_data. Kept only so existing
     * references do not break; use measurements() for anything that must
     * reflect real activity.
     */
    public function carbonEmissions()
    {
        return $this->hasMany(CarbonEmission::class);
    }

    /**
     * Emission entries actually recorded by this company.
     *
     * Measurements hang off locations, not companies, so this reaches them
     * through the location: company -> locations -> measurements.
     */
    public function measurements()
    {
        return $this->hasManyThrough(
            Measurement::class,
            Location::class,
            'company_id',   // locations.company_id
            'location_id',  // measurements.location_id
            'id',
            'id'
        );
    }

    /**
     * Get the carbon calculations for the company.
     */
    public function carbonCalculations()
    {
        return $this->hasMany(CarbonCalculation::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Consultant org that manages this client workspace (managed clients only).
     */
    public function consultantOrg()
    {
        return $this->belongsTo(Company::class, 'consultant_id');
    }

    /**
     * Managed client workspaces owned by this consultant org.
     */
    public function managedClients()
    {
        return $this->hasMany(Company::class, 'consultant_id');
    }

    /**
     * Agency pack subscriptions for this consultant org.
     */
    public function consultantSubscriptions()
    {
        return $this->hasMany(ConsultantSubscription::class, 'consultant_company_id');
    }

    /**
     * Client engagements where this company is the consultant org.
     */
    public function consultantEngagements()
    {
        return $this->hasMany(ConsultantClientEngagement::class, 'consultant_company_id');
    }

    /**
     * Engagements where this company is the managed client.
     */
    public function managedEngagements()
    {
        return $this->hasMany(ConsultantClientEngagement::class, 'managed_company_id');
    }

    /**
     * Get client subscriptions.
     */
    public function clientSubscriptions()
    {
        return $this->hasMany(ClientSubscription::class);
    }

    /**
     * The plan this organisation is on right now, whichever kind it is.
     *
     * A consultant agency's subscription lives in consultant_subscriptions, a
     * client's in client_subscriptions -- two tables with two models -- so
     * admin lists that show "current package" for both need one place that
     * knows which to read.
     *
     * The consultant branch mirrors ConsultantAgencySubscriptionService::
     * getActiveSubscription(): prefer a paid pack over a free trial when both
     * are active, because an agency that has bought capacity still holds its
     * trial row and the trial would otherwise be reported as its plan.
     *
     * Returns null when nothing is active -- render that as Free, since
     * PlanEntitlementService falls through to the free tier in that case.
     */
    public function currentPlanName(): ?string
    {
        if ($this->company_type === 'consultant') {
            $subs = $this->consultantSubscriptions
                ->filter(fn ($s) => $s->status === 'active'
                    && $s->expires_at !== null
                    && $s->expires_at->gte(now()->startOfDay()));

            $sub = $subs->first(fn ($s) => !$s->isFreeTrial()) ?? $subs->first();

            return $sub?->plan?->plan_name;
        }

        return $this->clientSubscriptions
            ->first(fn ($s) => $s->status === 'active'
                && $s->expires_at !== null
                && $s->expires_at->gt(now()))
            ?->plan?->plan_name;
    }

    /**
     * Get active client subscription.
     */
    public function activeClientSubscription()
    {
        return $this->clientSubscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Get user company roles.
     */
    public function userCompanyRoles()
    {
        return $this->hasMany(UserCompanyRole::class);
    }

    /**
     * Get custom roles.
     */
    public function customRoles()
    {
        return $this->hasMany(CompanyCustomRole::class);
    }

    /**
     * Get feature flags.
     */
    public function featureFlags()
    {
        return $this->hasMany(FeatureFlag::class);
    }

    /**
     * Get usage tracking records.
     */
    public function usageTracking()
    {
        return $this->hasMany(UsageTracking::class);
    }

    /**
     * Check if company is a client.
     */
    public function isClient()
    {
        return $this->company_type === 'client' || $this->company_type === null;
    }

    public function isConsultantOrg(): bool
    {
        return $this->company_type === 'consultant';
    }

    public function isManagedClient(): bool
    {
        return $this->consultant_id !== null && $this->is_direct_client === false;
    }

    public function activeConsultantSubscription(): ?ConsultantSubscription
    {
        if (!$this->isConsultantOrg()) {
            return null;
        }

        return $this->consultantSubscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>=', now()->toDateString())
            ->orderByDesc('expires_at')
            ->first();
    }

    public function activeManagedEngagement(): ?ConsultantClientEngagement
    {
        if (!$this->isManagedClient()) {
            return null;
        }

        return $this->managedEngagements()
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        return asset('storage/' . ltrim($this->logo_path, '/'));
    }

    /** Base64 data URI for embedding in PDF exports (DomPDF). */
    public function logoDataUri(): ?string
    {
        if (!$this->logo_path || !Storage::disk('public')->exists($this->logo_path)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($this->logo_path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode(
            Storage::disk('public')->get($this->logo_path)
        );
    }
}
