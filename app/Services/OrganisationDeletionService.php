<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Consultant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Permanent deletion of a client company or a consultant, from admin.
 *
 * Most of the work is already done by the schema: the tables holding company_id
 * declare cascadeOnDelete, so removing the company row removes its emissions
 * data, reports, disclosures, locations, roles and subscriptions. This class
 * exists for the handful of things the cascade does NOT cover, and for the
 * checks that must happen before anything is destroyed.
 *
 * Not covered by cascade, handled here:
 *   invoices                   -- company_id has an index but NO foreign key
 *                                 (see create_invoices_table), so rows would be
 *                                 silently orphaned rather than removed
 *   admin_package_assignments  -- company_id/consultant_id are raw
 *                                 unsignedBigInteger columns whose foreign keys
 *                                 are only added when the parent tables exist;
 *                                 cleaned explicitly so a re-created company
 *                                 cannot inherit a dead assignment by id reuse
 *   consultants                -- a SEPARATE auth table with its own email and
 *                                 password. agency_company_id is nullOnDelete,
 *                                 so deleting an agency company used to leave a
 *                                 fully working consultant login behind, still
 *                                 listed in the directory. See deleteAgencyProfile()
 *   companies.consultant_id    -- nullOnDelete, so deleting an agency would
 *                                 quietly orphan its managed clients; blocked
 *                                 instead, see assertAgencyHasNoClients()
 *   users                      -- users.company_id cascades, which would delete
 *                                 a user who also belongs to ANOTHER company
 *                                 through user_company_roles
 *   uploaded files             -- report PDFs, consultant documents and the
 *                                 company logo live on disk, not in the database,
 *                                 so no cascade can reach them
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
            'consultants_deleted' => 0,
        ];

        // Collected inside the transaction, deleted only after it commits: a
        // rolled-back transaction must not leave the database intact but the
        // files gone.
        $files = [];

        DB::transaction(function () use ($company, &$summary, &$files) {
            $files = $this->collectFiles($company);
            $summary = array_merge($summary, $this->deleteCompanyRows($company));
        });

        $this->deleteFiles($files);

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
            'consultants_deleted' => 1,
        ];

        $files = [];

        DB::transaction(function () use ($consultant, $agency, &$summary, &$files) {
            $files = array_merge(
                $this->collectConsultantFiles($consultant),
                $agency ? $this->collectFiles($agency) : []
            );

            $this->deleteConsultantRows($consultant);

            if ($agency) {
                $agencySummary = $this->deleteCompanyRows($agency);
                $summary['users_deleted'] = $agencySummary['users_deleted'];
                $summary['users_detached'] = $agencySummary['users_detached'];
                $summary['invoices_deleted'] = $agencySummary['invoices_deleted'];
                $summary['consultants_deleted'] += $agencySummary['consultants_deleted'];
            }
        });

        $this->deleteFiles($files);

        Log::warning('Consultant permanently deleted by admin', $summary + ['actor_id' => $actorId]);

        return $summary;
    }

    /**
     * The row-level work of removing a company. Assumes the caller has already
     * checked blockerFor() and opened a transaction.
     */
    protected function deleteCompanyRows(Company $company): array
    {
        $counts = [
            'users_deleted' => 0,
            'users_detached' => 0,
            'invoices_deleted' => 0,
            'consultants_deleted' => 0,
        ];

        // No foreign key on invoices.company_id, so these would be orphaned.
        if (DB::getSchemaBuilder()->hasTable('invoices')) {
            $counts['invoices_deleted'] = DB::table('invoices')
                ->where('company_id', $company->id)
                ->delete();
        }

        // Raw integer columns, so a cascade may not exist here either.
        if (DB::getSchemaBuilder()->hasTable('admin_package_assignments')) {
            DB::table('admin_package_assignments')
                ->where('company_id', $company->id)
                ->delete();
        }

        // The agency's own login. Deleted BEFORE the company row, because
        // agency_company_id is nullOnDelete -- once the company is gone the
        // link is null and the profile can no longer be found.
        $counts['consultants_deleted'] = $this->deleteAgencyProfile($company);

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

    /**
     * Remove the consultant profile(s) whose agency IS this company.
     *
     * consultants is a separate auth guard with its own email and password, so
     * a profile left behind is not a stray row: it is a working login into a
     * workspace that no longer exists, and a listing still visible in the
     * public directory.
     */
    protected function deleteAgencyProfile(Company $company): int
    {
        if (!DB::getSchemaBuilder()->hasTable('consultants')
            || !DB::getSchemaBuilder()->hasColumn('consultants', 'agency_company_id')) {
            return 0;
        }

        $deleted = 0;

        foreach (Consultant::where('agency_company_id', $company->id)->get() as $consultant) {
            $this->deleteConsultantRows($consultant);
            $deleted++;
        }

        return $deleted;
    }

    /**
     * Row-level removal of a consultant profile and the rows that do not
     * cascade from it.
     */
    protected function deleteConsultantRows(Consultant $consultant): void
    {
        if (DB::getSchemaBuilder()->hasTable('admin_package_assignments')) {
            DB::table('admin_package_assignments')
                ->where('consultant_id', $consultant->id)
                ->delete();
        }

        // consultant_documents, intro requests and public inquiries all
        // cascade from consultants.id.
        $consultant->delete();
    }

    /**
     * Paths of files on disk owned by this company. Read before deletion,
     * because after the cascade the rows naming them are gone.
     */
    protected function collectFiles(Company $company): array
    {
        $paths = [];

        // Logos are served from the public disk (see Company::logoUrl).
        if ($company->logo_path) {
            $paths[] = ['public', $company->logo_path];
        }

        if (DB::getSchemaBuilder()->hasTable('consultants')
            && DB::getSchemaBuilder()->hasColumn('consultants', 'agency_company_id')) {
            foreach (Consultant::where('agency_company_id', $company->id)->get() as $consultant) {
                $paths = array_merge($paths, $this->collectConsultantFiles($consultant));
            }
        }

        return $paths;
    }

    protected function collectConsultantFiles(Consultant $consultant): array
    {
        if (!DB::getSchemaBuilder()->hasTable('consultant_documents')) {
            return [];
        }

        // Trade licences and similar are deliberately NOT public
        // (see Consultant\DocumentController), so they live on the local disk.
        return DB::table('consultant_documents')
            ->where('consultant_id', $consultant->id)
            ->whereNotNull('file_path')
            ->pluck('file_path')
            ->map(fn ($path) => ['local', $path])
            ->all();
    }

    /**
     * Best effort: the database is already consistent at this point, so a file
     * that cannot be removed is logged rather than allowed to surface as a
     * failure on an operation that has actually succeeded.
     */
    protected function deleteFiles(array $paths): void
    {
        $seen = [];

        foreach ($paths as [$disk, $path]) {
            if (!$path || isset($seen[$disk . '|' . $path])) {
                continue;
            }

            $seen[$disk . '|' . $path] = true;

            try {
                Storage::disk($disk)->delete($path);
            } catch (\Throwable $e) {
                Log::warning('Could not delete file for deleted organisation', [
                    'disk' => $disk,
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
