# MenetZero — Project Overview

A working map of the codebase so anyone (you or a new collaborator) can get productive fast. This is a **carbon emissions tracking / ESG reporting SaaS** built on Laravel 12.

> Companion SQL dump from production lives at `silverwebbuzz_in_menetzero(5).sql` (root). It is gitignored — import it locally to populate master tables (emission sources, factors, GWP values, unit conversions, industry labels, etc.).

---

## 1. Tech Stack

| Layer | Choice |
|---|---|
| Framework | Laravel **12** (PHP ^8.2) |
| Frontend | Blade server-rendered + Tailwind/Vite (`resources/css`, `resources/js`) |
| Auth | Laravel Sessions + **Socialite** (Google OAuth) |
| Permissions | **spatie/laravel-permission** + custom `CompanyCustomRole` layer |
| Excel | `maatwebsite/excel` |
| PDF | `barryvdh/laravel-dompdf` |
| Syntax highlighting | `phiki/phiki` |
| DB | MySQL (see `silverwebbuzz_in_menetzero(5).sql`) |

Composer entry: [composer.json](../composer.json).

---

## 2. High-Level Domain

The platform lets a company measure, calculate and report **GHG emissions** across Scope 1, 2 and 3.

- **Super Admin** curates master data: emission sources, emission factors, GWP values (AR4/AR5/AR6), unit conversions, industry labels, selection rules, subscription plans, role templates.
- **Company (Client)** owners configure locations, pick emission boundaries, create **measurement periods** (fiscal year), and enter emission data either via the full **Measurements flow** or the faster **Quick Input** flow.
- Data is saved into `measurement_data` / `carbon_emissions`, CO₂e is calculated via `EmissionCalculationService`, and results feed the **Reports** module (Excel + PDF export).
- **Staff** members can be invited into a company with a custom role & granular module.action permissions.

---

## 3. Directory Map

```
menetzeroapp/
├── app/
│   ├── Console/                     artisan commands
│   ├── Exports/
│   │   └── ResultsBreakdownExport.php   Excel export for Reports
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/               Super-admin (AdminLogin, EmissionManagement, SuperAdmin)
│   │   │   ├── Auth/                Register, OAuth (Google), ForgotPassword
│   │   │   ├── Client/              Client-area SubscriptionController
│   │   │   ├── AccountSelectorController    workspace switcher
│   │   │   ├── CompanySetupController
│   │   │   ├── DashboardController
│   │   │   ├── EmissionBoundaryController
│   │   │   ├── HomeController
│   │   │   ├── InvitationController         staff invite acceptance
│   │   │   ├── LocationController
│   │   │   ├── MeasurementController
│   │   │   ├── ProfileController
│   │   │   ├── QuickInputController         largest controller (~1.2k lines)
│   │   │   ├── ReportController
│   │   │   ├── RoleManagementController
│   │   │   └── StaffManagementController
│   │   └── Middleware/
│   │       ├── CheckCompanyType      client vs. (legacy) partner type gate
│   │       ├── CheckPermission       module.action permission gate
│   │       ├── EnsureSuperAdmin      admin guard gate
│   │       └── SetActiveCompanyContext   resolves $user->getActiveCompany()
│   ├── Mail/                        WelcomeEmail, PasswordChangedEmail
│   ├── Models/                      ~40 Eloquent models (see §5)
│   ├── Providers/
│   └── Services/                    domain services (see §6)
├── bootstrap/app.php                middleware aliases registered here
├── config/                          app, auth, database, mail, queue, session, ...
├── database/
│   ├── factories/
│   ├── migrations/                  schema (see §7)
│   └── seeders/                     AdminSeeder, BasicDataSeeder, SimpleUaeSeeder, DatabaseSeeder
├── resources/
│   ├── css/  js/  images/
│   └── views/                       Blade UI (see §8)
├── routes/
│   ├── console.php
│   └── web.php                      ~400 lines, all HTTP routes
├── public/                          index.php, built assets
├── storage/                         logs, framework cache, app/public
├── documentation/                   (this folder) — gitignored
├── silverwebbuzz_in_menetzero(5).sql   latest prod DB dump (gitignored)
├── SYSTEM_DOCUMENTATION_COMPLETE.md    pre-existing Quick Input spec (gitignored)
├── DATABASE_SETUP_COMPLETE.sql         pre-existing DB setup script (gitignored)
├── composer.json / composer.lock
├── artisan
├── deploy.sh
└── .env / .htaccess / index.php
```

