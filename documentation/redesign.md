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
| 1 | Auth | **COMPLETE** — 2 hotfixes 2026-08-26, awaiting re-test |
| 2 | Emails | **DEFERRED to last** — see §14 |
| 3 | Consultant | **SHELL COMPLETE** — hotfixed 2026-08-26 |
| 4 | Admin | **SHELL COMPLETE** — awaiting review |
| 5 | Company | **5.0–5.4, 5.7 COMPLETE** — Overview, Settings, Social migrated |
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

## 13. Phase 1 — Hotfix 2: `.mnz-body` misapplied to `<body>`

Found in production testing **2026-08-26**, immediately after Hotfix 1. View resolution was correct (the new view rendered), but the layout was broken: the dark panel stacked below the form instead of beside it, and content was clipped at ~543px.

### Root cause

`mnz-ui.css` line 73:

```css
.mnz-body{flex:1;display:flex;align-items:stretch;min-height:0}
```

Despite the name, `.mnz-body` is the **portal shell's content-row class** — the flex row that holds sidebar + main content. It is not a `<body>` class.

I had applied it to the `<body>` element in all four layouts. On `<body>` it turns the page into a flex container, which fought the auth grid and produced the stacked, clipped render.

### Fix

Removed `.mnz-body` from every `<body>` tag — the auth shell and all three Phase 0 layouts. Phases 3–5 will apply it to the correct inner element when those shells are actually built.

Also hardened the auth grid while here:

| Before | After |
|---|---|
| `grid-template-columns: repeat(auto-fit, minmax(min(100%, 440px), 1fr))` | `grid-template-columns: 1fr 1fr` |
| No explicit single-column rule under 880px | `.mnz-auth { grid-template-columns: 1fr }` added |

`auto-fit` + `minmax` collapsed unpredictably once the parent was a flex child. An explicit two-column grid with an explicit mobile override is deterministic.

### Latent bug caught in Phase 0 layouts

The same `.mnz-body` mistake was present in all three Phase 0 layouts:

- `resources/views/layouts/app.blade.php`
- `resources/views/consultant/layouts/app.blade.php`
- `resources/views/admin/layouts/app.blade.php`

It was invisible because those shells have no new-theme views yet. It would have surfaced as a broken portal in Phases 3–5, far from its cause. **Fixed now.**

### Class audit

Audited every `mnz-` class used across all theme views and layouts:

| Class | Status |
|---|---|
| `mnz-auth`, `mnz-auth__*` (9 classes) | Mine, defined in the auth shell — no conflict |
| `mnz-theme` | Not defined in `mnz-ui.css` — inert marker, harmless |
| `mnz-body` | **Removed** — belonged to the portal shell |

No other `mnz-ui.css` class is applied anywhere yet.

### Lesson for Phases 3–5

`mnz-ui.css` class names describe **position in the portal shell**, not generic roles. Before applying any class from it, check its definition. `.mnz-body`, `.mnz-main`, `.mnz-side` are all structural shell classes, not semantic ones.

---

## 14. Phase 2 — Emails: DEFERRED

Deferred by product owner **2026-08-26**, to run after the portal phases.

### Why the original plan was wrong

Phase 2 was scoped as "6 templates, isolated, no theme flag needed." Inspection showed the email system is **not** Blade-per-template:

- `resources/views/emails/template.blade.php` — one wrapper (header, card, footer)
- `{!! $bodyHtml !!}` — content injected from the **`email_templates` DB table**, falling back to `config/emails.php`
- Admins edit those bodies through `/admin/email-templates`

So "6 templates" is **1 wrapper + 6 database rows**. Restyling bodies is a data migration, not a view migration.

### The structural problem

**Emails have no session**, so `?theme=new` cannot preview them. Whatever the wrapper renders is what every customer receives on the next send. This is the only phase that ships straight to customers with no preview — which breaks the core premise of the migration and is why it now runs last.

### When resumed, three options

| Option | Scope | Data risk |
|---|---|---|
| **A** | Themed wrapper only; DB bodies untouched | **None** |
| **B** | A + update `config/emails.php` defaults for the 5 templates that do not exist yet | None (does not touch admin-edited rows) |
| **C** | A + reseed all 6 DB bodies | **Overwrites admin edits in production** |

Recommended: **A or A+B**. Option C is the only one touching production data.

**Open question for that phase:** the wrapper should be config-driven (follows `THEME_DEFAULT`, flips with everything else at Phase 6) rather than shipping ahead of the portal.

