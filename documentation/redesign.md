# MENetZero 2.0 — Master Implementation Plan

**Status:** Planning — no code written
**Created:** 2026-08-25
**Design source:** `Menetzero-Redesign/` (67 screens, 6 `.dc.html` files)
**Target:** Progressive, additive migration to the MENetZero 2.0 experience

---

## 0. Governing Principles

These constrain every decision in this document. If a proposed change violates one, the change is wrong — not the principle.

| # | Principle | Practical meaning |
|---|---|---|
| P1 | **Additive only** | New routes, views, and assets are *added*. Existing ones are not edited or deleted until switch-over. |
| P2 | **Nothing existing breaks** | All 371 current routes keep working, unchanged, throughout every phase. |
| P3 | **No unnecessary DB/service rewrites** | New screens consume existing services. New methods may be *added*; existing signatures are never changed. |
| P4 | **Session-first testing** | Theme selection resolves from session before anything else, so testing can never leak to a client. |
| P5 | **Final production URLs** | No `/v2` prefix. New URLs are the URLs we keep — verified collision-free first. |
| P6 | **Fallback for unfinished sections** | An unmigrated screen renders existing content inside the new shell. The new theme is never broken, only incomplete. |
| P7 | **Every phase independently testable** | Each sub-phase ships and is verifiable on its own, behind the theme flag. |

### The one rule that protects everything

> **Old theme code is read-only until the final switch.**

Anything that must change in a shared file (a service, a migration, a config) must be **purely additive** — a new method, a new nullable column, a new config key. Never a modified signature, never a dropped column, never a changed default.

---

## 1. Existing Route Inventory

**Total: 371 route registrations** across `routes/web.php` (716 lines). No `routes/api.php` — API endpoints live inside `web.php`.

### 1.1 Inventory by group

| # | Group | URL prefix | Name prefix | Middleware | Approx. routes |
|---|---|---|---|---|---|
| A | Public marketing | `/` | — | none | 10 |
| B | Public consultant directory | `/consultant-list` | `consultant-list.` | `throttle` on POST | 3 |
| C | Consultant portal (auth) | `/consultant` | `consultant.` | none (public subset) | 11 |
| D | Consultant portal (protected) | `/consultant` | `consultant.` | `ensureConsultant`, `syncConsultantAgencySession` | 16 |
| E | Consultant agency hub | `/consultant` | `consultant.` | `syncConsultantAgencySession`, `ensureConsultantAgency`, `auth:web`, `setActiveCompany`, `checkCompanyType:consultant` | 30 |
| F | Client auth | `/` | — | none | 12 |
| G | OAuth | `/auth/google`, `/consultant/auth/google` | `auth.google.` | none | 3 |
| H | Password reset | `/forgot-password`, `/reset-password` | `password.` | none | 5 |
| I | Invitations | `/invitations` | `invitations.` | none (public, token-gated) | 4 |
| J | Account selector | `/account` | `account.` | `auth:web` | 2 |
| K | **Company portal (main)** | `/` | `client.`, various | `auth:web`, `setActiveCompany`, `ensureConsultantManagedWorkspace`, `checkCompanyType:client`, `restrictManagedClientWorkspace`, `ensureOnboardingComplete` | ~150 |
| L | Payment webhooks | `/webhooks/payments` | `webhooks.payments.` | CSRF-exempt, signature-verified | 3 |
| M | Public API helpers | `/api/industries`, `/api/subcategories` | — | none | 2 |
| N | Admin auth | `/admin` | `admin.` | none | 3 |
| O | Admin portal | `/admin` | `admin.` | `ensureSuperAdmin` | ~100 |

### 1.2 Group K expanded — the company portal (the redesign target)

This is where the IA change lands. Sub-groups, all inside the six-middleware stack:

| Sub-group | URL | Name | Notes |
|---|---|---|---|
| Dashboard | `/dashboard` | `client.dashboard` | `DashboardController@index` |
| Help | `/help` | `client.help` | `PortalGuideController@company` |
| Zero AI | `/zero-ai`, `/zero-ai/ask` | `client.zero-ai*` | throttled 60/1 |
| Support | `/support` | `client.support*` | throttled 10/1 |
| Profile | `/profile`, `/profile/personal`, `/profile/company`, `/profile/password` | `client.profile`, `profile.update.*` | |
| Locations | `/locations` (resource + 3 extra) | `locations.*` | **`Route::resource`** — see collision rules |
| Emission boundaries | `/locations/{location}/emission-boundaries` | `emission-boundaries.*` | nested under locations |
| **Measurements (legacy)** | `/measurements`, `/measurements/{path}` | — | **redirect only** — `{path}` is `.*` wildcard |
| Quick Input | `/quick-input/*` | `quick-input.*` | 17 routes, ends in `/{scope}/{slug}` wildcard |
| Quick Input API | `/api/quick-input/*` | `api.quick-input.*` | 8 AJAX routes |
| Settings — reporting | `/settings/reporting` | `settings.reporting*` | **only `/settings/*` route that exists** |
| **Disclosures** | `/disclosures/*` | `disclosures.*` | ~90 routes + `disclosureAccess` middleware |
| Reports | `/reports/*` | `reports.*` | 5 routes |
| Roles | `/roles` (resource, no `show`) | `roles.*` | |
| Staff | `/staff/*` | `staff.*` | 7 routes, two are redirects to roles |
| Subscriptions | `/subscriptions/*` | `subscriptions.*` | 18 routes + `restrictManagedClientBilling` |
| Client consultants | `/consultants/*` | `client.consultants.*` | 9 routes, ends in `/{consultant}` wildcard |

### 1.3 Disclosures expanded — where E/S/G lives today

`/disclosures/*` (prefix `disclosures.`, middleware `disclosureAccess`):