---

## 4. Authentication & Authorization

Two auth guards are configured in [config/auth.php](../config/auth.php):

| Guard | Provider | Model | Used for |
|---|---|---|---|
| `web` | `users` | `App\Models\User` | Client + staff login |
| `admin` | `admins` | `App\Models\Admin` | Super-admin portal at `/admin/*` |

> ⚠️ **Known inconsistency:** [routes/web.php](../routes/web.php) references a `partner` guard and `partner` middleware group (lines 61–74, 250–308), but `config/auth.php` only defines `web` + `admin`. There are `Partner\ExternalClientController` and `Partner\SubscriptionController` route references but those controller files do not exist under `app/Http/Controllers/`. Partner functionality is **legacy / not wired**. Either remove the partner routes or register a `partner` guard + create the controllers before enabling.

### Middleware aliases ([bootstrap/app.php](../bootstrap/app.php))

| Alias | Class |
|---|---|
| `setActiveCompany` | `SetActiveCompanyContext` |
| `checkCompanyType` | `CheckCompanyType` |
| `ensureSuperAdmin` | `EnsureSuperAdmin` |
| `permission` | `CheckPermission` |

### Permission model

- **Super admin** (`User::isAdmin()`, checks `role === 'admin'`): bypasses all permission checks.
- **Company owner** (`UserCompanyRole.company_custom_role_id` is `NULL` or `0`): bypasses all permission checks within that company.
- **Staff** (`UserCompanyRole.company_custom_role_id` > 0): permissions resolved through `CompanyCustomRole` → `CompanyCustomRolePermission` → `Permission` (module + action, e.g. `measurements.view`). Supports `module.*` wildcards.

### Multi-company access

A single user can **own one company** and **be staff in many**. `UserActiveContext` remembers the currently selected company, falling back to session storage if the table does not exist (see `User::switchToCompany()`). When a user has more than one accessible company, `AccountSelectorController` shows a workspace picker after login.

---

## 5. Models (app/Models)

Domain groups:

**Identity & org**
- `User`, `Admin`, `Company`, `UserCompanyAccess`, `UserCompanyRole`, `UserActiveContext`
- `CompanyInvitation` (staff invite tokens)
- `CompanyCustomRole`, `CompanyCustomRolePermission`, `Permission`, `RoleTemplate`, `RoleTemplatePermission`

**Emissions master data (admin-managed)**
- `EmissionSourceMaster` — Natural Gas, Fuel, Vehicle, Refrigerant, Electricity, …
- `EmissionFactor`, `EmissionFactorSelectionRule`
- `EmissionGwpValue` (AR4/AR5/AR6)
- `EmissionUnitConversion`
- `EmissionIndustryLabel` (industry-friendly display names)
- `EmissionSourceFormField` (dynamic form fields)
- `MasterIndustryCategory`

**Company operational data**
- `Location`, `Facility`, `LocationEmissionBoundary`
- `Measurement`, `MeasurementData`, `MeasurementAuditTrail`
- `CarbonEmission`, `CarbonCalculation`
- Typed data tables: `EnergyData`, `TransportData`, `IndustrialData`, `WasteData`, `AgricultureData`
- `Report`

**Billing**
- `SubscriptionPlan`, `ClientSubscription`, `ClientBillingMethod`, `PaymentTransaction`, `UsageTracking`, `FeatureFlag`

---

## 6. Services (app/Services)

| Service | Responsibility |
|---|---|
| `EmissionCalculationService` | Select the right emission factor (rules + conditions), apply unit conversion, multiply by GWP → CO₂e |
| `QuickInputFormBuilder` | Builds dynamic forms per emission source using `EmissionSourceFormField` |
| `MeasurementService` / `MeasurementPeriodService` | Create measurement periods, group by fiscal year, audit trail |
| `CompanyInvitationService` | Create + email invitation tokens for staff |
| `RoleManagementService` | Seeds custom roles from `RoleTemplate` and manages permissions |
| `SubscriptionService` | Plan lookup, upgrade/downgrade logic |
| `UsageTrackingService` | Records API / feature usage per company |
| `FeatureFlagService` | Company-level feature flags |

---

## 7. Database Migrations (database/migrations)

Migrations fall into three tranches (run in this order):