---

## 15. Phase 3 — Consultant Portal: shell record

Shell completed **2026-08-26**. **Zero existing files modified. Zero route changes** (still 372).

### Files added (2)

| File | Purpose |
|---|---|
| `themes/new/consultant/layouts/app.blade.php` | Consultant shell — topbar, sidebar, content row |
| `themes/new/layouts/partials/nav-consultant.blade.php` | Sidebar nav |

### Structure

Follows `mnz-ui.css` shell vocabulary:

```
.mnz-app > .mnz-topbar + .mnz-body > (.mnz-side + .mnz-main)
```

`data-pillar="s"` on `<body>` makes the consultant portal read Social-blue via `--accent`, distinct from the company portal's Environmental-green.

### Nav parity verified

Every route reference in the new nav was diffed against the old nav — **identical, route for route**:

```
diff <(old nav routes) <(new nav routes)  →  no differences
```

A `consultant.support` link was drafted and then **removed**: the route exists and is in the same middleware group, but the old nav does not link it. Adding it would be an IA change, and IA changes belong to Phase 5.

`$showRenewalNav` (supplied by `ConsultantAgencyComposer`) still gates the Renewal link.

### Risk R-1 (composers) — resolved by the finder

`ConsultantAgencyComposer` binds to the view **name** `consultant.layouts.app`. The finder swaps the *file* behind that name, so the composer fires and `$showRenewalNav` arrives regardless of theme. `withThemeViews()` covers the namespaced form as well. **No composer change needed.**

### Acting-as banner — correctly out of scope

The "Acting as / Back to Agency Hub" banner lives in `layouts/app.blade.php` (the **company** shell), not the consultant shell — a consultant acting on a client sees the *company* portal. That is Phase 5 work. The consultant topbar shows `company_name`, matching what `header-context.blade.php` renders for `portal=consultant`.

### Phase 0 dead-code cleanup

With a full themed shell in place, the `@theme('new')` asset block Phase 0 added to `consultant/layouts/app.blade.php` can never execute — the finder serves the themed file instead. Removed.

`resources/views/consultant/layouts/app.blade.php` is now **byte-identical to pre-redesign**.

**The same cleanup is pending** for `layouts/app.blade.php` and `admin/layouts/app.blade.php` when their themed shells are built in Phases 4 and 5.

### Remaining Phase 3 work

The shell is done; the 5 consultant **pages** still fall back to their existing views inside the new shell:

| Sub-phase | Scope | Status |
|---|---|---|
| 3.1 | Consultant shell | **DONE** |
| 3.2 | Dashboard | Fallback |
| 3.3 | Clients / workspace switcher | Fallback |
| 3.4 | Packs / orders / documents | Fallback |
| 3.5 | Profile / intro-requests | Fallback |

This is the fallback mechanism working as designed — the portal is fully usable with a new shell and old page bodies.

---

## 16. Phase 3 — Hotfix: fallback CSS dropped from the shell

Found in production testing **2026-08-26** on `/consultant/dashboard?theme=new`. Shell rendered correctly; the page body was an unstyled wall of text.

### Root cause — I violated my own risk R-3

R-3 in this document says: *"Fallback pages lose colors (no Tailwind CDN) — mitigation: fallback wrapper keeps old CSS."*

The new consultant shell loaded **only** `mnz-ui.css`. But consultant page bodies are not migrated yet — they render their existing markup, which depends on:

| Dependency | Used for |
|---|---|
| `portal-enterprise.css` | `ent-kpi-card`, `ent-grid-6`, `ent-page-title`, `ent-label` |
| `portal-design-system.css` | `btn`, `btn-primary`, `btn-sm` |
| `consultant-shell.css` | `cd-notice` |
| `app-shell.css` | base shell rules |
| Tailwind CDN | `flex`, `gap-2`, `mb-4`, `items-start` |

Dropping all five left the KPI grid, buttons, and notices completely unstyled.

**A themed shell must keep the old stylesheets until its page bodies are migrated.** They are removed per-portal at the end of that portal's phase, not when its shell is built.

### Three bugs fixed

**1. Fallback CSS restored.** All four stylesheets plus the Tailwind CDN and Inter font now load *before* `mnz-ui.css`, so the shell still wins. Verified: the old stylesheets contain **zero** `mnz-` occurrences, so no collision is possible.