| Sub-prefix | URL | Purpose | Pillar |
|---|---|---|---|
| — | `/disclosures` | Hub | all |
| `ifrs-s2` | `/disclosures/ifrs-s2/*` | Climate: sections, climate-risks, opportunities, targets, report | **E** |
| `ifrs-s1` | `/disclosures/ifrs-s1/*` | General: sections, material-topics, sustainability-risks, report | **all** |
| `gri` | `/disclosures/gri/*` | GRI: sections, material-topics, report, 3 content-index CSVs | **all** |
| — | `/disclosures/esg-dashboard` | **Existing ESG dashboard** | **E/S/G** |
| — | `/disclosures/esg-depth` | ESG depth overview | all |
| `stakeholders` | `/disclosures/stakeholders/*` | Stakeholder engagement | **S** |
| `materiality-matrix` | `/disclosures/materiality-matrix/*` | Materiality | all |
| `supply-chain` | `/disclosures/supply-chain/*` | Suppliers | **S** |
| `esg-targets` | `/disclosures/esg-targets/*` | Sustainability targets | all |
| `esg-scorecard` | `/disclosures/esg-scorecard/*` | Scorecard + 6 import/export routes | **E/S/G** |
| `sasb` | `/disclosures/sasb/*` | SASB index | all |
| `uae-esg-report` | `/disclosures/uae-esg-report/*` | UAE ESG unified report + assurance | all |

**Critical finding:** E/S/G is already a first-class dimension in the backend — `EsgDashboardService`, `config/esg_scorecard.php`, `config/esg_report.php`, `EsgDepthController`, `EsgScorecardController` all tag by pillar. The redesign **promotes an existing backend concept to top-level navigation**. It does not invent it.

This is the single most important fact in this document: the E/S/G dashboards are largely a **presentation and routing change over services that already compute pillar-tagged data**.

---

## 2. New URL Architecture & Collision Check

### 2.1 The proposed IA

From `Menetzero-Redesign/github.md`: six top-level tabs with a contextual sidebar per section.

```
Overview  |  Environmental  |  Social  |  Governance  |  Reports  |  Settings
```

### 2.2 Constraints discovered that shape the URL design

Four findings from the existing code force specific decisions:

**F1 — `/dashboard` is taken and must stay.**
`GET /dashboard` → `client.dashboard`. It is the post-login redirect target in `routes/web.php` (`redirect()->intended(route('client.dashboard'))`). It cannot move. **New Overview must reuse `/dashboard`, switching content by theme** — not claim a new URL.

**F2 — `/measurements/{path}` is a `.*` catch-all.**
```php
Route::get('/measurements/{path}', fn () => redirect()->route('quick-input.index'))->where('path', '.*');
```
Any URL starting `/measurements/` is swallowed. **No new URL may begin with `/measurements`.**

**F3 — `/quick-input/{scope}/{slug}` is a two-segment wildcard.**
Any unmatched two-segment path under `/quick-input/` hits `QuickInputController@show`. New quick-input URLs must be registered **above** it, exactly as the existing `scope3-bulk-import` routes already are (there is an explicit code comment about this).

**F4 — `/settings` currently has exactly one child: `/settings/reporting`.**
The namespace is almost entirely free. This makes Settings the lowest-risk new tab.

### 2.3 Resulting URL strategy

| Tab | Strategy | Rationale |
|---|---|---|
| Overview | **Reuse `/dashboard`** | F1 — cannot move the login target |
| Environmental | **New `/environmental/*`** | Namespace completely free |
| Social | **New `/social/*`** | Namespace completely free |
| Governance | **New `/governance/*`** | Namespace completely free |
| Reports | **Reuse `/reports`, extend children** | Root exists; only 5 children |
| Settings | **Reuse `/settings`, extend children** | F4 — only one child exists |

**Existing `/disclosures/*` is not moved, not redirected, not deleted.** It remains the canonical backend surface. New tabs are *additional entry points* to the same controllers. This is the core of the additive approach: two URL trees, one set of controllers.

---

## 3. Proposed New Routes

All under the existing company-portal middleware stack (Group K) unless noted. `disclosureAccess` is added where the target controller requires it.

### 3.1 Overview

| Method | URL | Name | Controller | Notes |
|---|---|---|---|---|
| GET | `/dashboard` | `client.dashboard` | `DashboardController@index` | **Existing route, unchanged.** Theme resolver picks view. |

### 3.2 Environmental

| Method | URL | Proposed name | Backing controller | New? |
|---|---|---|---|---|
| GET | `/environmental` | `env.overview` | new thin controller → `EsgDashboardService` | shell only |
| GET | `/environmental/measure` | `env.measure` | `QuickInputController@index` | reuse |
| GET | `/environmental/measure/entries` | `env.measure.entries` | `QuickInputController@index` | reuse |
| GET | `/environmental/measure/bulk-import` | `env.measure.bulk-import` | `QuickInputController@bulkImport` | reuse |
| GET | `/environmental/locations` | `env.locations` | `LocationController@index` | reuse |
| GET | `/environmental/boundaries` | `env.boundaries` | `EmissionBoundaryController@index` | reuse |
| GET | `/environmental/climate-risks` | `env.climate-risks` | `Disclosure\ClimateRiskController@index` | reuse + `disclosureAccess` |
| GET | `/environmental/opportunities` | `env.opportunities` | `Disclosure\ClimateOpportunityController@index` | reuse + `disclosureAccess` |
| GET | `/environmental/targets` | `env.targets` | `Disclosure\ReductionTargetController@index` | reuse + `disclosureAccess` |

### 3.3 Social

| Method | URL | Proposed name | Backing controller | New? |
|---|---|---|---|---|
| GET | `/social` | `social.overview` | new thin controller → `EsgDashboardService` | shell only |
| GET | `/social/stakeholders` | `social.stakeholders` | `Disclosure\StakeholderEngagementController@index` | reuse |
| GET | `/social/supply-chain` | `social.supply-chain` | `Disclosure\SupplyChainSupplierController@index` | reuse |
| GET | `/social/scorecard` | `social.scorecard` | `Disclosure\EsgScorecardController@index` | reuse, pillar-filtered |

### 3.4 Governance