1. **Laravel defaults** — users, cache, jobs (`0001_01_01_00000*`).
2. **2025-01 batch** — emission sources, boundaries, measurements, audit trail.
3. **2025-09 → 2025-10 batch** — companies, carbon_emissions, carbon_calculations, facilities, typed data tables (energy, transport, industrial, waste, agriculture), reports, subscriptions, locations, UAE-specific company fields, user profile fields, business subcategory.

Also see `DATABASE_SETUP_COMPLETE.sql` (gitignored) for an all-in-one setup script and `silverwebbuzz_in_menetzero(5).sql` for the latest prod dump.

Seeders: `AdminSeeder` (creates super-admin), `BasicDataSeeder`, `SimpleUaeSeeder` (UAE-specific reference data), `DatabaseSeeder` (orchestrator).

---

## 8. Blade Views (resources/views)

```
views/
├── layouts/app.blade.php + layouts/partials/       main client layout
├── components/                                      buttons, alerts, inputs, cards, brand logo
├── auth/                                            login, register, forgot/reset password
├── invitations/                                     accept, setup-password, expired, invalid
├── dashboard/                                       client dashboard + no-company-access
├── account-selector.blade.php                       workspace picker
├── locations/                                       CRUD + head-office toggle
├── emission-boundaries/                             per-location boundary picker
├── measurements/                                    index + calculate-source + edit-source + show
├── quick-input/                                     index (list), show (form), view (detail)
├── emission-form/                                   stepper (step, success, sections/)
├── emissions/                                       shared emission-related partials
├── reports/                                         index + PDF template
├── roles/                                           role CRUD (combined staff view)
├── staff/                                           invitation-success
├── profile/                                         personal / company / password tabs
├── client/subscriptions/                            index, current-plan, upgrade, billing, payment-history
├── admin/                                           super-admin portal
│   ├── auth/                                        admin login
│   ├── dashboard.blade.php / statistics.blade.php
│   ├── companies/                                   index + show
│   ├── users/
│   ├── subscription-plans/
│   ├── role-templates/
│   └── emissions/                                   sources, factors, gwp-values, unit-conversions, industry-labels, selection-rules
├── emails/                                          welcome, password-changed
└── welcome.blade.php
```

---

## 9. Routes Cheat Sheet ([routes/web.php](../routes/web.php))

### Public
- `GET /` → `HomeController@index`
- `GET|POST /register`, `/login`, `/logout`
- `GET|POST /partner/register`, `/partner/login` *(legacy, see §4 warning)*
- `GET /auth/google`, `/auth/google/callback` (Socialite)
- `GET|POST /forgot-password`, `/reset-password/{token}`
- `GET|POST /invitations/accept/{token}` + setup-password flow

### Client area (`auth:web` + `setActiveCompany` + `checkCompanyType:client`)
- `/dashboard`
- `/profile` (personal / company / password)
- `/locations` (resource) + toggle-status + toggle-head-office + stepped creation
- `/locations/{location}/emission-boundaries`
- `/measurements` (resource, no create/store/edit) + submit + per-source calculate/edit/delete
- `/quick-input/entries`, `/quick-input/{scope}/{slug}`, plus AJAX endpoints under `/api/quick-input/*`
- `/reports` + `/reports/export/excel` + `/reports/export/pdf`
- `/roles` (resource) — role CRUD
- `/staff/*` — staff CRUD (most routes redirect to `/roles` as a combined view)
- `/subscriptions/*` — plans, upgrade, billing, payment history, billing methods CRUD

### Public AJAX helpers
- `GET /api/industries?sector_id=…`
- `GET /api/subcategories?industry_id=…`

### Admin portal (`ensureSuperAdmin`)
- `/admin/login`, `/admin/logout`
- `/admin/dashboard`, `/admin/statistics`
- `/admin/companies`, `/admin/users`
- `/admin/subscription-plans` (CRUD)
- `/admin/role-templates` (CRUD)
- `/admin/emissions/{sources,factors,gwp-values,unit-conversions,industry-labels,selection-rules}` (CRUD each)

### Partner area (legacy, referenced but not fully wired)
- `/partner/dashboard`, `/partner/profile`, `/partner/clients` (resource), `/partner/roles`, `/partner/staff`, `/partner/subscriptions`
- Controllers `Partner\ExternalClientController` and `Partner\SubscriptionController` are **missing** — these routes will 500 until the controllers + `partner` guard are created.

---

## 10. How the Main Flows Connect