**2. Duplicate page heading.** My shell rendered `@yield('page-title')` in a `.mnz-pagehead`. The consultant shell it replaces **ignores** `page-title` — consultant pages render their own `<h1 class="ent-page-title">` inside `content`. Every heading appeared twice. Removed from the shell.

**3. CSS token collision.** `consultant-shell.css` defines `body.consultant-portal { background-color: var(--canvas); color: var(--ink) }`, whose specificity beats `mnz-ui.css`'s bare `body` **regardless of load order**. Both it and `app-shell.css` also define `--ink` / `--canvas` on `:root` with different values than `mnz-ui.css`.

Fixed two ways:
- Dropped `consultant-portal` from `<body>` — old page bodies do not need it; their `ent-*` and `.btn` rules are class-scoped and still apply.
- Re-asserted the MENetZero 2.0 token values in the shell's inline `<style>`, which loads last.

### Not a bug: blue charts

The chart colours (`#3b82f6`, `#60a5fa`, `#93c5fd`) are **hardcoded in `consultant/dashboard.blade.php`**, pre-dating the redesign. They will be restyled when the dashboard body is migrated in 3.2. Chart.js itself is pushed by the page via `@push('head')`, and the new shell provides all three stacks (`styles`, `head`, `scripts`), so charts render correctly.

### Rules for Phases 4 and 5

1. **A themed shell keeps the old stylesheets** until every page body in that portal is migrated.
2. **Check what the shell being replaced actually renders.** The consultant shell ignores `page-title`; the company and admin shells *do* render it. Copying one shell's behaviour to another duplicates or drops headings.
3. **Watch `body.<portal-class>` rules.** `body.consultant-portal` and `body.company-portal` beat bare `body` on specificity. Omit the class or override explicitly.
4. **`--ink` / `--canvas` are defined by three stylesheets** with different values. Re-assert them in the shell's inline style.

---

## 17. Phase 4 — Admin Portal: shell record

Shell completed **2026-08-26**. **Zero existing files modified. Zero route changes** (still 372).

### Files added (2)

| File | Purpose |
|---|---|
| `themes/new/admin/layouts/app.blade.php` | Admin shell — topbar, sidebar, page head, flash messages |
| `themes/new/admin/partials/nav.blade.php` | Sidebar nav — 18 routes, 7 sections |

### The four §16 rules, applied

This is the first shell built *after* the Phase 3 hotfix, and each rule changed the outcome:

| Rule | Applied |
|---|---|
| **1. Keep old stylesheets** | `app-shell.css`, `portal-design-system.css`, Tailwind CDN, Inter font — all before `mnz-ui.css`. Verified admin pages use `card` (33×), `table`, `btn`, `btn-ghost`, `btn-xs`, all defined in the two kept stylesheets. |
| **2. Check what the old shell renders** | Admin **does** render `page-title` in its header, and admin pages do **not** render their own `<h1>` — the opposite of the consultant portal. The shell renders it. Dropping it would have lost every heading. |
| **3. Watch body classes** | `bg-slate-50` is a Tailwind utility with no specificity trap, but it is dropped anyway — the shell paints its own background. |
| **4. Re-assert tokens** | `app-shell.css` defines `--ink` / `--canvas` on `:root`; re-asserted in the shell's inline `<style>`, which loads last. |

Rule 2 is the one that would have caused a visible bug had it not been recorded — the consultant and admin shells behave in exactly opposite ways here.

### Flash messages preserved

The old admin shell renders a `.flash-stack` block for `session('success')` / `session('error')` above the content. Admin controllers rely on it. Reproduced as `.mnz-flash` in the same position.

### Nav parity verified

```
diff <(old nav routes) <(new nav routes)  →  no differences
```

All 18 routes, all 7 sections. Two links leave the admin portal and are preserved with a `↗` marker: **Pricing page** (public, `target="_blank"`) and **Client Portal** (`client.dashboard`).

`$isActive` uses the same `str_starts_with` prefix logic as the original.

### Palette

`data-pillar="neutral"` makes admin read neutral-ink — distinct from company green and consultant blue.

### Phase 0 dead-code cleanup

`admin/layouts/app.blade.php` is now **byte-identical to pre-redesign**, matching the Phase 3 cleanup.

**Remaining:** `layouts/app.blade.php` still carries its Phase 0 block, to be removed when the company shell is built in Phase 5.

### Remaining Phase 4 work

