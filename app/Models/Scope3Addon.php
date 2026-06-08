<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scope3Addon extends Model
{
    protected $table = 'scope3_addons';

    protected $fillable = [
        'name',
        'price_display',
        'items',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'items' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Shape this add-on the way the pricing view expects.
     */
    public function toMatrixAddon(): array
    {
        $includes = [];
        foreach (($this->items ?? []) as $item) {
            $includes[] = [
                'label' => $item['label'] ?? '',
                'soon' => (bool) ($item['soon'] ?? false),
            ];
        }

        return [
            'name' => $this->name,
            'price_display' => $this->price_display,
            'includes' => $includes,
        ];
    }
}