**Onboarding**
1. `RegisterController` creates a `User` (+ optional `Company`).
2. Welcome email dispatched (`WelcomeEmail`, `Mail/welcome.blade.php`).
3. Dashboard prompts `CompanySetupController@store` if the user has no company yet.
4. After company setup → `Location` → `EmissionBoundary` → ready to enter data.

**Data entry → calculation**
1. User picks scope (1/2/3) and source (Natural Gas, Fuel, Vehicle, Refrigerant, Electricity, Heat/Steam/Cooling, Flights, Public Transport, Home Workers).
2. `QuickInputFormBuilder` reads `EmissionSourceFormField` to render a dynamic form.
3. On submit, `QuickInputController` (or `MeasurementController` for the full flow) calls `EmissionCalculationService`:
   - `selectEmissionFactor()` picks a factor from `EmissionFactor` using `EmissionFactorSelectionRule` + fuel/vehicle/unit/region conditions.
   - Unit converted via `EmissionUnitConversion`.
   - Multiplied by GWP values from `EmissionGwpValue` (AR4/AR5/AR6).
   - Result (CO₂e) persisted to `measurement_data` / `carbon_emissions` / `carbon_calculations`, with an `MeasurementAuditTrail` entry.

**Reporting**
- `ReportController` aggregates company-level emissions and renders:
  - `reports/index.blade.php` (web)
  - `reports/pdf.blade.php` via DomPDF
  - `ResultsBreakdownExport` via maatwebsite/excel

**Staff management**
- Owner invites staff → `CompanyInvitation` with token + email.
- Invitee accepts → creates `User` + `UserCompanyRole` (+ optional `CompanyCustomRole`).
- Permission checks flow through `CheckPermission` middleware and `User::hasPermission()`.

---

## 11. Local Setup (from scratch)

```bash
# 1. Install dependencies
composer install
npm install

# 2. Copy env + generate key
cp .env.example .env
php artisan key:generate

# 3. Edit .env — DB credentials, MAIL_*, GOOGLE_CLIENT_ID/SECRET

# 4a. Fresh schema via migrations + seeders
php artisan migrate --seed

# 4b. OR import the prod dump (gives you real master data)
mysql -u <user> -p <db_name> < "silverwebbuzz_in_menetzero(5).sql"

# 5. Build assets
npm run dev        # or: npm run build

# 6. Serve
php artisan serve
```

Default super-admin is seeded by `AdminSeeder` — check that file for credentials.

---

## 12. Conventions & Gotchas

- **Active company context is implicit**: most controllers call `Auth::user()->getActiveCompany()` rather than receiving the company as a parameter. If that returns `null`, the user is redirected to company setup.
- **Schema::hasTable guards** are sprinkled through `User` — the app is designed to degrade gracefully if `user_company_roles` or `user_active_context` are missing. When adding new code, prefer the same pattern until the schema stabilises.
- **Legacy fields on `users` table** (`role`, `custom_role_id`, `company_id`) are kept for backward compatibility — new logic should use `UserCompanyRole` instead.
- **Partner portal is half-finished** — don't assume those routes work (see §4 + §9).
- **Two huge files to pre-read** when touching emission logic: `QuickInputController` (~1.2k lines) and `EmissionCalculationService` (~350 lines).
- **`.gitignore` now excludes `*.sql` and `*.md`** — new docs live in this `documentation/` folder locally but are not pushed.

---

## 13. Reference Files — Quick Links

- Routes: [routes/web.php](../routes/web.php)
- Middleware registration: [bootstrap/app.php](../bootstrap/app.php)
- Auth config: [config/auth.php](../config/auth.php)
- Core controller: [app/Http/Controllers/QuickInputController.php](../app/Http/Controllers/QuickInputController.php)
- Core service: [app/Services/EmissionCalculationService.php](../app/Services/EmissionCalculationService.php)
- User model (auth/permissions): [app/Models/User.php](../app/Models/User.php)
- **Payment gateways (Stripe, Razorpay, Cashfree):** [PAYMENT_GATEWAYS.md](./PAYMENT_GATEWAYS.md)
- Pre-existing Quick Input spec: [SYSTEM_DOCUMENTATION_COMPLETE.md](../SYSTEM_DOCUMENTATION_COMPLETE.md)
- Latest DB dump: [silverwebbuzz_in_menetzero(5).sql](<../silverwebbuzz_in_menetzero(5).sql>)