| Method | URL | Proposed name | Backing controller | New? |
|---|---|---|---|---|
| GET | `/governance` | `gov.overview` | new thin controller → `EsgDashboardService` | shell only |
| GET | `/governance/materiality` | `gov.materiality` | `Disclosure\MaterialityMatrixController@index` | reuse |
| GET | `/governance/risks` | `gov.risks` | `Disclosure\SustainabilityRiskController@index` | reuse |
| GET | `/governance/sasb` | `gov.sasb` | `Disclosure\SasbIndexController@index` | reuse |
| GET | `/governance/policies` | `gov.policies` | `Disclosure\SectionController@editS1` | reuse |

### 3.5 Reports

| Method | URL | Proposed name | Backing controller | New? |
|---|---|---|---|---|
| GET | `/reports` | `reports.index` | `ReportController@index` | **existing, unchanged** |
| GET | `/reports/hub` | `reports.hub` | `Disclosure\OverviewController@hub` | new alias |
| GET | `/reports/ifrs-s1` | `reports.s1` | `Disclosure\IfrsS1ReportController@preview` | new alias |
| GET | `/reports/ifrs-s2` | `reports.s2` | `Disclosure\IfrsS2ReportController@preview` | new alias |
| GET | `/reports/gri` | `reports.gri` | `Disclosure\GriReportController@preview` | new alias |
| GET | `/reports/uae-esg` | `reports.uae-esg` | `Disclosure\UaeEsgReportController@preview` | new alias |

### 3.6 Settings

| Method | URL | Proposed name | Backing controller | New? |
|---|---|---|---|---|
| GET | `/settings` | `settings.index` | new thin controller | new landing |
| GET | `/settings/reporting` | `settings.reporting` | `CompanyReportingSettingsController@edit` | **existing, unchanged** |
| GET | `/settings/profile` | `settings.profile` | `ProfileController@index` | new alias |
| GET | `/settings/team` | `settings.team` | `RoleManagementController@index` | new alias |
| GET | `/settings/billing` | `settings.billing` | `Client\SubscriptionController@billing` | new alias |

### 3.7 Theme control (super-admin gated)

| Method | URL | Name | Notes |
|---|---|---|---|
| GET | `/theme/{theme}` | `theme.switch` | `{theme}` ∈ `old\|new`. Sets session, redirects back. **Open to any authenticated user** — see 7A.1. |

Supports `?theme=new` as a query param on any URL via middleware, which then **writes to session** so the choice is sticky across navigation.

### 3.8 Route count

| Category | Count |
|---|---|
| Genuinely new URLs | 27 |
| Reused existing (unchanged) | 3 |
| Theme control | 1 |
| **New registrations** | **28** |

Existing 371 remain untouched → **399 total after Phase 1–4.**

---

## 4. Route Collision Matrix

### 4.1 Collision rules applied

| Rule | Description |
|---|---|
| R1 | Exact route already exists |
| R2 | A wildcard/dynamic route can capture the new URL |
| R3 | A parent route can interfere |
| R4 | Existing middleware/auth affects the new route |
| R5 | Existing rewrite/redirect rules affect the new route |
| R6 | Existing frontend routing could intercept |
| R7 | `/disclosures/*` creates conceptual or technical ambiguity |

**R5 — rewrite rules.** Both `.htaccess` files verified. Root `.htaccess` rewrites non-file/non-dir to `public/index.php`; `public/.htaccess` is stock Laravel plus a **trailing-slash 301 redirect** (`RewriteCond %{REQUEST_URI} (.+)/$ → R=301`). Consequence: `https://app.menetzero.com/dashboard/?theme=NEW` **301-redirects to `/dashboard?theme=NEW`**. Harmless — the query string survives — but it means the URL in the original brief works only because the param is preserved through the redirect. Server-level config (nginx/Cloudflare) is **outside the repo and requires your verification**.

**R6 — frontend routing.** No SPA router present. No Vue/React/Inertia. Alpine.js only (dropdowns/sidebar). **R6 is clear for every route below.**

### 4.2 The matrix

