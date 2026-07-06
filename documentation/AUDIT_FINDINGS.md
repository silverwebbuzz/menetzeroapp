# Code Audit — Likely Broken Things

Based on a read of the code + the prod SQL dump. Each finding lists file + line and a fix suggestion. Bugs are ranked by **blast radius × likelihood**.

---

## P0 — Will definitely break / causing visible bugs now

### 1. Schema drift — migrations don't match prod
**Evidence:** Prod has 52 tables. Repo has 25 migrations that create only ~28 tables between them. Prod's `migrations` table lists 31 migrations that ran — but **at least 4 of those migration files are missing from the repo**:

- `2025_09_27_134040_create_emission_factors_table` ❌ missing
- `2025_10_02_000200_update_emission_factors_schema` ❌ missing
- `2025_10_02_000300_create_emission_sources_table` ❌ missing
- `2025_10_02_000400_update_emission_factors_for_calculator` ❌ missing
- `2025_01_15_000005_create_measurement_data_table` ❌ missing
- `2025_01_15_000008_update_emission_factors_for_measurements` ❌ missing

And **~28 prod tables have no corresponding migration file at all**:

```
admin_password_reset_tokens, client_billing_methods, client_payment_transactions,
client_subscriptions, company_custom_roles, company_custom_role_permissions,
company_invitations, document_processing_logs, document_templates,
document_uploads, document_usage_trackings, emission_factor_selection_rules,
emission_factors, emission_gwp_values, emission_industry_labels,
emission_source_form_fields, emission_unit_conversions, feature_flags,
master_industry_categories, measurement_data, permissions,
role_template_permissions, role_templates, subscription_plans,
usage_tracking, user_active_context, user_company_roles
```

**Impact:** `php artisan migrate` on a fresh database creates a broken schema. Any new developer can't set up locally without importing the SQL dump. CI / staging environments that build from scratch don't match prod.

**Fix:** Regenerate migrations from the prod schema. Two options:
- (a) Use `php artisan schema:dump` against a DB imported from the SQL to create a single squashed schema file, then delete the old migrations.
- (b) Hand-write migrations for each missing table to match prod column-for-column.

Option (a) is faster and safer. Option (b) preserves history but is a multi-day task.

---

### 2. `subscriptions` migration creates wrong table
**File:** [database/migrations/2025_10_02_000190_create_subscriptions_table.php](../database/migrations/2025_10_02_000190_create_subscriptions_table.php)

Creates a `subscriptions` table with schema `(id, company_id, plan_type enum, status, stripe_customer_id, started_at, expires_at)`.

Prod has **no `subscriptions` table** — it uses `client_subscriptions` with a completely different schema (see SQL dump line 226). The `ClientSubscription` model references `client_subscriptions`.

**Impact:** Fresh migration creates an orphaned table. Any `ClientSubscription::query()` call will fail with "table not found" because `client_subscriptions` isn't in any migration.

**Fix:** Delete this migration. Create proper migration for `client_subscriptions`, `subscription_plans`, `client_billing_methods`, `client_payment_transactions` matching the prod schema.

---