Shell done; all 51 admin **pages** still fall back to their existing views inside the new shell:

| Sub-phase | Scope | Status |
|---|---|---|
| 4.1 | Admin shell | **DONE** |
| 4.2 | Dashboard / statistics | Fallback |
| 4.3 | Companies / users | Fallback |
| 4.4 | Consultants / orders | Fallback |
| 4.5 | Subscriptions / price book / packages | Fallback |
| 4.6 | Site content / email templates / emissions | Fallback |

---

## 18. Phase 5.0 — Decision validation against code

Run **2026-08-26** before writing any Phase 5 route, per the plan's 5.0 gate.

### D1 — `/environmental/targets` → reduction targets: **VALID**

Two distinct controllers exist, both `DisclosureBaseController` subclasses with an `index(Request)` signature:

| Controller | Surface |
|---|---|
| `ReductionTargetController` | `/disclosures/ifrs-s2/targets` — climate reduction targets, uses `ReductionTargetProgressService` |
| `EsgSustainabilityTargetController` | `/disclosures/esg-targets` — broader ESG targets |

D1 selects the reduction/climate surface. Directly aliasable.

### D2 — `/environmental/boundaries` → list then select: **VALID, and the only option**

`EmissionBoundaryController@index(Location $location)` takes a **route-model-bound Location**. A param-less `/environmental/boundaries` cannot call it.

D2's "list → select location → boundaries" is therefore not a preference but the **only workable form**. The new route lists locations and links each to the existing nested URL.

Also noted: the controller resolves company via `$user->getActiveCompany()`, not `$user->company_id`, so consultant-acting workspaces work. Any new route must preserve that.

### D3 — `/governance/policies` → S1 governance section: **VALID**

`config/disclosure.php` defines an S1 `governance` section titled **"Sustainability Governance"**. The section key is `governance`, so `SectionController@editS1` can be reached with that default.

### D4 — risk registers stay separate: **VALID — and cheaper than expected**

`github.md` claimed `sustainability-risks` has 4 columns against `climate-risks`' 7, and that merging needs three fields added. **That claim is wrong.**

Verified against both models and `2026_06_09_200000_phase2_ifrs_s1_disclosure_tables.php`:

```
ClimateRisk:        company_id fiscal_year name risk_type time_horizon
                    description financial_impact likelihood mitigation owner status
SustainabilityRisk: company_id fiscal_year name topic     time_horizon
                    description financial_impact likelihood mitigation owner status
```

The schemas are **identical except for the discriminator** — `risk_type` (climate) vs `topic` (sustainability). The 4-vs-7 difference is in what the **views display**, not what the tables store.

**Consequence: D4 requires no migration whatsoever.** A unified Governance risk UI can read both registers and present them together today. The only remaining Phase 5 database work is the Phase 6 `theme` column.

This removes the last item that was ever marked BLOCKED in this document.

### Net effect on Phase 5

| Item | Before validation | After |
|---|---|---|
| D1 | Assumed aliasable | Confirmed |
| D2 | "needs context design" | Confirmed as the only form; `getActiveCompany()` constraint noted |
| D3 | "needs a default" | Section key confirmed: `governance` |
| D4 | Feared 3-field migration | **No migration needed** |

---

## 19. Phase 5.1 — Company Portal shell + price-book fix

Completed **2026-08-26**. **Zero route changes** (still 372).

### The company shell is the most stateful of the four

It carries shell state the other portals do not. All of it is preserved:

| State | Purpose |
|---|---|
| `isConsultantActing` | Consultant working inside a client workspace |
| `consultantReadOnly` | Review-only workspace |
| `consultantActingEngagement` | PRY for the acting banner |
| `consultantSwitchableClients` | Managed-client switcher |
| `accessibleCompanies` | Multi-company switcher |
| `companyRenewalNudge` | Renewal banner (from `PlanGateComposer`) |
| Flash alerts | `x-alert` component, success/error |
| Guest branch | `@auth('web')` / `@else` — content renders bare |

The **acting-as bar** is rendered full-width in amber above the topbar, with "Switch client" and "Back to Agency Hub". It must never be missable: a consultant editing the wrong client's inventory is a data-integrity problem.

### Two bugs caught during the build

**1. `getAccessibleCompanies()` returns arrays, not models.**

```php
$companies->push(['id' => ..., 'name' => ..., 'role_name' => ..., 'company' => $model]);
```

