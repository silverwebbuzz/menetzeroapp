<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Consultant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Permanent deletion of a client company or a consultant, from admin.
 *
 * Most of the work is already done by the schema: 28 of the 30 tables holding
 * company_id declare cascadeOnDelete, so removing the company row removes its
 * emissions data, reports, disclosures, locations, roles and subscriptions.
 * This class exists for the handful of things the cascade does NOT cover, and
 * for the checks that must happen before anything is destroyed.
 *
 * Not covered by cascade, handled here:
 *   invoices                  -- company_id has an index but NO foreign key
 *                                (see create_invoices_table), so rows would be
 *                                silently orphaned rather than removed
 *   companies.consultant_id   -- nullOnDelete, so deleting an agency would
 *                                quietly orphan its managed clients; blocked
 *                                instead, see assertAgencyHasNoClients()
 *   users                     -- users.company_id cascades, which would delete
 *                                a user who also belongs to ANOTHER company
 *                                through user_company_roles
 */
class OrganisationDeletionService
{
    /**
     * Why this company cannot be deleted, or null when it can.
     *
     * Returned as a message rather than thrown so the detail page can show the
     * reason and disable the button, instead of failing only on submit.
     */
    public function blockerFor(Company $company): ?string
    {
        $managed = Company::where('consultant_id', $company->id)->count();

        if ($managed > 0) {
            return "This agency still manages {$managed} client "
                . ($managed === 1 ? 'company' : 'companies')
                . '. Move or delete them first — deleting the agency would leave them with no consultant.';
        }

        return null;
    }

    /**
     * Permanently delete a company and everything belonging to it.
     */
    public function deleteCompany(Company $company, int $actorId): array
    {
        if ($blocker = $this->blockerFor($company)) {
            throw new RuntimeException($blocker);
        }

        $summary = [
            'company_id' => $company->id,
            'name' => $company->name,
            'type' => $company->company_type,
            'users_deleted' => 0,
            'users_detached' => 0,
            'invoices_deleted' => 0,
        ];

        DB::transaction(function () use ($company, &$summary) {
            $summary = array_merge($summary, $this->deleteCompanyRows($company));
        });

        Log::warning('Organisation permanently deleted by admin', $summary + ['actor_id' => $actorId]);

        return $summary;
    }

    /**
     * Permanently delete a consultant, and the agency company behind it.
     *
     * A consultant profile and its agency company are two rows describing one
     * organisation, so deleting only the profile would leave the company (and
     * every client workspace under it) behind.
     */
    public function deleteConsultant(Consultant $consultant, int $actorId): array
    {
        $agency = $consultant->agency_company_id
            ? Company::find($consultant->agency_company_id)
            : null;

        if ($agency && ($blocker = $this->blockerFor($agency))) {
            throw new RuntimeException($blocker);
        }

        $summary = [
            'consultant_id' => $consultant->id,
            'name' => $consultant->name,
            'agency_company_id' => $agency?->id,
            'users_deleted' => 0,
            'users_detached' => 0,
            'invoices_deleted' => 0,
        ];

        DB::transaction(function () use ($consultant, $agency, &$summary) {
            $consultant->delete();

            if ($agency) {
                $agencySummary = $this->deleteCompanyRows($agency);
                $summary['users_deleted'] = $agencySummary['users_deleted'];
                $summary['users_detached'] = $agencySummary['users_detached'];
                $summary['invoices_deleted'] = $agencySummary['invoices_deleted'];
            }
        });

        Log::warning('Consultant permanently deleted by admin', $summary + ['actor_id' => $actorId]);

        return $summary;
    }

    /**
     * The row-level work of removing a company. Assumes the caller has already
     * checked blockerFor() and opened a transaction.
     */
    protected function deleteCompanyRows(Company $company): array
    {
        $counts = ['users_deleted' => 0, 'users_detached' => 0, 'invoices_deleted' => 0];

        if (DB::getSchemaBuilder()->hasTable('invoices')) {
            $counts['invoices_deleted'] = DB::table('invoices')
                ->where('company_id', $company->id)
                ->delete();
        }

        foreach ($company->users as $user) {
            $otherMemberships = DB::table('user_company_roles')
                ->where('user_id', $user->id)
                ->where('company_id', '!=', $company->id)
                ->where('is_active', true)
                ->count();

            if ($otherMemberships > 0) {
                DB::table('users')->where('id', $user->id)->update(['company_id' => null]);
                $counts['users_detached']++;
            } else {
                $counts['users_deleted']++;
            }
        }

        $company->delete();

        return $counts;
    }
}
