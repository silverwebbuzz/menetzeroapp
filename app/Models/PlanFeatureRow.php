<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanFeatureRow extends Model
{
    protected $fillable = [
        'label',
        'coming_soon',
        'value_starter',
        'value_growth',
        'value_enterprise',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'coming_soon' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Convert a stored cell value to the renderable form:
     *   'yes' => true (✓), 'no'/''/null => false (—), otherwise the raw string.
     *
     * @return bool|string
     */
    public static function castCell(?string $value)
    {
        $normalized = strtolower(trim((string) $value));

        if ($normalized === 'yes' || $normalized === 'true' || $normalized === '1') {
            return true;
        }
        if ($normalized === '' || $normalized === 'no' || $normalized === 'false' || $normalized === '0') {
            return false;
        }

        return $value;
    }

    /**
     * Shape this row the way the pricing view expects.
     */
    public function toMatrixRow(): array
    {
        return [
            'label' => $this->label,
            'coming_soon' => (bool) $this->coming_soon,
            'cells' => [
                'client_starter' => self::castCell($this->value_starter),
                'client_growth' => self::castCell($this->value_growth),
                'client_enterprise' => self::castCell($this->value_enterprise),
            ],
        ];
    }
}