I first wrote `$company->id`, which would have been a **fatal error** on every multi-company user's page load. The original uses `$company['id']`. Fixed and commented.

Worth noting the method mixes shapes — `'company' => $ownedCompany` is a model nested inside an array — so this is easy to get wrong again.

**2. Chart.js load position.** The original loads it in `<head>`. I initially placed it before `</body>`. Company pages call `new Chart(...)` from body-level scripts, so a bottom load would run too late and break every dashboard chart. Moved to `<head>`.

### Nav deliberately reused

The shell includes `layouts.partials.nav-client` **unchanged** — 21 KB of plan-gated navigation. Replacing it with the six-tab IA is 5.3+ work, not shell work. Doing both at once would make a nav regression indistinguishable from a shell regression.

### §16 rules applied

| Rule | Company portal |
|---|---|
| 1. Keep old CSS | `app-shell`, `portal-design-system`, `portal-enterprise`, Tailwind, Inter — plus `@stack('styles')` in its original position between them |
| 2. page-title | **Not rendered** — company shell ignores it, like consultant, unlike admin |
| 3. Body class | `company-portal` dropped |
| 4. Tokens | Re-asserted |

### Phase 0 cleanup complete

`layouts/app.blade.php` is now byte-identical to pre-redesign. **All four layouts are clean** — every existing view file in the repo matches pre-redesign except `admin/price-book/index.blade.php`.

### Price-book `§` fix

Reported as "why am I seeing § across the site". Investigated: **not a bug and not caused by the redesign.** `§` is the section sign, used correctly to cite IFRS clauses (`config/disclosure.php`) and pricing-sheet sections.

The real defect was the notes input at `min-w-[14rem]`, too narrow for its content, so `xlsx §5 up to 3 sites…` clipped to `xls §5 up to 3 sites` and read as a stray symbol.

| Change | Detail |
|---|---|
| Notes input | `min-w-[14rem]` → `min-w-[26rem]`, plus `title` attribute for full text on hover |
| Section headings | `(xlsx §5)` → `(pricing sheet, section 5)`; `(§6.2)` → `(pricing sheet, section 6.2)` |

`§` in `config/disclosure.php` is **left alone** — `IFRS S2 §5–7` is correct regulatory citation.

### Remaining Phase 5 work

| Sub-phase | Scope | Status |
|---|---|---|
| 5.0 | Validate D1–D4 | **DONE** (§18) |
| 5.1 | Company shell | **DONE** |
| 5.2 | Overview / dashboard | Fallback |
| 5.3 | Environmental + six-tab nav + Quick Input | Not started |
| 5.4 | Social | Not started |
| 5.5 | Governance (unified risk UI, no migration per D4) | Not started |
| 5.6 | Reports | Not started |
| 5.7 | Settings / Team / Billing / Profile | Not started |
| 5.8 | Internal states | Not started |

**5.3 is where the ~28 new routes land.** The shell must be confirmed working first — a nav regression on top of an unverified shell would be hard to attribute.

---

## 20. Phase 5.3 — Six-tab IA: routes and navigation

Completed **2026-08-26**. Route count **372 → 399**, exactly the figure projected in §3.8.

### Routes added (27)

| Group | Count | Middleware |
|---|---|---|
| `/environmental/*` | 9 | company stack; `disclosureAccess` on the 4 disclosure-backed routes |
| `/social/*` | 4 | company stack + `disclosureAccess` |
| `/governance/*` | 5 | company stack + `disclosureAccess` |
| `/settings/*` | 4 | company stack; `restrictManagedClientBilling` on billing |
| `/reports/*` | 5 | company stack + `disclosureAccess` |

`/dashboard`, `/reports` and `/settings/reporting` are **reused, not re-registered** — as the matrix specified.

### Why `disclosureAccess` is mandatory, not optional

`DisclosureBaseController::resolveContext()` calls:

```php
$this->requirePermission('disclosures', 'view', [['reports', 'view']]);
```

An alias without the same gate would 403 inconsistently, or worse, present a route the `/disclosures/*` group would have blocked. Every alias to a `DisclosureBaseController` subclass carries it.

### Decisions implemented

| Decision | Implementation |
|---|---|
| D1 | `/environmental/targets` → `ReductionTargetController` |
| D2 | `/environmental/boundaries` → `LocationController@index` (list → select → existing nested URL), since `EmissionBoundaryController@index` requires a bound Location |
| D3 | `/governance/policies` → `SectionController@editS1` with `->defaults('section', 'governance')` |
| D4 | `/governance/risks` → `SustainabilityRiskController`, registers kept separate, **no migration** |

