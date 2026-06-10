<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Live DB upgrade: rename partner_* schema (already migrated via p11/p12) to consultant_*.
 *
 * Safe to run when partner_subscriptions exists and consultant_subscriptions does not.
 * Skips automatically on fresh installs that never had partner tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('partner_subscriptions') || Schema::hasTable('consultant_subscriptions')) {
            return;
        }

        $this->renameCompaniesColumns();
        $this->renameConsultantsColumn();
        $this->renameSubscriptionTables();
        $this->migratePaymentTransactionTypes();
    }

    public function down(): void
    {
        // Irreversible on production — partner naming was retired.
    }

    private function renameCompaniesColumns(): void
    {
        if (!Schema::hasTable('companies')) {
            return;
        }

        if (Schema::hasColumn('companies', 'company_type')) {
            // ENUM('client','partner') must be widened before 'consultant' can be stored.
            DB::statement(
                "ALTER TABLE `companies` MODIFY `company_type` VARCHAR(20) NOT NULL DEFAULT 'client'"
            );
            DB::statement("UPDATE companies SET company_type = 'consultant' WHERE company_type = 'partner'");
        }

        if (!Schema::hasColumn('companies', 'partner_id')) {
            return;
        }

        $this->dropForeignKeyIfExists('companies', 'companies_partner_id_foreign');

        DB::statement(
            'ALTER TABLE `companies` CHANGE `partner_id` `consultant_id` BIGINT UNSIGNED NULL DEFAULT NULL'
        );

        if (!$this->indexExists('companies', 'companies_consultant_id_index')
            && !$this->indexExists('companies', 'companies_partner_id_index')) {
            DB::statement('ALTER TABLE `companies` ADD INDEX `companies_consultant_id_index` (`consultant_id`)');
        } elseif ($this->indexExists('companies', 'companies_partner_id_index')) {
            DB::statement('ALTER TABLE `companies` RENAME INDEX `companies_partner_id_index` TO `companies_consultant_id_index`');
        }

        DB::statement(
            'ALTER TABLE `companies` ADD CONSTRAINT `companies_consultant_id_foreign` '
            . 'FOREIGN KEY (`consultant_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL'
        );
    }

    private function renameConsultantsColumn(): void
    {
        if (!Schema::hasTable('consultants') || !Schema::hasColumn('consultants', 'partner_company_id')) {
            return;
        }

        $this->dropForeignKeyIfExists('consultants', 'consultants_partner_company_id_foreign');

        DB::statement(
            'ALTER TABLE `consultants` CHANGE `partner_company_id` `agency_company_id` BIGINT UNSIGNED NULL DEFAULT NULL'
        );

        DB::statement(
            'ALTER TABLE `consultants` ADD CONSTRAINT `consultants_agency_company_id_foreign` '
            . 'FOREIGN KEY (`agency_company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL'
        );
    }

    private function renameSubscriptionTables(): void
    {
        $this->dropForeignKeyIfExists('partner_subscription_addons', 'partner_subscription_addons_partner_subscription_id_foreign');
        $this->dropForeignKeyIfExists('partner_subscription_addons', 'partner_subscription_addons_managed_company_id_foreign');
        $this->dropForeignKeyIfExists('partner_subscription_addons', 'partner_subscription_addons_payment_transaction_id_foreign');

        $this->dropForeignKeyIfExists('partner_client_engagements', 'partner_client_engagements_partner_subscription_id_foreign');
        $this->dropForeignKeyIfExists('partner_client_engagements', 'partner_client_engagements_partner_company_id_foreign');
        $this->dropForeignKeyIfExists('partner_client_engagements', 'partner_client_engagements_managed_company_id_foreign');
        $this->dropForeignKeyIfExists('partner_client_engagements', 'partner_client_engagements_previous_engagement_id_foreign');

        $this->dropForeignKeyIfExists('partner_subscriptions', 'partner_subscriptions_partner_company_id_foreign');
        $this->dropForeignKeyIfExists('partner_subscriptions', 'partner_subscriptions_subscription_plan_id_foreign');
        $this->dropForeignKeyIfExists('partner_subscriptions', 'partner_subscriptions_payment_transaction_id_foreign');

        DB::statement(
            'ALTER TABLE `partner_subscriptions` CHANGE `partner_company_id` `consultant_company_id` BIGINT UNSIGNED NOT NULL'
        );
        DB::statement('RENAME TABLE `partner_subscriptions` TO `consultant_subscriptions`');

        DB::statement(
            'ALTER TABLE `partner_client_engagements` CHANGE `partner_company_id` `consultant_company_id` BIGINT UNSIGNED NOT NULL'
        );
        DB::statement(
            'ALTER TABLE `partner_client_engagements` CHANGE `partner_subscription_id` `consultant_subscription_id` BIGINT UNSIGNED NOT NULL'
        );
        DB::statement('RENAME TABLE `partner_client_engagements` TO `consultant_client_engagements`');

        DB::statement(
            'ALTER TABLE `partner_subscription_addons` CHANGE `partner_subscription_id` `consultant_subscription_id` BIGINT UNSIGNED NOT NULL'
        );
        DB::statement('RENAME TABLE `partner_subscription_addons` TO `consultant_subscription_addons`');

        DB::statement(
            'ALTER TABLE `consultant_subscriptions` ADD CONSTRAINT `cs_consultant_company_id_foreign` '
            . 'FOREIGN KEY (`consultant_company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE'
        );
        DB::statement(
            'ALTER TABLE `consultant_subscriptions` ADD CONSTRAINT `cs_subscription_plan_id_foreign` '
            . 'FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`)'
        );
        DB::statement(
            'ALTER TABLE `consultant_subscriptions` ADD CONSTRAINT `cs_payment_transaction_id_foreign` '
            . 'FOREIGN KEY (`payment_transaction_id`) REFERENCES `client_payment_transactions` (`id`) ON DELETE SET NULL'
        );

        DB::statement(
            'ALTER TABLE `consultant_client_engagements` ADD CONSTRAINT `cce_consultant_company_id_foreign` '
            . 'FOREIGN KEY (`consultant_company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE'
        );
        DB::statement(
            'ALTER TABLE `consultant_client_engagements` ADD CONSTRAINT `cce_managed_company_id_foreign` '
            . 'FOREIGN KEY (`managed_company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE'
        );
        DB::statement(
            'ALTER TABLE `consultant_client_engagements` ADD CONSTRAINT `cce_consultant_subscription_id_foreign` '
            . 'FOREIGN KEY (`consultant_subscription_id`) REFERENCES `consultant_subscriptions` (`id`) ON DELETE CASCADE'
        );
        DB::statement(
            'ALTER TABLE `consultant_client_engagements` ADD CONSTRAINT `cce_previous_engagement_id_foreign` '
            . 'FOREIGN KEY (`previous_engagement_id`) REFERENCES `consultant_client_engagements` (`id`) ON DELETE SET NULL'
        );

        DB::statement(
            'ALTER TABLE `consultant_subscription_addons` ADD CONSTRAINT `csa_consultant_subscription_id_foreign` '
            . 'FOREIGN KEY (`consultant_subscription_id`) REFERENCES `consultant_subscriptions` (`id`) ON DELETE CASCADE'
        );
        DB::statement(
            'ALTER TABLE `consultant_subscription_addons` ADD CONSTRAINT `csa_managed_company_id_foreign` '
            . 'FOREIGN KEY (`managed_company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL'
        );
        DB::statement(
            'ALTER TABLE `consultant_subscription_addons` ADD CONSTRAINT `csa_payment_transaction_id_foreign` '
            . 'FOREIGN KEY (`payment_transaction_id`) REFERENCES `client_payment_transactions` (`id`) ON DELETE SET NULL'
        );

        $this->renameIndexIfExists(
            'consultant_subscriptions',
            'partner_subscriptions_partner_company_id_status_index',
            'cs_consultant_status_idx'
        );
        $this->renameIndexIfExists(
            'consultant_subscriptions',
            'partner_subscriptions_partner_company_id_contract_year_index',
            'cs_consultant_year_idx'
        );
        $this->renameIndexIfExists(
            'consultant_client_engagements',
            'partner_client_engagements_partner_company_id_status_index',
            'cce_consultant_status_idx'
        );

        if (!$this->indexExists('consultant_client_engagements', 'cce_managed_consultant_idx')) {
            DB::statement(
                'ALTER TABLE `consultant_client_engagements` '
                . 'ADD INDEX `cce_managed_consultant_idx` (`managed_company_id`, `consultant_company_id`)'
            );
        }

        if (!$this->indexExists('consultant_client_engagements', 'cce_managed_status_idx')) {
            DB::statement(
                'ALTER TABLE `consultant_client_engagements` '
                . 'ADD INDEX `cce_managed_status_idx` (`managed_company_id`, `status`)'
            );
        }

        if (!$this->indexExists('consultant_subscription_addons', 'csa_unlock_lookup_idx')) {
            DB::statement(
                'ALTER TABLE `consultant_subscription_addons` '
                . 'ADD INDEX `csa_unlock_lookup_idx` '
                . '(`consultant_subscription_id`, `addon_type`, `managed_company_id`, `reporting_year`)'
            );
        }
    }

    private function migratePaymentTransactionTypes(): void
    {
        if (!Schema::hasTable('client_payment_transactions')) {
            return;
        }

        $map = [
            'partner_pack' => 'consultant_agency_pack',
            'partner_extra_slot' => 'consultant_agency_extra_slot',
            'partner_year_unlock' => 'consultant_agency_year_unlock',
            'partner_renewal' => 'consultant_agency_renewal',
        ];

        foreach ($map as $from => $to) {
            DB::table('client_payment_transactions')
                ->where('transaction_type', $from)
                ->update(['transaction_type' => $to]);
        }

        DB::table('client_payment_transactions')
            ->where('metadata', 'like', '%partner_%')
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $metadata = is_string($row->metadata) ? json_decode($row->metadata, true) : null;

                    if (!is_array($metadata)) {
                        continue;
                    }

                    $encoded = json_encode($this->replacePartnerKeys($metadata));

                    if ($encoded !== false && $encoded !== $row->metadata) {
                        DB::table('client_payment_transactions')
                            ->where('id', $row->id)
                            ->update(['metadata' => $encoded]);
                    }
                }
            });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function replacePartnerKeys(array $data): array
    {
        $out = [];

        foreach ($data as $key => $value) {
            $newKey = match ($key) {
                'partner_subscription_id' => 'consultant_subscription_id',
                'partner_addon_id' => 'consultant_addon_id',
                default => $key,
            };

            if (is_array($value)) {
                $out[$newKey] = $this->replacePartnerKeys($value);
                continue;
            }

            if (is_string($value)) {
                $out[$newKey] = str_replace(
                    [
                        'partner_pack',
                        'partner_extra_slot',
                        'partner_year_unlock',
                        'partner_renewal',
                        'partner_5',
                        'partner_10',
                        'partner_25',
                        'partner_50',
                        'partner_slots',
                        'partner_slot_count',
                        'partner_agency',
                    ],
                    [
                        'consultant_agency_pack',
                        'consultant_agency_extra_slot',
                        'consultant_agency_year_unlock',
                        'consultant_agency_renewal',
                        'consultant_5',
                        'consultant_10',
                        'consultant_25',
                        'consultant_50',
                        'consultant_slots',
                        'consultant_slot_count',
                        'consultant_agency',
                    ],
                    $value
                );
                continue;
            }

            $out[$newKey] = $value;
        }

        return $out;
    }

    private function dropForeignKeyIfExists(string $table, string $constraint): void
    {
        $exists = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS '
            . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$table, $constraint, 'FOREIGN KEY']
        );

        if ($exists) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return (bool) DB::selectOne(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS '
            . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$table, $index]
        );
    }

    private function renameIndexIfExists(string $table, string $from, string $to): void
    {
        if ($this->indexExists($table, $from) && !$this->indexExists($table, $to)) {
            DB::statement("ALTER TABLE `{$table}` RENAME INDEX `{$from}` TO `{$to}`");
        }
    }
};