| New URL | Exact Existing Match | Similar Existing Route | Potential Conflict | Recommended Action | Final Decision |
|---|---|---|---|---|---|
| `/dashboard` | **YES** — `client.dashboard` | `/consultant/dashboard`, `/admin/dashboard` | **R1 CRITICAL.** Also login redirect target. | Do **not** register. Reuse route; theme resolver selects view. | **REUSE — no new route** |
| `/environmental` | No | `/disclosures/esg-dashboard` | R7 conceptual overlap only. No technical clash. | Register. Same controllers, new entry point. | **SAFE** |
| `/environmental/measure` | No | `/quick-input/entries` | R7. F3 wildcard is under `/quick-input/`, not here. | Register. | **SAFE** |
| `/environmental/measure/entries` | No | `/quick-input/entries` | None — 3 segments, distinct root. | Register. | **SAFE** |
| `/environmental/measure/bulk-import` | No | `/quick-input/bulk-import` | None. | Register. | **SAFE** |
| `/environmental/locations` | No | `/locations` (**`Route::resource`**) | **R2/R3.** Resource generates `/locations/{location}`. Different root → no capture. | Register. Verify no `/environmental` resource added later. | **SAFE — monitor** |
| `/environmental/boundaries` | No | `/locations/{location}/emission-boundaries` | R3 — existing is nested under `{location}`. New one needs a location context (query or session). | Register. **Resolve location context explicitly.** | **SAFE — needs context design** |
| `/environmental/climate-risks` | No | `/disclosures/ifrs-s2/climate-risks` | R4 — target needs `disclosureAccess`. R7. | Register **with `disclosureAccess`**. | **SAFE — add middleware** |
| `/environmental/opportunities` | No | `/disclosures/ifrs-s2/opportunities` | R4, R7. | Register with `disclosureAccess`. | **SAFE — add middleware** |
| `/environmental/targets` | No | `/disclosures/ifrs-s2/targets`, `/disclosures/esg-targets` | R4, R7 — **two** existing target surfaces. | Register with `disclosureAccess`. **Decide which it maps to.** | **NEEDS DECISION** |
| `/social` | No | — | None. | Register. | **SAFE** |
| `/social/stakeholders` | No | `/disclosures/stakeholders` | R4, R7. | Register with `disclosureAccess`. | **SAFE — add middleware** |
| `/social/supply-chain` | No | `/disclosures/supply-chain` | R4, R7. | Register with `disclosureAccess`. | **SAFE — add middleware** |
| `/social/scorecard` | No | `/disclosures/esg-scorecard` | R4, R7. Scorecard spans E/S/G — needs pillar filter. | Register with `disclosureAccess` + pillar param. | **SAFE — needs filter** |
| `/governance` | No | — | None. | Register. | **SAFE** |
| `/governance/materiality` | No | `/disclosures/materiality-matrix` | R4, R7. | Register with `disclosureAccess`. | **SAFE — add middleware** |
| `/governance/risks` | No | `/disclosures/ifrs-s1/sustainability-risks` **and** `/disclosures/ifrs-s2/climate-risks` | R7 — two registers, different schemas (4 vs 7 cols). | **D4: keep separate, unified UI.** No migration. | **SAFE — per D4** |
| `/governance/sasb` | No | `/disclosures/sasb` | R4, R7. | Register with `disclosureAccess`. | **SAFE — add middleware** |
| `/governance/policies` | No | `/disclosures/ifrs-s1/sections/{section}` | R3 — existing takes a `{section}` param; new is param-less. | Register. **Default section must be chosen.** | **SAFE — needs default** |
| `/reports` | **YES** — `reports.index` | — | **R1.** Root already registered. | Reuse; theme resolver selects view. | **REUSE — no new route** |
| `/reports/hub` | No | `/reports/show`, `/disclosures` (hub) | R3 — sibling under existing prefix. No wildcard in `/reports/*`. | Register inside existing group. | **SAFE** |
| `/reports/ifrs-s1` | No | `/disclosures/ifrs-s1/report` | R7. | Register. | **SAFE** |
| `/reports/ifrs-s2` | No | `/disclosures/ifrs-s2/report` | R7. | Register. | **SAFE** |
| `/reports/gri` | No | `/disclosures/gri/report` | R7. | Register. | **SAFE** |
| `/reports/uae-esg` | No | `/disclosures/uae-esg-report/report` | R7. | Register. | **SAFE** |
| `/settings` | No | `/settings/reporting` | R3 — child exists, parent does not. Registering parent is safe. | Register landing page. | **SAFE** |
| `/settings/reporting` | **YES** — `settings.reporting` | — | **R1.** | Reuse; theme resolver selects view. | **REUSE — no new route** |
| `/settings/profile` | No | `/profile` | R7 only. | Register. | **SAFE** |
| `/settings/team` | No | `/roles`, `/staff/*`, `/consultant/team/*` | R7 — three team surfaces. `/consultant/team` is a different root. | Register. | **SAFE** |
| `/settings/billing` | No | `/subscriptions/billing` | **R4** — existing has `restrictManagedClientBilling`. | Register **with `restrictManagedClientBilling`**. | **SAFE — add middleware** |
| `/theme/{theme}` | No | — | R4 — gating decided in 7A.1. | Register open, `switch_enabled` kill-switch. | **SAFE — open by decision** |

### 4.3 Summary

| Verdict | Count | Routes |
|---|---|---|
| **REUSE — no new route** | 3 | `/dashboard`, `/reports`, `/settings/reporting` |
| **SAFE — register as-is** | 13 | |
| **SAFE — add middleware** | 8 | all `disclosureAccess`; `/settings/billing`; `/theme/*` |
| **NEEDS DECISION** | 3 | `/environmental/targets`, `/environmental/boundaries`, `/governance/policies` |
| **BLOCKED** | 0 | *(none — D4 unblocked `/governance/risks`)* |

**No hard blockers from exact collisions.** The three `REUSE` cases are handled by the theme resolver rather than new routes, which is cleaner anyway.

### 4.4 Wildcards to respect

| Wildcard | Pattern | Rule |
|---|---|---|
| `/measurements/{path}` | `.*` | **Never** create a URL under `/measurements` |
| `/quick-input/{scope}/{slug}` | 2 segments | New `/quick-input/*` routes must register **above** it |
| `/consultants/{consultant}` | 1 segment | New `/consultants/*` routes must register **above** it |
| `/locations/{location}` | resource | New `/locations/*` routes must register **above** it |
| `/admin/consultants/{consultant}` | 1 segment | Same, in admin |

---

## 5. Phased Implementation Plan

### Phase order rationale

Sequenced by **risk to live clients**, ascending. Contrary to a company-first instinct, the company portal goes **last** — it is the largest surface (~150 routes), carries the IA change, and every live client uses it daily. Auth and Emails first prove the design system in Laravel at near-zero risk.

| Phase | Scope | Screens | Client risk | Depends on |
|---|---|---|---|---|
| **0** | Theme infrastructure | 0 | **None** | — |
| **1** | Auth | 24 | **Very low** | 0 |
| **2** | Emails | 6 | **None** | — |
| **3** | Consultant portal | 5 | **Low** | 0, 1 |
| **4** | Admin portal | 6 | **None** (internal) | 0, 1 |
| **5** | Company portal | 18 + 8 internal | **High** | all |
| **6** | Switch-over | — | **Managed** | all |

---

### Phase 0 — Theme Infrastructure

**Objective:** Make two themes coexist. No visual change to anything.

| Sub-phase | Deliverable | Test |
|---|---|---|
| 0.1 | `config/themes.php` — registry, default `old` | Config loads |
| 0.2 | `ThemeResolver` service — session → DB → default | Unit test each tier |
| 0.3 | `ResolveTheme` middleware — reads `?theme=`, **writes to session** | Param sticks across navigation |
| 0.4 | `/theme/{theme}` route, `ensureSuperAdmin` | Non-admin gets 403 |
| 0.5 | Copy `mnz-ui.css` + `mnz-ui.js` → `public/css/`, `public/js/` | Assets 200 |
| 0.6 | View namespace `theme-new::` with fallback to default | Missing view falls back silently |
| 0.7 | `layouts/app.blade.php` resolves shell by theme | Old theme renders **byte-identical** |

**Exit criteria:** every existing page renders unchanged with theme `old`; theme `new` resolves but has no views yet.