### Non-negotiables verified

| # | Requirement | Result |
|---|---|---|
| 2 | `/disclosures/*` intact | **Zero** removed lines |
| 3 | No `/v2` | Zero occurrences |
| 10 | Company middleware stack + order | All new routes inside the existing group |
| 12 | `/measurements` untouched | Zero diff lines |
| 13 | `/quick-input` ordering | Zero diff lines |

Duplicate route names checked: `social.overview` and `gov.overview` are distinct despite both leaves being `overview`.

### The six-tab nav — and a bug that would have leaked paid features

`themes/new/layouts/partials/nav-client.blade.php` renders the six tabs with contextual sub-navs, and highlights the active tab whether the user arrived via a new 2.0 route **or** an existing `/disclosures/*` URL.

**I initially wrote gating against an invented API**: `$planGate->allows('disclosures')`. Both halves were wrong:

- `PlanGateComposer` shares **`$gate`**, not `$planGate`
- `PlanGate` has **no `allows()` method** — its API is `isScope3Locked()`, `canBulkImport()`, `canExport()`, and similar

Worse, nav gating is **permission-based, not plan-based**. The old nav computes `$canViewDisclosures`, `$canViewReports`, `$canViewQuickInput`, `$canViewLocations`, `$canViewStaff`, `$canViewRoles` from `hasPermission()` / `hasModulePermission()`.

An undefined variable in Blade is null, so `$can(...)` would have evaluated **truthy by default** — showing every disclosure link to every user regardless of permission. That is risk R-1 realised: exactly the silent, invisible failure the plan warned about.

Fixed by reproducing the permission block **verbatim** from the old nav. All 25 route names in the nav were then verified to exist.

### Remaining Phase 5 work

| Sub-phase | Scope | Status |
|---|---|---|
| 5.0–5.3 | Validation, shell, routes, nav | **DONE** |
| 5.2 | Overview / dashboard body | Fallback |
| 5.4–5.7 | Social / Governance / Reports / Settings bodies | Fallback |
| 5.8 | Internal states (onboarding, bulk-import mapping, empty, error) | Not started |

All tabs are navigable now; their pages render existing bodies inside the new shell.

---

## 21. Phase 5.3 — Hotfix: orphaned `$tab` variable

Found in production **2026-08-26**: `Undefined variable $tab` — a 500 on `/dashboard`, theme `new`.

### Root cause

While fixing the invented-`allows()` gating (§20), I replaced the nav's whole `@php` block. The original block defined `$tab` via a `match(true)` expression to track the open top-level tab. The replacement block did not — but one reference survived at line 69:

```blade
class="mnz-nav {{ $tab === 'o' ? 'is-active' : '' }}"
```

Every other link had already been converted to `$routeName`-based checks; this one was missed.

**Fixed:** `{{ $routeName === 'client.dashboard' ? 'is-active' : '' }}`, matching the pattern used by every other link in the file.

### Why static checks missed it

Balance checks (`@if`/`@endif`, braces, parens) all passed — the file was structurally valid. Nothing in the earlier verification looked for *variables referenced but never defined*.

The irony is instructive: an undefined variable in Blade is normally **silently null**, which is exactly why the `$can()` bug in §20 was dangerous (it would have evaluated truthy and shown gated links). Here PHP 8.3 raised an `ErrorException` instead, because the value was used in a strict `===` comparison rather than a boolean test. **The same class of bug is silent in one position and fatal in another.**

### New standing check

An undefined-variable scan now runs across every theme file:

1. Strip Blade comments (so commented mentions do not count)
2. Collect definitions from `@php` blocks, inline `@php(...)`, `@foreach ... as $x`, and closure parameters
3. Add the composer-shared set (`$gate`, `$companyRenewalNudge`, `$activeTheme`, `$isNewTheme`, `$themeAssets`)
4. Flag anything used but never defined

Run across all 16 theme files: **clean**. Two flagged references in the nav were verified as false positives — `$planGate` appears only inside a comment, `$prefixes` is a closure parameter.

**This check must run before every future phase is reported complete.** Balance checks alone are not sufficient for Blade.

---

## 22. Phase 5.2 — Overview (company dashboard)

Completed **2026-08-26**. **First migrated page body** in the whole redesign — every prior phase delivered shells only.

