<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A change to the organisational boundary (GHG Protocol Chapter 5).
 *
 * Distinguishes structural change (boundary moved) from organic change (same
 * boundary, real operational performance) — only the latter belongs in a
 * reduction claim.
 */
class StructuralChange extends Model
{
    /**
     * Whether each change type transfers an EXISTING emitting activity into or
     * out of the inventory. Those require a base-year recalculation; organic
     * growth (a newly built site) does not.
     */
    public const CHANGE_TYPES = [
        'acquisition' => ['label' => 'Acquisition', 'recalculates' => true],
        'divestment' => ['label' => 'Divestment', 'recalculates' => true],
        'outsourcing' => ['label' => 'Outsourcing', 'recalculates' => true],
        'insourcing' => ['label' => 'Insourcing', 'recalculates' => true],
        'methodology' => ['label' => 'Methodology / factor change', 'recalculates' => true],
        'error_correction' => ['label' => 'Error correction', 'recalculates' => true],
        'new_build' => ['label' => 'New site built (organic growth)', 'recalculates' => false],
        'closure' => ['label' => 'Site closed (organic)', 'recalculates' => false],
    ];

    protected $fillable = [
        'company_id',
        'location_id',
        'fiscal_year',
        'change_type',
        'title',
        'description',
        'triggers_recalculation',
        'emissions_impact_tco2e',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'triggers_recalculation' => 'boolean',
        'emissions_impact_tco2e' => 'decimal:4',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function typeLabel(): string
    {
        return self::CHANGE_TYPES[$this->change_type]['label'] ?? $this->change_type;
    }

    /** Default recalculation flag for a change type. */
    public static function recalculatesByDefault(string $changeType): bool
    {
        return (bool) (self::CHANGE_TYPES[$changeType]['recalculates'] ?? false);
    }
}