**Why the session write matters (0.3):** a bare query param dies on the first nav click. `/dashboard?theme=new` → click Reports → `/reports` with no param → old theme. Writing to session on read makes it sticky until explicitly cleared.

---

### Phase 1 — Auth (24 screens)

**Why first:** no layout dependency, no company context, no data. Pure proof that `mnz-ui.css` works in Blade.

| Sub-phase | Screens | Views |
|---|---|---|
| 1.1 | Company auth (8) | `auth/login`, `register`, `forgot-password`, `reset-password`, `password-reset-success` |
| 1.2 | Consultant auth (8) | `consultant/auth/*` |
| 1.3 | Admin auth (8) | `admin/auth/*` |

Routes unchanged — only the view layer switches by theme. **Each sub-phase independently testable.**

---

### Phase 2 — Emails (6 templates)

**Why parallel-safe:** emails have no theme flag at all. They are separate templates either way.

Per `github.md`: welcome exists in `EmailTemplateService`; the other five are new. Outlook-safe table layouts with plain-text parts. Test via existing `/admin/email-test`.

---

### Phase 3 — Consultant Portal (5 screens, 16 pages)

Smallest real portal. `consultant/layouts/app.blade.php` gets a new-theme sibling.

| Sub-phase | Scope |
|---|---|
| 3.1 | Consultant shell (topbar, sidebar, acting-as banner) |
| 3.2 | Dashboard |
| 3.3 | Clients / workspace switcher |
| 3.4 | Packs / orders / documents |
| 3.5 | Profile / intro-requests |

**Note:** the "acting as" banner already exists (`$isConsultantActing`, `consultant.workspace.enter/exit`, managed-client switcher) — the design recreates it faithfully. Reuse `ConsultantAgencyWorkspaceService` unchanged.

---

### Phase 4 — Admin Portal (6 screens, 51 pages)

Internal users only — bugs cost nothing externally.

| Sub-phase | Scope |
|---|---|
| 4.1 | Admin shell |
| 4.2 | Dashboard / statistics |
| 4.3 | Companies / users |
| 4.4 | Consultants / orders |
| 4.5 | Subscriptions / price book / packages |
| 4.6 | Site content / email templates / emissions master data |

---

### Phase 5 — Company Portal (18 + 8 screens, ~150 routes)

The IA change. Highest risk, done last, when the system is proven.

| Sub-phase | Scope | Routes | Notes |
|---|---|---|---|
| 5.0 | **Resolve the 3 NEEDS DECISION + 1 BLOCKED item** | — | Gate for everything below |
| 5.1 | Company shell + six-tab nav + **fallback wrapper** | — | Unbuilt tabs render existing content in new shell |
| 5.2 | Overview | reuse `/dashboard` | `DashboardInsightsService` unchanged |
| 5.3 | Environmental + Quick Input grid | 9 new | Largest sub-phase |
| 5.4 | Social | 4 new | |
| 5.5 | Governance | 5 new | **Needs risk-register merge** |
| 5.6 | Reports + preview | 5 new | |
| 5.7 | Settings / Team / Billing / Profile | 5 new | |
| 5.8 | Internal states — onboarding, bulk-import mapping, empty, error | — | Genuinely new; no existing views |

**5.1 fallback is what makes 5.2–5.8 independently shippable.** Without it, a user in the new shell clicking an unbuilt tab hits a dead route. With it, the new theme always fully works — only partially restyled.

---

### Phase 6 — Switch-over

| Step | Action | Reversible? |
|---|---|---|
| 6.1 | Internal team on `new` for 1 week | Yes — session |
| 6.2 | Opt-in flag per company; 2–3 friendly clients | Yes — DB flag |
| 6.3 | Default flips to `new`; `?theme=old` still available | Yes — one DB value |
| 6.4 | Old views/CSS removed after a stable period | **No** |

**Only 6.4 is irreversible.** Everything before it is a flag flip.

---

## 6. Phase 2 — Existing Architecture & Dependency Mapping

*(Per the brief: understand what existing pages depend on before changing their UI. This section is a dependency map, not an implementation phase.)*

### 6.1 Shared services — reuse rules

| Service | Used by | New theme rule |
|---|---|---|
| `DashboardInsightsService` | dashboard | Reuse. 6 public methods — **add only** |
| `EsgDashboardService` | esg-dashboard | Reuse for E/S/G overviews — **primary reuse target** |
| `DisclosureService` | all disclosures | Reuse unchanged |
| `IfrsS1ReportService`, `IfrsS2ReportService`, `UaeEsgReportService` | reports | Reuse unchanged |
| `ConsultantAgencyWorkspaceService` | acting-as | Reuse unchanged |
| `PlanGate` + `PlanGateComposer` | gating | **Must apply to new views too** |
| `Scope12BulkImportService`, `Scope3BulkImportService` | import | Reuse; mapping step is additive |

> **Rule P3 restated:** new theme may **add** service methods. It may never change an existing signature, because the old theme calls the same object.

### 6.2 View composers — must extend to new views

`app/Providers/AppServiceProvider.php` binds composers to **view names**. New views will not receive this data unless registered.

| Composer | Currently bound to | Action |
|---|---|---|
| `PlanGateComposer` | `layouts.app`, `layouts.partials.nav-client`, `reports.*`, `disclosures.*`, `quick-input.*` | **Add new-theme view patterns** |
| `ReportingYearsComposer` | `disclosures.*`, `reports.*` | **Add new-theme patterns** |
| `ConsultantAgencyComposer` | `consultant.layouts.app` | **Add new consultant layout** |

**This is the most likely silent failure in the whole migration.** A new view renders, looks correct, and quietly lacks plan-gating — a paid feature visible to a free tier. Verify explicitly per phase.

### 6.3 Middleware dependencies

The company stack is six-deep and order-sensitive:
```
auth:web → setActiveCompany → ensureConsultantManagedWorkspace
→ checkCompanyType:client → restrictManagedClientWorkspace → ensureOnboardingComplete
```
Plus `disclosureAccess` on `/disclosures/*` and `restrictManagedClientBilling` on `/subscriptions/*`.