### Files

| File | Change |
|---|---|
| `themes/new/dashboard/partials/enterprise.blade.php` | **New** — redesigned Overview |
| `dashboard/partials/enterprise-scripts.blade.php` | **New** — chart config, shared by both themes |
| `dashboard/partials/enterprise.blade.php` | **Modified** — 126-line script block replaced by an include |

### Chart config extracted rather than duplicated

The original partial carried 126 lines of Chart.js configuration in a `@push('scripts')` block. Copying it into the themed partial would have created two copies that drift — a chart fix applied to one theme and not the other.

Instead it is extracted **verbatim** into `dashboard/partials/enterprise-scripts.blade.php`, which **both** partials include. Verified byte-identical against `c02534a`:

```
diff <(git show c02534a:…enterprise.blade.php | sed -n '262,387p') \
     <(sed -n '11,136p' …enterprise-scripts.blade.php)   →  identical
```

The extraction is behaviour-neutral for the old theme: same content, same `@push('scripts')`, same position in the render.

**Canvas ids are load-bearing.** The shared script binds by `#monthlyEmissionsChart` and `#emissionsByScopeChart`; both themes render those exact ids. Any future theme rendering the Overview must too — noted in the partial's header.

### Data contract unchanged

The redesigned Overview consumes exactly the controller's existing `compact()` payload — 14 variables. **No controller change** (P3 holds). Six sections: page head with year filter, KPI row, net zero progress, charts, compliance, recommendations.

Two details preserved deliberately:
- **Boundary-change warning** — absolute emissions across years with a different organisational boundary are not like-for-like (GHG Protocol Ch.5). Carried over in full.
- **Trend direction semantics** — falling emissions are *good*, so a negative trend gets the positive colour. Inverting this would misreport performance.

### Verification

| Check | Result |
|---|---|
| Undefined-variable scan (§21), all 17 theme files | **CLEAN** |
| `mnz-` classes used vs defined in `mnz-ui.css` | 35 used, **0 missing** |
| Blade balance, 3 touched files | OK |
| Canvas ids, view vs script | Match |
| `co2e_t()` helper | Exists in `app/helpers.php`, autoloaded via composer `files` |
| Script extraction vs pre-redesign | **Byte-identical** |

The §21 scan flagged `@push` in a Blade comment — same false-positive class as `$planGate` earlier. Comment reworded so the scan stays clean.

### State

Existing files modified since pre-redesign: **2** — `admin/price-book/index.blade.php` (the `§` fix) and `dashboard/partials/enterprise.blade.php` (script extraction, behaviour-neutral).

---

## 23. Phase 5.7 — Settings (reporting methodology) + automated pre-flight checks

Completed **2026-08-26**.

### Why Settings came before 5.4–5.6

Settings is the smallest remaining cluster and the highest-consequence-per-line: `settings/reporting.blade.php` is a 13-field form whose values are **disclosed alongside the inventory**. A dropped `min`, `step` or `maxlength` is silent data corruption, not a visual glitch.

### Form contract verified field by field

Checked against `CompanyReportingSettingsController::update()`'s 15 validation rules **and** the original view, with a multi-line-aware attribute comparison:

| Check | Result |
|---|---|
| Field names | 13 old / 13 new, **0 missing, 0 extra** |
| `type`, `step`, `min`, `max`, `maxlength`, `rows`, `required` | **All 12 fields match exactly** |
| `old()` bindings | Preserved, including `old('scope3_reason.'.$cat, …)` |
| Scope 3 array fields | `scope3_included[]` and `scope3_reason[{cat}]` unchanged |

Nothing was "tidied". `step="0.0001"` on the intensity denominator and `maxlength="40"` on its unit label are load-bearing — they match the controller's `numeric` and `max:40` rules.

### Automated pre-flight checks

The `$tab` failure (§21) and the `allows()` failure (§20) were both in the same family: **things static balance checks cannot see**. Those checks are now a script, `.claude/scripts/check-theme-views.py`:

1. **Undefined variables** — silently null in a boolean test, fatal in a strict comparison
2. **`mnz-` classes used but never defined** — the Phase 3 unstyled-page failure
3. **Blade directive balance**

It resolves classes from `mnz-ui.css` **and** each shell's inline `<style>`, since layouts legitimately define their own.

**Run it before reporting any phase complete.** Current state: **18 files, 0 problems.**

### A real bug the checker caught

