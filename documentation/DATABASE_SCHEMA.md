# Database schema

76 tables, as of the 2026-09-03 baseline. The authoritative definition is
`database/schema/mysql-schema.sql`, dumped from the live database.

## How migrations work here

The 89 migrations that built the schema were squashed into that schema file on
2026-09-03, and the `migrations` table was emptied at the same time. They remain
in git history (commit `9f3f9aa` and earlier) if you ever need to read one.

`database/migrations/` now holds **one** file: `2026_09_03_000000_baseline_schema.php`.
It does nothing. It exists so the `migrations` table has a row marking the
baseline.

**From here on, every schema change is a new migration dated after the baseline.**
Never edit `mysql-schema.sql` to make a change — regenerate it (`php artisan
schema:dump`) only when squashing again, which should be rare.

### Why the squash was necessary

Seven migrations had been deleted from the repo while their rows stayed in the
`migrations` table. As a result 29 tables — including `emission_factors`,
`subscription_plans`, `user_company_roles` and `permissions` — had no creating
migration at all, and the schema could not be rebuilt from the repository.

## Three login tables

This is the single most common source of bugs in this codebase. There is no
one "user" table:

| Table | Guard | Who |
|---|---|---|
| `users` | `web` | Workspace accounts |
| `consultants` | `consultant` | Agency logins — **own email and password**, not a `users` row |
| `admins` | `admin` | Super admin, at `/admin/login` |

`consultants.agency_company_id` is `nullOnDelete`, so deleting an agency company
does **not** remove its consultant login. `OrganisationDeletionService` deletes
it explicitly; anything else touching deletion must do the same.

Password resets need `password_reset_tokens`, `admin_password_reset_tokens` and
`consultant_password_reset_tokens` — one per guard, wired in `config/auth.php`.

## Where emissions data actually lives

```
companies → locations → measurements → measurement_data
```

`measurements` hangs off `location_id`, **not** `company_id`. Use
`Company::measurements()` (a `hasManyThrough`) to count real activity.

`carbon_emissions` and `carbon_calculations` are **legacy and empty** — nothing
writes to them. Admin screens used to count `carbon_emissions`, which is why
every company showed "0 emission entries" and read as dormant. Fixed
2026-09-03. Do not add new reads of those two tables; they are kept only so
existing relations resolve.

## Tables that look unused but are not

Each of these is referenced only through its Eloquent model, so a grep for the
table name returns nothing. Do not drop them:

| Table | Used by |
|---|---|
| `usage_tracking` | `UsageTrackingService` |
| `feature_flags` | `FeatureFlagService` |
| `company_invitations` | `StaffManagementController` |
| `emission_factor_selection_rules` | Admin emission management UI |
| `admin_password_reset_tokens`, `consultant_password_reset_tokens` | `config/auth.php` |

Before dropping any table, search for its **model class**, not just its name.

## Removed on 2026-09-03

Eleven empty, unreferenced tables were dropped, and their models deleted:

- `document_uploads`, `document_templates`, `document_processing_logs`,
  `document_usage_trackings` — an abandoned feature, no model or route
- `facilities` and the sector tables `agriculture_data`, `energy_data`,
  `industrial_data`, `transport_data`, `waste_data` — superseded by
  `measurement_data`; models existed but nothing ever queried them
- `emission_factor_update_log` — no model, no references

## Table groups

**Identity & access** — `users`, `consultants`, `admins`, `companies`,
`user_company_roles`, `company_custom_roles`, `company_custom_role_permissions`,
`permissions`, `role_templates`, `role_template_permissions`,
`company_invitations`, `user_active_context`, `sessions`, the three
`*_password_reset_tokens`

**Emissions** — `measurements`, `measurement_data`, `measurement_audit_trail`,
`locations`, `location_emission_boundaries`, `emission_sources_master`,
`emission_factors`, `emission_source_form_fields`, `emission_gwp_values`,
`emission_unit_conversions`, `emission_factor_selection_rules`,
`emission_industry_labels`, `master_industry_categories`

**Reporting & disclosure** — `reports`, `company_disclosures`,
`company_reporting_settings`, `base_year_restatements`, `structural_changes`,
`material_sustainability_topics`, `climate_risks`, `climate_opportunities`,
`sustainability_risks`, `transition_actions`, `reduction_targets`,
`esg_kpi_snapshots`, `esg_sustainability_targets`, `stakeholder_engagements`,
`supply_chain_suppliers`, `hris_kpi_import_logs`

**Billing** — `subscription_plans`, `client_subscriptions`, `plan_feature_rows`,
`client_payment_transactions`, `client_billing_methods`, `invoices`,
`payment_gateways`, `subscription_coupons`, `subscription_coupon_redemptions`,
`usage_tracking`, `feature_flags`, `scope3_addons`,
`admin_package_assignments`

**Consultant / agency** — `consultant_subscriptions`,
`consultant_client_engagements`, `consultant_subscription_addons`,
`consultant_documents`, `consultant_intro_requests`,
`consultant_public_inquiries`, `consultant_orders`

**Site & infrastructure** — `site_pages`, `site_settings`, `email_templates`,
`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `migrations`