**Every new company route must join the identical stack in the identical order.** Omitting `setActiveCompany` yields a null company; omitting `disclosureAccess` exposes plan-gated content.

### 6.4 Layout section contract

All 152 pages use exactly four sections — `content` (152), `title` (150), `page-title` (109), `sidebar` (8). **Any new layout must yield the same four.** This is why existing pages can render inside the new shell unchanged, and it is the technical foundation of the 5.1 fallback.

### 6.5 Asset coexistence

`mnz-ui.css` uses 92 classes, **all `mnz-` prefixed**. Zero collision with `app-shell.css`, `portal-design-system.css`, `portal-enterprise.css`, `quick-input.css`, `consultant-shell.css`. Both stylesheets ship together indefinitely. No build step — plain CSS, like the current files.

**The new design removes dependencies:** no Tailwind CDN, no Alpine.js, in any of the six design files. It replaces the Tailwind CDN + three portal stylesheets + Alpine with ~14 KB CSS + ~3 KB JS.

> **Caution:** `layouts/app.blade.php` remaps `purple`/`violet`/`indigo`/`orange` → brand green via the Tailwind CDN config. Any old-theme page rendered inside a new shell **without** the Tailwind CDN will lose those colors. The 5.1 fallback wrapper must keep loading the old CSS for fallback pages.

### 6.6 Database changes

| Change | Type | Risk |
|---|---|---|
| `theme` column on `companies` | Add nullable | **None** — additive |
| `theme` column on consultant table | Add nullable | **None** — additive |
| Risk-register merge (3 fields) | Add nullable to `sustainability_risks` | **Low** if nullable + old view untouched |

Per `github.md`: `climate-risks` has 7 columns (name, risk_type, time_horizon, likelihood, owner, financial_impact, mitigation); `sustainability-risks` has 4 (name, topic, time_horizon, description). Merging needs **three fields added to S1 risks**. Additive and nullable → old view keeps reading its original four.

### 6.7 Design-file translation

The `.dc.html` files are **mockups, not markup**. They use canvas templating — `<x-dc>`, `<sc-if value="{{ showActing }}">`, `<helmet>`, `{{ placeholder }}` — with inline styles in artboard bodies rather than the class layer.

**Each screen is hand-translated to Blade against `mnz-ui.css`. No paste shortcut.** The CSS and JS in `assets/` are real production assets and drop in as-is.

---

## 7. Decisions — APPROVED

Approved by product owner **2026-08-25**. These are settled; do not re-litigate.

| # | Decision | Approved resolution |
|---|---|---|
| D1 | `/environmental/targets` | Use the **reduction / climate targets** surface (`/disclosures/ifrs-s2/targets`) |
| D2 | `/environmental/boundaries` | **List → select location → boundaries** |
| D3 | `/governance/policies` | Default to the **Governance section of IFRS S1** |
| D4 | Risk registers | **Keep schemas separate for V1**; present through a unified Governance risk UI. **Do NOT merge schemas.** |
| D5 | Old URLs after switch | **Keep working.** No unnecessary 301 migrations. |
| D6 | Admin theming | Admin uses the new theme as **default** once Phase 4 is ready. No permanent dual-theme for Admin. |
| D7 | Server-level routing | **Requires separate verification** by product owner (nginx / Cloudflare / Webuzo). Not verifiable from the repo. |

**D4 supersedes the earlier "BLOCKED" verdict on `/governance/risks` in §4.2.** With schemas kept separate, that route is unblocked — the Governance risk UI reads both registers and presents them together, without a migration.

### 7.1 Confirmed server environment

Verified from the production panel (2026-08-25):

| Property | Value |
|---|---|
| Panel | Webuzo v4.7.5 |
| OS | AlmaLinux v9.8 |
| Server IP | 147.93.62.3 |
| PHP | 8.3.23 |
| Web server | Apache (`.htaccess` rewrites active — confirms R5 analysis) |

D7 still stands for **nginx / Cloudflare / proxy layers** in front of Apache.

---

## 7A. Non-Negotiable Technical Requirements

Approved **2026-08-25**. Any change violating one of these is wrong.

| # | Requirement | Status |
|---|---|---|
| 1 | Existing 371 routes must continue working | **Binding** |
| 2 | Existing `/disclosures/*` routes must remain intact | **Binding** |
| 3 | No `/v2` prefix | **Binding** |
| 4 | ~~Theme selection must be Super Admin only~~ | **OVERRIDDEN — see 7A.1** |
| 5 | Theme selection stored in session, sticky across navigation | **Binding** |
| 6 | New functionality must be additive | **Binding** |
| 7 | Existing service signatures must not change | **Binding** |
| 8 | Existing DB structures not unnecessarily modified | **Binding** |
| 9 | New views must receive required composers, esp. `PlanGateComposer` | **Binding** |
| 10 | Every new company route uses the same middleware stack, correct order | **Binding** |
| 11 | Unbuilt pages use the fallback, never a broken route | **Binding** |
| 12 | `/measurements/{path}` untouched — catch-all | **Binding** |
| 13 | Careful with `/quick-input/{scope}/{slug}` route ordering | **Binding** |

### 7A.1 Requirement 4 — explicit override

**Original:** theme switching restricted to Super Admin.

**Overridden by product owner, 2026-08-25.** Theme switching is open to any authenticated user via `?theme=new`.

**Why the original was unbuildable.** Super admins authenticate on the **`admin` guard** (`admins` table, `App\Models\Admin`). The company portal requires the **`web` guard** (`auth:web` → `checkCompanyType:client`). A super admin therefore **cannot reach `/dashboard` at all** — the only identity permitted to switch the theme was the one identity unable to view the pages the theme applies to. Phase 5 would have been untestable.

**Risk accepted.** `?theme=new` is guessable; a client could land on a half-built portal and report it as broken. This is a **presentation** risk, not a security one — the theme changes appearance only. **Every route retains its full middleware stack regardless of theme.** No data access changes.