`mnz-side__title` — the section labels in the six-tab nav — was defined in the consultant and admin shells but **not the company shell**. Every "Environmental" / "Social" / "Governance" label in `nav-client` would have rendered as plain body text.

This is the same failure mode as the Phase 3 unstyled dashboard, caught **before** a screenshot rather than after. Added to the company shell.

### Verification

| Check | Result |
|---|---|
| Pre-flight script, 18 theme files | **0 problems** |
| Form field parity | 13/13, attributes identical |
| `mnz-` classes in settings view | 19 used, 0 missing |
| Blade balance | OK |

### Remaining Phase 5 work

| Sub-phase | Status |
|---|---|
| 5.0, 5.1, 5.2, 5.3, 5.7 | **DONE** |
| 5.4 Social bodies | Fallback |
| 5.5 Governance bodies | Fallback |
| 5.6 Reports bodies | Fallback |
| 5.8 Internal states | Not started |

Note 5.7's other three screens (Profile, Team, Billing) are route aliases into existing views and still fall back — only the reporting-methodology page has a themed body.

---

## 24. Phase 5.4 — Social + a parity checker that found real regressions

Completed **2026-08-26**. Two page bodies migrated; the pre-flight checker extended and, in doing so, **nine genuine regressions caught that no screenshot would have revealed**.

### Files added (3)

| File | Purpose |
|---|---|
| `themes/new/layouts/partials/nav-disclosures-esg-depth.blade.php` | ESG depth sub-nav, shared by 5.4/5.5 pages |
| `themes/new/disclosures/stakeholders/index.blade.php` | Stakeholder register |
| `themes/new/disclosures/supply-chain/index.blade.php` | Supplier register |

Form contracts verified field-for-field against `StakeholderEngagementController::validateEngagement()` (6 fields) and `SupplyChainSupplierController::store()` (9 rules). **6/6 and 8/8 field names match**, routes match, and every action keeps `['fiscal_year' => $fiscalYear]` — without it a record silently lands in the wrong reporting year.

`scope3_category` is validated by the supply-chain controller but has **no input in the view being replaced**. Not added: Phase 5 reproduces the existing contract, and adding a field is a behaviour change.

### The checker now compares against the original

Three new comparisons, each catching something real:

| Check | Found |
|---|---|
| **Form fields dropped** | none |
| **`x-field-help` dropped** | **8** — 6 on stakeholders, 2 on supply chain |
| **Routes dropped** | **9** on `nav-client`, 1 on the dashboard |

**The `x-field-help` loss is the kind of regression that ships.** Those components render contextual guidance from `config/portal-guide-*`. A themed page without them looks perfect in a screenshot and is quietly worse to use. All 8 restored; both pages now 7/7.

### Nine destinations that would have become unreachable

The six-tab nav had silently dropped links the old nav carries:

| Restored | Where it went |
|---|---|
| `disclosures.s1.overview`, `disclosures.s2.overview` | Reports |
| `disclosures.esg-dashboard`, `disclosures.esg-depth.overview` | Social |
| `quick-input.bulk-import.index` | Environmental → Measure |
| `client.consultants.index` | Settings — **revenue-facing marketplace** |
| `subscriptions.billing` | Settings (was pointing at `subscriptions.index`) |
| `admin.dashboard` | Settings, admin-only escape hatch |

Every route stayed registered, so nothing 404'd — they were simply **unreachable from the UI**, which is exactly the failure a route-count check cannot see.

### One real omission on the dashboard

The Overview had lost the **emissions-intensity** line under current emissions — the `$currentIntensity` value, and its "Set an intensity denominator" link when unset. Intensity is how growth-adjusted performance is read; a company that added sites can still be improving per unit. Restored.

### Deliberate omission, declared

The old nav expands **every emission source** as its own `quick-input.show` link — dozens of entries. The six-tab IA replaces that with one Measure link. No destination is lost; sources are chosen on-page.

The checker honours a `DELIBERATE OMISSION` marker in a view's header comment, so an intentional change is declared in the file rather than silently tolerated.

### Checker hardening

Three false-positive classes fixed so its output stays actionable:

- Controller payload variables **derived from source** rather than hand-listed — the list cannot go stale
- `<meta name="viewport">` is not a form field
- A shell's routes may legitimately live in its nav partial

**Current state: 21 files, 0 problems.**

---

*Sections 1–6 are the plan. Sections 7, 7A, 9 record approved decisions.*
