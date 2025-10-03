<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'email', 'phone',
        'address', 'city', 'state', 'country', 'postal_code', 'website',
        'description', 'industry', 'business_subcategory', 'employee_count', 'annual_revenue', 'is_active', 'settings',
        // UAE additions
        'emirate', 'sector', 'license_no', 'contact_person',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
     */
    public function carbonEmissions()
    {
        return $this->hasMany(CarbonEmission::class);
    }

    /**
     * Get the carbon calculations for the company.
     */
    public function carbonCalculations()
    {
        return $this->hasMany(CarbonCalculation::class);
    }

    public function facilities()
    {
        return $this->hasMany(Facility::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }
}