**Mitigations retained:**
- `config/themes.php` → `switch_enabled` (env `THEME_SWITCH_ENABLED`) kills switching instantly without a deploy.
- **No theme UI anywhere.** No nav item, button, or menu entry in any portal. URL-only. (Requirement 2 of the product owner's brief — normal users must never *see* a theme option.)

---

## 8. Risk Register

| # | Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|---|
| R-1 | View composers not bound to new views → plan-gating silently lost | **High** | **High** | Explicit composer verification per phase (§6.2) |
| R-2 | New route misses a middleware → auth/data leak | Medium | **High** | Route-group template; never register standalone |
| R-3 | Fallback pages lose colors (no Tailwind CDN) | **High** | Medium | Fallback wrapper keeps old CSS (§6.5) |
| R-4 | Service signature changed for new theme breaks old | Low | **Critical** | P3 — additive only, enforced in review |
| R-5 | `?theme=new` found by a client | Medium | Low | **Accepted per 7A.1.** No theme UI; `THEME_SWITCH_ENABLED=false` kills it instantly |
| R-6 | IA remap wrong → rework across dozens of screens | Medium | **High** | §2–3 approved before Phase 5 |
| R-7 | Server rewrite rules unknown | Medium | Medium | D7 — verify before Phase 6 |
| R-8 | Half-migrated nav creates visible seams | **High** | Low | Accepted; 5.1 fallback keeps it functional |

---

## 9. Approved Phase Order & Status

Phase order approved **2026-08-25** — risk-based, company portal last.

| Phase | Scope | Status |
|---|---|---|
| 0 | Theme Infrastructure | **COMPLETE** — awaiting review |
| 1 | Auth | **COMPLETE** — hotfixed 2026-08-26, awaiting re-test |
| 2 | Emails | Not started |
| 3 | Consultant | Not started |
| 4 | Admin | Not started |
| 5 | Company | Not started |
| 6 | Switch-over | Not started |

**Each phase stops for review before the next begins.**

---

## 10. Phase 0 — Implementation Record

Completed **2026-08-25**. Route count 371 → **372** (only `/theme/{theme}`).

### Files added

| File | Purpose |
|---|---|
| `config/themes.php` | Theme registry, default, kill-switch, session/query keys |
| `app/Services/ThemeResolver.php` | Resolution, persistence, asset + view fallback |
| `app/Http/Middleware/ResolveTheme.php` | Reads `?theme=`, **writes to session** |
| `app/Providers/ThemeServiceProvider.php` | Namespace registration, view sharing, Blade directives |
| `public/css/mnz-ui.css` | New design system (13.6 KB, 92 `mnz-` classes) |
| `public/js/mnz-ui.js` | New design JS (6.5 KB, no dependencies) |
| `public/images/menetzero-2.svg` | New theme logo |
| `resources/views/themes/new/` | New theme view root (empty — Phases 1–5 fill it) |

### Files modified

| File | Change | Additive? |
|---|---|---|
| `bootstrap/providers.php` | Register `ThemeServiceProvider` | Yes |
| `bootstrap/app.php` | Append `ResolveTheme` to `web` group | Yes |
| `routes/web.php` | +1 route (`/theme/{theme}`) | Yes |
| `app/Providers/AppServiceProvider.php` | Composers expanded via `withThemeViews()` | Yes |
| `resources/views/layouts/app.blade.php` | Asset block inside `@theme('new')` | Yes |
| `resources/views/consultant/layouts/app.blade.php` | Same | Yes |
| `resources/views/admin/layouts/app.blade.php` | Same | Yes |
| `.env.example` | Documented `THEME_*` vars | Yes |

**No file had a line removed or changed in a way that affects the old theme.** Every layout edit sits inside a `@theme('new')` guard.

### Verification performed

| Check | Result |
|---|---|
| PHP brace/paren/bracket balance, 8 files | PASS |
| Blade `@theme`/`@endtheme` balance, 3 layouts | PASS (2/2 each) |
| Blade `@foreach`/`@endforeach` balance | PASS |
| Layout diff vs pre-Phase-0 | Additive only, all inside theme guards |
| Route count | 371 → 372 |
| `/measurements` untouched (req 12) | PASS — zero diff lines |
| `/disclosures/*` untouched (req 2) | PASS — zero diff lines |
| `/quick-input` ordering untouched (req 13) | PASS — zero diff lines |
| Fallback simulation (empty theme dir) | All views fall back correctly |
| Composer expansion | 5→10, 2→4, 1→2 |
| Resolution order | no session→old, new→new, bogus→old |

### Not yet verified

**No PHP runtime on the development machine** (`php` not on PATH; `artisan` unavailable). Verification was static: syntax balance, diff review, and logic simulation in Python. **Runtime verification must happen on the server** — see Phase 0 review notes.

---

## 11. Phase 1 — Auth: Implementation Record

Completed **2026-08-25**. **Zero route changes** (still 372). 10 new views, 9 existing files touched by one line each.

### Mechanism added in Phase 1

Phase 0 handled `@extends`-style layouts. Phase 1 revealed a gap: some views are returned **by name from a controller** — `AdminLoginController` does `return view('admin.auth.login')`. Editing that controller would break rule P3.

**Solution: `View::getFinder()->prependLocation()`.** Under the new theme, `resources/views/themes/new/` is prepended to the view finder's search paths. Blade finds a theme copy first when one exists, and the original everywhere else.

This is strictly better than the namespace approach alone, and it is why an unmigrated screen can never 404 — the original path is still searched, just second. It applies to **every** view, including controller-returned ones, with no controller edits.

### Two layout-resolution paths, both additive

| Path | Used by | Mechanism |
|---|---|---|
| `@extends($authLayout ?? '...')` | 8 auth pages | `$authLayout` shared by `ThemeServiceProvider`; null-coalescing keeps the page working even if the variable is ever absent |
| Finder `prependLocation` | `admin.auth.login`, all controller-returned views | Theme dir searched first |

### Files added (10)

| File | Screen |
|---|---|
| `themes/new/layouts/portal-auth.blade.php` | Auth shell — split layout, dark stats panel, per-portal accent |
| `themes/new/auth/login.blade.php` | Company sign in |
| `themes/new/auth/register.blade.php` | Company sign up |
| `themes/new/auth/forgot-password.blade.php` | Company forgot password |
| `themes/new/auth/reset-password.blade.php` | Company set new password |
| `themes/new/consultant/auth/login.blade.php` | Consultant sign in |
| `themes/new/consultant/auth/register.blade.php` | Consultant sign up |
| `themes/new/consultant/auth/forgot-password.blade.php` | Consultant forgot password |
| `themes/new/consultant/auth/reset-password.blade.php` | Consultant set new password |
| `themes/new/admin/auth/login.blade.php` | Admin sign in |

### Files modified (11)

| File | Change |
|---|---|
| 8 × `auth/*.blade.php`, `consultant/auth/*.blade.php` | **One line each**: `@extends('layouts.portal-auth')` → `@extends($authLayout ?? 'layouts.portal-auth')` |
| `app/Services/ThemeResolver.php` | +`layout()` method |
| `app/Providers/ThemeServiceProvider.php` | +`registerThemeViewOverrides()`, +`$authLayout`/`$appLayout` shared |

**Not modified:** `layouts/portal-auth.blade.php`, `admin/auth/login.blade.php`, any controller, any route.

### Form contracts preserved

Every redesigned form posts to the same route with the same field names as the view it replaces — verified field-by-field against the originals:

| Screen | Route | Fields |
|---|---|---|
| Company login | `login.post` | email, password |
| Company register | `register` | name, email, password, password_confirmation |
| Company forgot | `password.email` | email |
| Company reset | `password.update` | token, email, password, password_confirmation |
| Consultant login | `consultant.login.post` | email, password, remember |
| Consultant register | `consultant.register.post` | name, company_name, email, phone, password, password_confirmation |
| Consultant forgot | `consultant.password.email` | email |
| Consultant reset | `consultant.password.update` | token, email, password, password_confirmation |
| Admin login | `admin.login.post` | email, password, remember |

### Deliberately left on fallback

`auth/password-reset-success`, `invitations/accept`, `invitations/expired`, `invitations/invalid`, `invitations/setup-password` extend `layouts.app`, not `layouts.portal-auth`. Migrating them means touching the main app shell, which is Phase 5 work. They render correctly on the old shell today — **this is the fallback doing its job**, not an omission.

### Verification performed

| Check | Result |
|---|---|
| Blade directive balance, 10 new views | PASS |
| PHP balance, 2 modified services | PASS |
| Old-theme diff, 8 auth views | One line each, null-coalescing fallback |
| Route count | 372 — unchanged from Phase 0 |
| Fallback simulation | 10 resolve to theme, 5 fall back correctly |
| Live directives inside a comment block | Found and removed in the auth shell |

### Not yet verified

Same limitation as Phase 0: **no PHP runtime and no `vendor/` directory on the development machine.** Static verification only.

One item genuinely needs runtime confirmation: `@hasSection` closes with `@endif` (Laravel's documented behaviour) — correct as written, but unexecuted. **A single page load of `/login?theme=new` confirms the whole phase.**

---

## 12. Phase 1 — Hotfix: view finder ordering

Found in production testing **2026-08-26** on `/login?theme=new`. Old page content rendered inside the new shell.

### Root cause

`registerThemeViewOverrides()` ran in `ThemeServiceProvider::boot()`, which fires **before session middleware**. At that moment the session was empty, so `$themes->current()` returned `'old'`, whose `view_path` is null — the method returned early and **never prepended the theme directory**.

`ResolveTheme` wrote `theme=new` to the session correctly, but that happened *after* boot. The view finder was already frozen on the old theme.

The result was a split render: the **shell** switched (its `@theme('new')` evaluates late, at render time) while the **page view** did not. Symptoms were an unsized Google logo, doubled `—` + `✓` list markers, and old placeholder text.

### Fix

Moved the prepend into `ResolveTheme::pointViewFinderAtTheme()`, which runs after `StartSession`. The middleware is registered with `web(append:)` — **it must stay appended**, since prepending would place it before the session exists and reintroduce the bug. This constraint is now documented at the registration site in `bootstrap/app.php`.

Added two safeguards:
- **Idempotency guard** (`static $prepended`) — the finder is a singleton and `prependLocation()` does not de-duplicate, so a sub-request would otherwise stack the same path repeatedly.
- **`$finder->flush()`** after prepending — views resolved earlier in the request are cached against their old paths.

### Simplification the fix enabled

With the finder resolving themes, the `$authLayout` variable became redundant — and actively harmful, since it created two different paths to the same file.

Removed: `ThemeResolver::layout()`, the shared `$authLayout` / `$appLayout` variables, and `@extends($authLayout ?? …)` in every view.

**Consequence: the 8 existing auth views are now byte-identical to pre-redesign** (`git diff c02534a` is empty for them). Phase 1 touches **zero** existing view files — the theme layer needs no page edits at all.

### Files changed by the hotfix

| File | Change |
|---|---|
| `app/Http/Middleware/ResolveTheme.php` | Rewritten: +`pointViewFinderAtTheme()`, idempotency guard, flush |
| `app/Providers/ThemeServiceProvider.php` | Removed `registerThemeViewOverrides()` and layout sharing; note left explaining why it cannot live at boot |
| `app/Services/ThemeResolver.php` | Removed dead `layout()`; `view()` docblock updated |
| `bootstrap/app.php` | Documented the append-vs-prepend constraint |
| 8 existing auth views | **Reverted to original** — zero diff |
| 9 new theme views | `@extends('layouts.portal-auth')` — plain name |

### Environment facts confirmed

| Fact | Implication |
|---|---|
| Laravel 12 | `prependLocation()` and `flush()` are both on `ViewFinderInterface` |
| Octane **not** installed | Statics reset per request; the guard cannot leak across requests |
| Apache + PHP-FPM (Webuzo) | Standard per-request lifecycle |
| `append()` places middleware after `StartSession` | Session available when the finder is configured |

---

*Sections 1–6 are the plan. Sections 7, 7A, 9 record approved decisions.*
