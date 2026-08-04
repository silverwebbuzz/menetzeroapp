<?php

namespace App\Models;

use App\Data\CompanyPackageOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultantEntityRequest extends Model
{
    protected $fillable = [
        'consultant_company_id',
        'user_id',
        'entity_count',
        'lines',
        'package_code',
        'needs_sites_over_5',
        'extras',
        'wants_enterprise',
        'message',
        'status',
        'admin_notes',
        'quote_amount_aed',
        'quote_breakdown',
        'quoted_at',
        'paid_at',
        'activated_at',
    ];

    protected $casts = [
        'needs_sites_over_5' => 'boolean',
        'wants_enterprise' => 'boolean',
        'entity_count' => 'integer',
        'extras' => 'array',
        'lines' => 'array',
        'quote_amount_aed' => 'float',
        'quoted_at' => 'datetime',
        'paid_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    /**
     * Normalized request lines: list of {package_code, entity_count}.
     * Falls back to legacy single package_code + entity_count.
     *
     * @return list<array{package_code: string, entity_count: int}>
     */
    public function normalizedLines(): array
    {
        $raw = $this->lines;
        if (is_array($raw) && $raw !== []) {
            $out = [];
            foreach ($raw as $line) {
                if (!is_array($line)) {
                    continue;
                }
                $code = (string) ($line['package_code'] ?? '');
                $count = (int) ($line['entity_count'] ?? 0);
                if ($code === '' || $count < 1) {
                    continue;
                }
                if (isset($out[$code])) {
                    $out[$code]['entity_count'] += $count;
                } else {
                    $out[$code] = [
                        'package_code' => $code,
                        'entity_count' => $count,
                    ];
                }
            }

            return array_values($out);
        }

        $code = $this->package_code
            ?? ($this->wants_enterprise ? 'client_enterprise' : null);

        if (!$code) {
            return [];
        }

        return [[
            'package_code' => $code,
            'entity_count' => max(1, (int) $this->entity_count),
        ]];
    }

    public function totalEntityCount(): int
    {
        return array_sum(array_column($this->normalizedLines(), 'entity_count'));
    }

    public function packageLabel(): string
    {
        $lines = $this->normalizedLines();
        if (count($lines) > 1) {
            return collect($lines)
                ->map(fn (array $l) => CompanyPackageOptions::label($l['package_code']) . ' ×' . $l['entity_count'])
                ->implode(', ');
        }

        if (count($lines) === 1) {
            return CompanyPackageOptions::label($lines[0]['package_code']) . ' ×' . $lines[0]['entity_count'];
        }

        if ($this->package_code) {
            return CompanyPackageOptions::label($this->package_code);
        }

        return $this->wants_enterprise ? 'Enterprise' : 'Standard';
    }

    public function consultantCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'consultant_company_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
