<?php

namespace Database\Seeders;

use App\Models\Consultant;
use App\Models\EmissionFactor;
use App\Models\EmissionSourceMaster;
use App\Models\Location;
use App\Models\MeasurementData;
use App\Services\ConsultantAccountService;
use App\Services\ConsultantAgencyClientService;
use App\Services\ConsultantAgencySubscriptionService;
use App\Services\MeasurementService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * One consultant with agency org, 1 managed client, location, and emissions for end-to-end testing.
 *
 * Run: php artisan db:seed --class=ConsultantFullDemoSeeder
 */
class ConsultantFullDemoSeeder extends Seeder
{
    public const EMAIL = 'demo.full@menetzero.com';

    public const PASSWORD = 'FullDemo1!';

    public function run(): void
    {
        if (Consultant::where('email', self::EMAIL)->exists()) {
            $this->command?->warn('ConsultantFullDemoSeeder: ' . self::EMAIL . ' already exists — skipping.');

            return;
        }

        $reportingYear = (int) date('Y');

        $consultant = Consultant::create([
            'name' => 'Demo Consultant',
            'email' => self::EMAIL,
            'password' => Hash::make(self::PASSWORD),
            'phone' => '+971501234567',
            'company_name' => 'Silver Webbuzz Sustainability Practice',
            'trade_license_number' => 'DEMO-TL-001',
            'bio' => 'Demo consultant account for testing managed client workspaces, emissions entry, and the client interface from the agency hub.',
            'emirates' => ['dubai', 'abu_dhabi'],
            'languages' => ['en', 'ar'],
            'specialties' => ['moccae', 'ghg_protocol', 'ifrs_s2'],
            'experience_years' => 8,
            'has_moccae_experience' => true,
            'status' => 'draft',
            'is_active' => true,
        ]);

        $accountService = app(ConsultantAccountService::class);
        ['user' => $user, 'company' => $consultantOrg] = $accountService->ensureLinked($consultant);

        app(ConsultantAgencySubscriptionService::class)->ensureFreeTrialSubscription($consultantOrg);

        $engagement = app(ConsultantAgencyClientService::class)->create($consultantOrg, [
            'name' => 'Al Noor Trading LLC',
            'display_name' => 'Al Noor Trading',
            'primary_reporting_year' => $reportingYear,
            'country' => 'United Arab Emirates',
            'emirate' => 'Dubai',
            'sector' => 'Trading & Distribution',
            'industry' => 'Wholesale trade',
            'contact_person' => 'Ahmed Al Noor',
            'description' => 'Demo managed client for consultant workspace testing.',
        ]);

        $managed = $engagement->managedCompany;

        $location = Location::create([
            'company_id' => $managed->id,
            'name' => 'Dubai Head Office',
            'address' => 'Business Bay, Dubai',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'location_type' => 'office',
            'is_head_office' => true,
            'is_active' => true,
            'staff_count' => 25,
        ]);

        $measurementService = app(MeasurementService::class);
        $measurement = $measurementService->getOrCreateMeasurement($location->id, $reportingYear);

        $entriesCreated = $this->seedEmissionEntries($measurement->id, $user->id, $reportingYear);
        $measurementService->updateMeasurementTotals($measurement->id);
        $measurement->refresh();

        $this->command?->info('');
        $this->command?->info('✅ Consultant full demo seeded');
        $this->command?->table(
            ['Item', 'Value'],
            [
                ['Consultant login', self::EMAIL],
                ['Password', self::PASSWORD],
                ['Consultant portal', '/consultant/login'],
                ['Agency org', $consultantOrg->name . ' (id ' . $consultantOrg->id . ')'],
                ['Managed client', $managed->name . ' (id ' . $managed->id . ')'],
                ['Reporting year (PRY)', (string) $reportingYear],
                ['Location', $location->name],
                ['Emission entries', (string) $entriesCreated],
                ['Portfolio total', number_format((float) $measurement->total_co2e, 0) . ' kg CO₂e'],
            ]
        );
        $this->command?->info('Test flow: sign in → Dashboard → Open workspace (or Clients → Open) → Quick Input / emissions.');
    }

    private function seedEmissionEntries(int $measurementId, int $userId, int $year): int
    {
        if (!Schema::hasTable('measurement_data')) {
            $this->setMeasurementTotalsDirect($measurementId);

            return 0;
        }

        $created = 0;
        $samples = [
            [
                'slug' => 'natural-gas',
                'quantity' => 1200,
                'unit' => 'litres',
                'fallback_co2e' => 3200,
                'scope' => 'Scope 1',
            ],
            [
                'slug' => 'electricity',
                'quantity' => 45000,
                'unit' => 'kWh',
                'fallback_co2e' => 19800,
                'scope' => 'Scope 2',
            ],
        ];

        foreach ($samples as $sample) {
            $source = EmissionSourceMaster::query()
                ->where('is_quick_input', true)
                ->where(function ($q) use ($sample) {
                    $q->where('quick_input_slug', $sample['slug'])
                        ->orWhere('name', 'like', '%' . str_replace('-', ' ', $sample['slug']) . '%');
                })
                ->first()
                ?? EmissionSourceMaster::query()->where('is_quick_input', true)->where('scope', $sample['scope'])->first();

            $factor = $source
                ? EmissionFactor::query()->where('emission_source_id', $source->id)->where('is_active', true)->first()
                : null;

            $co2e = $factor
                ? round((float) $sample['quantity'] * (float) $factor->factor_value, 2)
                : (float) $sample['fallback_co2e'];

            if ($co2e <= 0) {
                $co2e = (float) $sample['fallback_co2e'];
            }

            MeasurementData::create([
                'measurement_id' => $measurementId,
                'emission_source_id' => $source?->id,
                'field_name' => 'quick_input',
                'field_value' => (string) $sample['quantity'],
                'quantity' => $sample['quantity'],
                'unit' => $sample['unit'],
                'calculated_co2e' => $co2e,
                'scope' => $source?->scope ?? $sample['scope'],
                'emission_factor_id' => $factor?->id,
                'entry_date' => $year . '-06-01',
                'notes' => 'Seeded demo entry for consultant testing',
                'created_by' => $userId,
            ]);

            $created++;
        }

        if ($created === 0) {
            $this->setMeasurementTotalsDirect($measurementId);
        }

        return $created;
    }

    private function setMeasurementTotalsDirect(int $measurementId): void
    {
        $measurement = \App\Models\Measurement::find($measurementId);

        if (!$measurement) {
            return;
        }

        $measurement->update([
            'total_co2e' => 23000,
            'scope_1_co2e' => 3200,
            'scope_2_co2e' => 19800,
            'scope_3_co2e' => 0,
        ]);

        $this->command?->warn('No emission master data found — set measurement totals directly.');
    }
}