### 3. `requirePermission()` signature misuse — silent auth bypass risk
**Signature** ([Controller.php:19](../app/Http/Controllers/Controller.php#L19)):
```php
requirePermission($permissionOrModule, $action = null, $alternativePermissions = [])
```

**Broken callers:**

| File:Line | Call | Problem |
|---|---|---|
| [LocationController.php:62](../app/Http/Controllers/LocationController.php#L62) | `$this->requirePermission('locations.*', ['manage_locations']);` | 2nd arg is an array (expected `string|null`) → treated as `$action`, `hasModulePermission('locations.*', ['...'])` called, silently fails match |
| [LocationController.php:76](../app/Http/Controllers/LocationController.php#L76) | Same pattern | Same problem |
| [MeasurementController.php:598](../app/Http/Controllers/MeasurementController.php#L598) | `$this->requirePermission('measurements.delete', ['measurements.edit', 'measurements.*', 'manage_measurements']);` | Same pattern — 2nd arg is the alternative-permissions array, not an action |

**Impact:** These three permission checks never work as intended. Currently they pass for company admins (who bypass at the top of `requirePermission`) and fail for staff — so staff with `manage_locations` can't create locations even when they should. For delete, a staff with `measurements.edit` but not `measurements.delete` is supposed to pass thanks to alternatives, but won't.

**Fix:** Add `null` as the 2nd argument:
```php
$this->requirePermission('locations.*', null, ['manage_locations']);
$this->requirePermission('measurements.delete', null, ['measurements.edit', 'measurements.*', 'manage_measurements']);
```

---

### 4. `measurements.create` permission checked but doesn't exist
**File:** [MeasurementController.php:372, 398](../app/Http/Controllers/MeasurementController.php#L372)

```php
$this->requirePermission('measurements.create', null, ['measurements.edit', 'measurements.*', 'manage_measurements']);
```

The prod `permissions` table only has `measurements.view/add/edit/delete` — **no `create`** (standard CRUD uses `add`, not `create`).

**Impact:** Staff users fall through to the alternative checks. It mostly works via the alternatives but the intent is unclear and non-admin/non-*manage_measurements* staff fail unexpectedly.

**Fix:** Change `measurements.create` → `measurements.add` in both lines.

---

### 5. `partner` guard / middleware / controllers missing (already commented out)
Already handled in the cleanup commit — routes are commented out. Keep in mind:

- [config/auth.php](../config/auth.php) has no `partner` guard
- `App\Http\Controllers\Partner\*` directory doesn't exist
- [CheckCompanyType.php:50](../app/Http/Middleware/CheckCompanyType.php#L50) only supports `'client'` — the `'partner'` branch is missing

Decision needed: **remove entirely** (cleanest) or **build out** the partner portal.

---

## P1 — Correctness / maintenance bugs

### 6. `Company::isClient()` reads an un-fillable column
**File:** [Company.php:153](../app/Models/Company.php#L153)

```php
return $this->company_type === 'client' || $this->company_type === null;
```

Prod schema has `company_type enum('client','partner')` — but no migration adds this column, and `$fillable` on the Company model (lines 13-19) doesn't include it. Mass-assignment during company setup/update **silently drops `company_type`**.

Also `is_direct_client` exists in prod but isn't in fillable.

**Impact:** On a fresh DB created via migrations, `company_type` column doesn't exist at all → `isClient()` always returns `true` (ok-ish). On prod, any code that tries to set `company_type` via `create()`/`update()` fails silently.

**Fix:** Add a migration for `company_type` + `is_direct_client` columns and add both to `$fillable` in Company model. Or, since the app is client-only now, just delete `isClient()` everywhere and remove the column.

---

### 7. `User::role` enum with dead `'company_user'` value
**File:** [User.php:617](../app/Models/User.php#L617) + prod users table
```php
public function isAdmin() { return $this->role === 'admin'; }
```

Prod schema: `role enum('admin','company_admin','company_user')`. But the comment in User.php says "all roles in user_company_roles". So `company_user` is a dead enum value — staff invitees are created with `company_admin`? Actually looking at [InvitationController](../app/Http/Controllers/InvitationController.php), staff get role `'company_user'` in the users table plus a row in `user_company_roles`.

**Impact:** Low — the role column is redundant with user_company_roles. But the duplication means a staff user might show up as `company_user` in one place and owner-of-company in another if their `user_company_roles` flag them as owner.

**Fix:** Long-term: drop the `role` column. Short-term: leave it but document that `user_company_roles` is the source of truth.

---

### 8. `Schema::hasTable()` guards mask real errors
**File:** [User.php](../app/Models/User.php) — lines 324, 399, 549, 592

```php
if (\Illuminate\Support\Facades\Schema::hasTable('user_company_roles')) { ... }
```

Sprinkled throughout the User model. These were probably added because `user_company_roles` didn't always exist. On prod it does. **Every `Auth::user()` call now costs a `SHOW TABLES LIKE ...` query unless result is cached**.

**Impact:** Performance drag on every request. Also masks genuine schema drift — if the table gets dropped or renamed in prod, the app silently degrades to an incorrect `company_id`-based fallback instead of erroring.

**Fix:** Remove the `Schema::hasTable` guards. Rely on the migration existing.

---

### 9. `RoleManagementController.php:168` uses mismatched permission name
**File:** [RoleManagementController.php:168](../app/Http/Controllers/RoleManagementController.php#L168)
```php
$this->requirePermission('manage_settings');
```

There's no `manage_settings` permission in the prod `permissions` table — actual permissions use `settings.view/add/edit/delete` or `settings_view` etc.

**Impact:** Falls through to `abort(403)` for any non-admin.

**Fix:** Change to `'settings.edit'` (or whatever action fits what that line guards).

---

### 10. `CheckCompanyType` only gates `'client'` type
**File:** [CheckCompanyType.php:50](../app/Http/Middleware/CheckCompanyType.php#L50)

```php
if ($type === 'client' && !$company->isClient()) {
    abort(403, 'This route is for clients only');
}
```

If you ever call `checkCompanyType:partner` or `checkCompanyType:anything_else`, the middleware silently lets the request through.

**Impact:** Dead code today (partner routes are commented out). If re-enabled, misuse.

**Fix:** Either remove the `$type` parameter and hardcode `'client'`, or add explicit `elseif ($type === 'partner') ...`.

---

### 11. `Measurement` audit trail missing in audit table on insert
Routes POST `/measurements/{measurement}/submit` → `MeasurementController@submit`, but I didn't see a matching write to `measurement_audit_trail` in most handlers. Given the table exists and the model `MeasurementAuditTrail` exists, audit entries are likely not being written on some flows. Worth verifying.

**Fix:** Grep `MeasurementAuditTrail::create` usage, map which mutations are audited vs not.

---

### 12. `ForgotPasswordController` leaks reset links in SMTP-failure fallback
**File:** [ForgotPasswordController.php:37-60](../app/Http/Controllers/Auth/ForgotPasswordController.php#L37)

When SMTP fails, the code falls back to **rendering the password reset URL on-screen**. This is a dev convenience but **exposes password-reset links to anyone who can trigger SMTP failures** (e.g., via malformed MX records if MX is checked during send, or by DoS on the mail host).

**Impact:** In prod this is a credential-takeover pathway. Someone can request reset for victim@example.com, DoS mail, and read the reset link in the response.

**Fix:** Remove the `if (strpos($e->getMessage(), 'SMTP') !== false ...)` block. Let SMTP failures surface as a generic error message.

---

### 13. OAuth login auto-creates user with blank password, no email verification
**File:** [OAuthController.php](../app/Http/Controllers/Auth/OAuthController.php) (based on line 41/62/93 being Auth::login calls)

Google-auth'd users don't verify their Google email matches a `@yourcompany.com` domain or any allowlist, and the first Google-login creates a user with `company_admin` role by default (same as registration).

**Impact:** Anyone with a Google account can register themselves as a company_admin. That's probably intentional for SaaS signup, but confirm this is what you want.

---

## P2 — Quality / cleanup

### 14. Legacy `role`, `custom_role_id`, `company_id` columns on `users`
Comments say "kept for backward compatibility, but not used". If truly unused, drop them. If used as fallbacks, document where.

### 15. Root files `DATABASE_SETUP_COMPLETE.sql`, `SYSTEM_DOCUMENTATION_COMPLETE.md`
Already archived to `documentation/archive/`.

### 16. `.DS_Store` files
Already deleted.

### 17. Huge `QuickInputController` (1194 lines)
Single file handles list + form rendering + storage + AJAX for 7+ emission source types. Fine as-is, but factoring per-source builders would help testability.

### 18. View `dashboard/no-company-access.blade.php` exists but dashboard redirects through company setup flow
Verify this view is still reachable or remove it.

---

## Suggested Fix Order

1. **Fix #3** (permission signature misuse) — 1 minute, closes real permission gaps.
2. **Fix #4** (measurements.create → .add) — 1 minute, same file.
3. **Fix #9** (manage_settings → settings.edit) — 1 minute.
4. **Fix #12** (remove SMTP-fallback reset-link leak) — 5 minutes, security.
5. **Decide on #5** — remove partner routes entirely, or scope the work to build it.
6. **Fix #6** (company_type fillable + migration) — 30 minutes.
7. **Fix #2** (subscriptions migration wrong) — 1–2 hours, needs client_subscriptions schema.
8. **Fix #1** (schema drift) — 1 day via `schema:dump`, 2–3 days writing migrations by hand.
9. **Fix #8** (remove Schema::hasTable guards) — 30 minutes, but depends on #1 being done.
10. **Fix #10, #11, #13, #14** — cleanup as you touch the files.
