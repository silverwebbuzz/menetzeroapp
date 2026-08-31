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
| 5 | Company | **5.0–5.5, 5.7 DONE** — 5.6 surveyed, 5.8 pending |
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

## 25. Phase 5.5 — Governance

Completed **2026-08-26**. Three page bodies migrated. Checker clean throughout: **24 files, 0 problems**.

### Files added (3)

| File | Notable contract |
|---|---|
| `themes/new/disclosures/materiality-matrix/index.blade.php` | Bulk-edit nested array |
| `themes/new/disclosures/sasb/index.blade.php` | Plan-gated CSV export |
| `themes/new/disclosures/sustainability-risks/index.blade.php` | Per-row inline edit + delete forms |

### Materiality — the nested-array trap

The form posts `topics[{key}][impact_materiality]`, `[financial_materiality]` and `[is_material]`, read by `syncMaterialityMatrix()`.

`{key}` is the **topic key from the controller's `$topics` array**. Re-indexing it — using `$loop->index`, or renaming the loop variable — would write each value against the wrong topic, with no error. Materiality drives which topics appear in the report, so this is a silent-corruption risk. The loop key is used verbatim.

### SASB — plan gating preserved

The CSV export stays wrapped in `x-plan-gated-link` with `$gate->canDisclosureExportType('sasb_index', $fiscalYear)`. A bare link here would hand a paid export to every tier — **risk R-1 in its most direct form**.

Both `PlanGate` methods verified to exist (`canDisclosureExportType`, `disclosureExportMessage`), and the component's `lockedClass` prop confirmed before use, after the `allows()` episode in §20.

### Risks — reusing a partial rather than rewriting it

This page uses `disclosures.partials.header`, not the ESG-depth sub-nav. That partial carries the **reporting-year selector** and depends on `ReportingYearsComposer`, with fallback logic for companies that have no history yet.

**Reused unchanged.** Rewriting it would risk silently changing the user's reporting year — a worse failure than an unstyled control.

The add and edit forms expose only `name`, `topic`, `time_horizon`, `description`. The controller validates five more (`financial_impact`, `likelihood`, `mitigation`, `owner`, `status`) that the UI never sends. **Reproduced as-is** — adding fields is a behaviour change and belongs to a feature phase.

### D4 in practice

Confirmed again while building: the sustainability register stays separate from `climate_risks`. Their schemas are identical apart from the discriminator, so the unified Governance risk presentation needs **no migration**.

### Verification

| Page | Fields | Routes | field-help | Plan gates |
|---|---|---|---|---|
| Materiality | 1/1 | ✓ | ✓ | n/a |
| SASB | 1/1 | ✓ | ✓ | **1/1 preserved** |
| Risks | 5/5 | ✓ | ✓ | n/a |

Nested-array field names were compared structurally (`topics[][]`) rather than literally, so a re-indexing bug would still surface as a mismatch.

### Remaining

| Sub-phase | Status |
|---|---|
| 5.0–5.5, 5.7 | **DONE** |
| 5.6 Reports bodies | Fallback |
| 5.8 Internal states | Not started |

---

## 26. Phase 5.6 — Reports: findings before implementation

Survey completed **2026-08-26**. **Implemented 2026-08-26** — see §26.6 below.

### Why this page is different from 5.2/5.4/5.5

`reports/index.blade.php` is **654 lines** — larger than all five previously migrated bodies combined — and unlike them it carries its own asset blocks:

| Region | Lines | Contents |
|---|---|---|
| `@push('styles')` | 5–122 | ~117 lines of page CSS (accordion transitions, card overrides) |
| `@section('content')` | 124–536 | Form, three gated exports, results |
| `@push('scripts')` | 537–654 | ~117 lines of page JS |

### Contract to preserve

| Item | Detail |
|---|---|
| Form | `GET` → `reports.show`; `fiscal_year` (required), `location_id` (required), `moccae_only` checkbox |
| Plan gates | **3 × `x-plan-gated-link`** — PDF, Excel, IEQT exports |
| Cross-link | `client.profile` — logo upload tip for PDF branding |

**The three gated exports are the highest-risk item on the page.** PDF, Excel and IEQT are paid capabilities; rendering any as a bare link hands it to every tier (risk R-1, same shape as the SASB export in §25).

### Recommended approach when resumed

Follow the §22 precedent: the Overview's 126-line chart block was **extracted to a shared partial** included by both themes, rather than duplicated. The same applies here — the ~117-line script block should move to `reports/partials/index-scripts.blade.php` so report logic cannot drift between themes. The CSS block is theme-specific and should **not** be shared; the themed view gets its own.

### Status

| Sub-phase | Status |
|---|---|
| 5.0–5.5, 5.7 | **DONE** |
| 5.6 Reports | **Surveyed, not implemented** |
| 5.8 Internal states | Not started |

---

*Sections 1–6 are the plan. Sections 7, 7A, 9 record approved decisions.*

---

### 26.6 Phase 5.6 — Reports: implementation record

Completed **2026-08-26**. Three files touched.

| File | Status | Lines |
|---|---|---|
| `resources/views/reports/partials/index-scripts.blade.php` | **new** (shared) | 136 |
| `resources/views/themes/new/reports/index.blade.php` | **new** (themed body) | 523 |
| `resources/views/reports/index.blade.php` | modified — extraction only | 654 → 542 |

#### The shared script partial

Following the §22 precedent, the ~117-line script block was extracted **verbatim**
to `reports/partials/index-scripts.blade.php` and is now `@include`d by both themes,
so report logic cannot drift between them. Verified byte-identical to the original
(`diff` modulo the 4-space `@push` indent). The old view's diff is **3 insertions,
116 deletions** — pure extraction, no behaviour change.

The **CSS block was deliberately not shared.** It is presentation, not logic; the old
theme keeps its `.card-body` / `.report-kpi` block and the new theme gets its own
`.rpt-*` block. Sharing it would have coupled the two designs.

#### Chart.js version trap — documented in the partial

Both shells load Chart.js **3.9.1** in `<head>`, but this page pushes an *unpinned*
`chart.js` tag that resolves to **v4**, plus `chartjs-plugin-datalabels@2`. The
override is what makes `ChartDataLabels` work. Both tags were carried across
verbatim and the partial header warns against "tidying" them away — removing them
silently drops the plugin and the chart loses its labels.

#### DOM contract the shared script depends on

The partial documents, and the themed body honours, the exact hooks the script binds:
`.accordion-header[data-target]`, `.accordion-body` toggled via `hidden`,
`.accordion-icon`, `#analysisPieChart`, and `#btnScope` / `#btnEmission`.

**The toggle buttons keep `btn-primary` / `btn-secondary` alongside their `mnz-btn`
classes.** `setActiveButton()` adds and removes those two exact class names. Dropping
them for pure `mnz-` classes would have left the active-state toggle a no-op — the
chart would still switch, but neither button would ever look selected.

#### Plan gating — the highest-risk item, verified

All three exports (PDF, Excel, IEQT) are paid capabilities. Each stays wrapped in
`x-plan-gated-link` with its original `:allowed` / `:message` / `:locked-title` /
`:locked-href` expressions. Verified mechanically against `c02534a`:

- `route()` calls — **identical set**
- `<x-…>` components — **identical set and counts** (incl. `x-preview-only-banner`, `x-export-readiness-banner`)
- `name="…"` form fields — **identical set**
- `$gate->canExport(…)` — **4 call sites, same order, same arguments** (a raw grep shows 7 in the themed file; the 3 extra are the header comment, not code)

The preview-only overlay, the trial-watermark overlay and both banner branches were
carried across, re-skinned as `.rpt-watermark` but with the same `$previewOnly` /
`$trialWatermarked` conditions.

#### Composer coverage

`reports.*` was already registered for both `PlanGateComposer` and
`ReportingYearsComposer`, and `withThemeViews()` expands it to `theme-new::reports.*`.
No `AppServiceProvider` change was needed. Note the themed view resolves via the
**finder prepend** under the view name `reports.index`, which the unexpanded pattern
already covers — belt and braces.

#### Verification

- Pre-flight checker: **25 files, 0 problems**
- All 19 CSS custom properties used resolve in `mnz-ui.css` or the shell
- All 23 `mnz-` classes used are defined
- Blade directives balanced (`@section=3 / @endsection=1` is correct — two are the
  two-argument inline form, matching the source view)
- Repo diff outside the new theme: **one file**, the extraction described above

#### Still needs runtime confirmation by the user

- The scope/source chart toggle and the accordion, on a report with real data
- The locked state of all three exports on a tier that cannot download
- Export-readiness blocking on the MOCCAE PDF and IEQT buttons

---

## 27. Phase 5.8 — Internal states

Completed **2026-08-26**, with two items deliberately scoped out (see 27.4).

### 27.1 The survey premise was wrong — corrected here

`github.md` listed Internal — onboarding and Internal — empty + error states as
"NEW — no equivalent views in the repo." Verified against the code, that is only
partly true, and the difference changes the work:

| Item | `github.md` claim | Verified reality |
|---|---|---|
| Onboarding | "no view exists" | **Exists.** It is the `$needsCompanySetup` branch of `dashboard/index.blade.php` (lines 6–299, 413 lines of Tailwind) plus a redirect-and-flash flow through `EnsureOnboardingComplete` → `locations.create`. Not a missing page. |
| Error pages | "no equivalent views" | **Correct.** There is no `resources/views/errors/` at all. 403/404/419/500 render Laravel framework defaults today. 71 `abort()` calls exist across the controllers. |
| Bulk import | "NEW: the column-mapping step" | **Correct.** `quick-input/bulk-import.blade.php` exists (213 lines, two upload forms — Scope 1&2 and Scope 3). The mapping step is genuinely new. |

### 27.2 Onboarding needs no work — and why

`dashboard/index.blade.php` line 301 includes `dashboard.partials.enterprise`,
which the finder prepend already resolves to the themed version. So an onboarded
user is **already** on the new dashboard; the setup branch is what a brand-new
user sees instead.

That branch is raw Tailwind. It renders correctly in the new shell **only because
§16 rule 1 was applied when that shell was built** — `themes/new/layouts/app.blade.php`
loads the Tailwind CDN and all three portal stylesheets (lines 42–46). Verified.
The setup form therefore works in both themes; it simply looks like the old design.

That is a cosmetic inconsistency, not a breakage, and re-skinning a 413-line form
with live `/api/industries` and `/api/subcategories` cascades is a body migration
in its own right — not an "internal state." **Deferred, not forgotten.**

### 27.3 Error pages — built for both themes

Six new files under `resources/views/errors/`. Laravel resolves these by
convention; no route or config change was needed.

| File | Purpose |
|---|---|
| `layout.blade.php` | Standalone shell — see the constraint below |
| `partials/home-action.blade.php` | Guard-aware "where do I send this user" link |
| `403` `404` `419` `500` `503` | The pages themselves |

**The load-bearing constraint: these must NOT extend `layouts.app`.**
`layouts/app.blade.php` line ~339 calls `auth()->user()->isAdmin()` **unguarded**.
An error page rendered for a guest — a 404 on a public URL, or a 419, which by
definition means the session is gone — would throw a *second* fatal error while
rendering the first, producing a blank page or an error loop. So the shell is
fully standalone: no auth calls, no composers, no CDN, no external CSS or JS,
all styling inline. It renders identically for guests and signed-in users, and
whether or not the theme session exists.

**419 gets a different action on purpose.** The session is dead, so linking to a
dashboard would bounce straight back to login and lose the explanation. It offers
sign-in and a back link instead.

**`@elseauth` was avoided deliberately.** It was in the first draft, but there is
no `vendor/` directory here to confirm the directive against and no precedent for
it anywhere in this codebase. On the one page whose entire job is to not fail, an
unverifiable directive is not worth the elegance — `home-action.blade.php` uses
plain `Auth::guard(...)->check()` instead.

**Route names were verified, not assumed.** The first draft linked to
`consultant.dashboard` and `admin.dashboard`; **neither exists**. A bad `route()`
call would have thrown inside the error page. Those two now use `url('/consultant')`
and `url('/admin')`. Only `client.dashboard` and `login` are called by name, and
both were confirmed present in `routes/web.php`.

The `@hasSection` / `@else` / `@endif` structure matches the existing precedent in
`layouts/portal-auth.blade.php` lines 61–65.

### 27.4 Scoped out of this phase, by decision

**Bulk-import column mapping — deferred as a feature.** Confirmed with the user.
Phases 0–5 have been strictly additive *presentation* work. Column mapping needs
new routes, a controller, session state for the parsed upload, and validation-
preview logic against `Scope12BulkImportService::HEADERS` / `Scope3BulkImportService`.
That is new backend surface with its own failure modes; it should be built as its
own scoped piece after the redesign, not folded into a view-migration phase.

**Onboarding re-skin — deferred** for the reason in 27.2.

Neither is blocked. Both are recorded so they are not silently dropped.

### 27.5 Verification

- Every `route()` call in `errors/` resolves to a real named route (`client.dashboard`, `login`)
- No `auth()->user()->` chains anywhere in `errors/` — safe for guests
- Nothing in `errors/` extends `layouts.app`
- Blade directives balanced in all six files
- Pre-flight checker: **25 files, 0 problems** (the checker covers `themes/new/`; the error pages are theme-neutral and were verified separately, as above)
- Repo diff outside the new theme: **`resources/views/errors/` only** — purely additive

### 27.6 Needs runtime confirmation by the user

- Hit a bad URL while **signed out** → expect the branded 404, not a blank page
- Leave a form open until the session expires, then submit → expect the 419 page
- Confirm `APP_DEBUG=false` in production, otherwise 500 shows the debug trace rather than `errors/500`

---

## 28. Phase 2 — Emails

Completed **2026-08-26**. Deferred to last at the user's request; this closes the
final build phase before switch-over.

### 28.1 The survey premise was wrong again — corrected

`github.md` said: *"EmailTemplateService.php (welcome exists; the other five are new)."*
That is not what the code does. The real system is considerably better developed:

- **14 transactional templates** are configured in `config/emails.php`, not one.
- Templates are **DB-backed with config fallback** — `EmailTemplateService::resolve()`
  reads `email_templates` by slug and falls back to `config('emails.templates.*')`.
- Admins can **edit template bodies** (`Admin\EmailTemplateController`, `body_html`).
- A **plain-text part already exists** (`emails/template-text.blade.php`), so the
  "add a plain-text part" item was already done.

So the work was not "write five new templates." It was: improve the one shared
chrome that all of them render through.

### 28.2 Why the wrapper was the whole job

Every transactional email renders through `resources/views/emails/template.blade.php`.
**Five callers** use it:

| Caller | Purpose |
|---|---|
| `TemplateMail::build()` | all 14 configured templates |
| `ContactInboundMail` | inbound contact form |
| `RawTestMail` | test sends |
| `Admin\EmailTemplateController::preview()` | the admin preview |
| `Admin\EmailTestController` | template test harness |

Restyling that one file improves every transactional email at once — and because
the admin preview renders the same view, the preview stays accurate for free.

This is option **A** from §14, and the survey there recommended it.

### 28.3 What changed, and what deliberately did not

**Changed — the chrome only.** The previous wrapper laid out with `<div>`s and a
`<style>` block. Outlook on Windows renders through Word, which ignores much of
that — `max-width` and `border-radius` especially — so the email lost its 600px
column and ran the full window width. The new wrapper is table-based with inline
styles (the standard Outlook-safe pattern) plus an MSO conditional to pin the width,
and a `@media` query so phones get full-bleed.

**NOT changed — the body.** `{!! $bodyHtml !!}` is admin-editable content. Verified
before touching anything: **zero `class="` across all 14 templates** — every shipped
body styles its own elements inline. The chrome therefore owns no body styling, and
this rewrite cannot alter template content.

**The `<style>` block was kept deliberately**, even though the chrome no longer needs
it. Admins can edit `body_html`, so a stored body may still reference `.body` or
`.footer`. Removing the block would silently restyle their content. It is now a
compatibility shim, not the layout mechanism — and it is commented as such.

**One genuine behaviour change:** the preview text was emitted inside an HTML
comment (`<!-- preview: … -->`), which many clients ignore. It is now a proper
hidden preheader div — the pattern clients actually read for the inbox preview
line. It is escaped with `{{ }}` over `strip_tags` and capped at 140 chars.

### 28.4 Verification

- Blade variables — **identical set** to the original (`diff`)
- `config()` keys — **identical set** to the original (`diff`)
- `{!! $bodyHtml !!}` still unescaped — body renders as authored
- Directives balanced; `<table>` / `<tr>` / `<td>` balanced; 3 MSO conditionals opened and closed
- Wrapper **parses as well-formed HTML** with no unclosed tags (directives stubbed, then HTML-parsed)
- `\Illuminate\Support\Str::limit` matches existing precedent in two other views
- Original preserved at `scratchpad/template-orig.blade.php` for comparison

### 28.5 Needs runtime confirmation by the user

Static checks cannot verify how a mail client renders. Before relying on this:

1. Send a test through **Admin → Email templates → preview / test send**.
2. Check one in **Outlook on Windows** specifically — that is the renderer this
   rewrite targets, and the only way to confirm the 600px column holds.
3. Confirm the inbox **preview line** now shows body text rather than nothing.
4. Spot-check one template with a DB-edited body, if any exist, to confirm the
   compatibility shim covers it.

---

## 29. Phase 6 — Switch-over: Tier 2 opt-in

Completed **2026-08-26**. `THEME_DEFAULT` is deliberately **still `old`**.

### 29.1 Coverage reality — read this before flipping anything

Measured, not estimated:

| | Count |
|---|---|
| Themed views total | 25 |
| — shells, navs, partials (chrome) | 9 |
| — auth pages | 9 |
| — **real page bodies** | **7** (+1 dashboard partial) |
| Non-theme page views in the app | ~209 |

So roughly **4% of page bodies are themed.** Everything else renders an *old
body inside the new shell*.

Verified that this actually works rather than assuming it:

- Non-themed pages `@extends('layouts.app')`, which the finder resolves to the **new** shell.
- Their `card` / `btn` / `form-control` / `table` classes still resolve, because §16 rule 1 kept the old stylesheets loaded in the new shell.
- **Navigation coverage is complete**: the new nav links 33 routes vs the old nav's 24 — a superset, no gaps. (A naive diff shows `scope`/`slug` "missing"; both are `request()->route('slug')` calls, not links.)

The result is coherent and shippable, but visually mixed. **That is why the
default was not flipped** — the user chose Tier 2 opt-in over a global flip.

### 29.2 Tier 2 — the per-company opt-in

`ThemeResolver::current()` had a reserved Tier 2 slot since Phase 0. It is now live:

```
1. Session      ?theme= choice (unchanged)
2. Company      NEW — the company's opt-in
3. Config       themes.default ('old')
```

**No migration was needed.** `companies.settings` already exists as a nullable
JSON column cast to array, and is **completely unused** — verified nothing in the
app reads or writes it. (`CompanyReportingSettingsController` writes a *different*
model on its own table.) Storing the opt-in there satisfies requirement 8: do not
modify DB structures unnecessarily. `Company::themePreference()` /
`setThemePreference()` merge into that array rather than replacing it.

### 29.3 Two problems found and fixed during implementation

**A performance regression I was about to introduce.** `current()` runs on every
web request through `ResolveTheme`, and can be called several times per request
(middleware, provider, Blade directives). `User::getActiveCompany()` is **not
memoized** and issues a `Schema::hasTable()` plus a query. Tier 2 would therefore
have added repeated DB round-trips to every page load. Fixed with a per-request
memo (`false` = unresolved, `null` = no preference) written on **every** return
path. Confirmed `ThemeResolver` is bound as a **singleton**, so the memo holds;
and `account.switch` is a POST + redirect, so the next request rebuilds the
container and the memo cannot go stale across a company switch.

**Fail-open, not fail-closed.** `companyTheme()` is wrapped in `try/catch
(\Throwable)` and guarded with `method_exists()`. It runs on guest requests and
on the admin and consultant guards, where no company exists. Anything unexpected
degrades to the config default rather than throwing — a failure here would take
down every page in the product.

### 29.4 The admin control

`Admin\CompanyThemeController` + `POST /admin/companies/{company}/theme`
(`admin.companies.theme`), placed inside the existing **`ensureSuperAdmin`** group.
A new controller rather than an edit to `SuperAdminController`, so the whole
switch-over surface can be removed in one step later.

The UI is a card on the company detail page offering *Follow default* /
*MENetZero (current)* / *MENetZero 2.0*. Verified `$company` there is a real
`Company` model (`Company::with([...])`), not an array — the same bug class as
the `getAccessibleCompanies()` fatal in §20, so it was checked rather than assumed.

**This is the only theme UI in the product.** The standing constraint — *normal
users must never see a "Switch Theme" option* — is intact: the control lives in
super-admin company administration, and nothing company- or consultant-facing
exposes it.

### 29.5 Verification

- Route count **399 → 400** (exactly the one new route)
- Pre-flight checker: **25 files, 0 problems**
- Brace balance verified on all three PHP files; Blade directives balanced in `show.blade.php`
- `\Throwable` written correctly (the Python heredoc could have mangled the escape)
- Existing behaviour unchanged: with no opt-in set, `current()` returns the config default exactly as before

### 29.6 The remaining rollout — user-driven from here

1. **Now:** opt one internal company in via the admin card; use it for real work.
2. **Then:** opt in a friendly client or two. Feedback on the *mixed* state matters most — that is what 96% of pages look like today.
3. **Before any global flip:** migrate the high-traffic bodies still on old markup — `quick-input/*` (daily data entry), `locations/*`, `profile`, `subscriptions/billing`, `roles`. Flipping `THEME_DEFAULT` while coverage is ~4% would put every user in the mixed state at once.
4. **Flip:** `THEME_DEFAULT=new` — one env value, instantly reversible.
5. **Kill-switch, unchanged:** `THEME_SWITCH_ENABLED=false` disables `?theme=` immediately, no deploy.

**Still outstanding for the user (D7):** production nginx / Cloudflare rules are
outside the repo and only the user can verify that `?theme=new` is not stripped
or cached across users. A shared cache that ignores the query string would serve
one visitor's theme to another — worth checking before opting in any real client.

---

## 30. Body migration — Quick Input entries

Completed **2026-08-26**. The first of the high-traffic bodies identified in §29.6,
and the highest-value one: daily emission data entry.

| File | Status | Lines |
|---|---|---|
| `resources/views/quick-input/partials/source-icon.blade.php` | **new** (shared) | 57 |
| `resources/views/themes/new/quick-input/index.blade.php` | **new** (themed body) | 447 |
| `resources/views/quick-input/index.blade.php` | modified — extraction only | 449 → 403 |
| `.claude/scripts/check-theme-views.py` | modified — two false-positive patterns | +6 |

### 30.1 Shared partial — the icon map

48 lines of SVG paths keyed on `quick_input_slug`, extracted **verbatim**
(`diff`-confirmed) to `quick-input/partials/source-icon`. Duplicating it per theme
would guarantee drift as emission sources are added. The old view's diff is
**2 insertions, 48 deletions** — pure extraction, no behaviour change. §22 precedent.

### 30.2 Dependencies verified before writing

**Alpine.js** drives bulk selection (`x-data`, `x-for`, `x-show`, `x-cloak`,
`x-model.number`, `@click`, `@submit`, `@change`). Checked, not assumed:

- Both shells load Alpine 3.x from CDN — the new shell at line 299.
- **`[x-cloak]` is defined in `app-shell.css`**, which the new shell loads. Without
  that rule the bulk-actions bar flashes visible on every page load before Alpine
  initialises.

### 30.3 Gating preserved — verified mechanically

Diffed against the original: `route()` calls, `name="…"` form fields and
`<x-…>` components are all **identical sets**. `$gate` call sites match one-for-one
once the header comment is excluded (a raw grep over-counts because the comment
documents the same names):

| Gate | Sites | Guards |
|---|---|---|
| `canHelpGuide` / `helpGuideMessage` | 1 | Help-guide link |
| `canBulkExport` / `bulkExportMessage` | 1 | CSV export |
| `isScope3Locked` | **2** | Badge, and the whole source grid |
| `isAgencyWorkspace` / `agencyLockedMessage` | 1 | Which upgrade copy shows |
| `upgradeRoute` / `upgradeButtonLabel` | 1 | Upgrade CTA |

The Scope 3 lock is the sharp one: it replaces the entire source grid with an
upgrade callout. Rendering the grid unlocked would hand Scope 3 data entry to
every tier (risk R-1).

`$canDeleteEntries` has **exactly 4 live sites** — select-all column, bulk bar,
per-row checkbox, and the empty-state `colspan` (10 vs 9). `@csrf` ×2 and
`@method('DELETE')` ×2 match the original, so both destructive forms are intact.

### 30.4 One real bug caught by the checker

`is-selected` and `qi-source__icon` were both referenced but **undefined**.
Neither would have thrown — the Alpine `:class` binding would have set a class
that styles nothing (selected rows showing no highlight), and the icon partial's
`<svg>` would have rendered at intrinsic size and blown out the grid. Both are the
invisible-in-a-screenshot kind of regression the checker exists to catch. Fixed by
defining both in the page's `@push('styles')` block.

### 30.5 Two checker false positives — fixed in the checker, not worked around

The checker flagged `$event` and `$matches` as undefined. Both were verified
against the original view and are legitimate:

- **`$event`** is Alpine's magic inside `@click` / `@submit` / `@change` JS strings — never a Blade variable.
- **`$matches`** is `preg_match()`'s third argument, which PHP **assigns by reference**; the checker only recognised `$x = …` as a definition.

Both patterns are now understood by `check-theme-views.py`, so future migrations
do not hit spurious failures. Suppressing them per-file would have hidden the
same class of genuine bug later.

### 30.6 Verification

- Routes / form fields / `<x-…>` components — **identical sets** to the original
- `$gate` and `$canDeleteEntries` call sites — one-for-one
- Alpine directives — identical once the header comment is excluded
- All `mnz-`, `qi-` classes and CSS variables resolve
- Blade directives balanced (`@section=3 / @endsection=1` is the two-argument inline form, as elsewhere)
- Pre-flight checker: **26 files, 0 problems**

### 30.7 Needs runtime confirmation by the user

- **Bulk delete** — select rows, confirm the count in the dialog matches, and that only those rows go
- The bulk bar must stay hidden on load (the `x-cloak` path)
- Scope 3 lock on a tier without Scope 3 — expect the upgrade callout, **not** the source grid
- A user without delete permission — expect no checkboxes, no bulk bar, and a 9-column empty state

### 30.8 Remaining high-traffic bodies

Still on old markup, in rough order of traffic: `quick-input/show` (767 lines — the
data-entry form itself, and the largest single body in the app), `locations/*`,
`profile`, `subscriptions/billing`, `roles`. §29.6's advice stands: migrate these
before flipping `THEME_DEFAULT`.

---

## 31. Body migration — Quick Input entry page (`quick-input/show`)

Completed **2026-08-26**. The largest body in the app, and the first migration
done as a **partial re-skin** rather than a full one.

| File | Status | Lines |
|---|---|---|
| `resources/views/quick-input/partials/entry-form.blade.php` | **new** (shared, verbatim) | 551 |
| `resources/views/themes/new/quick-input/show.blade.php` | **new** (themed chrome) | 305 |
| `resources/views/quick-input/show.blade.php` | modified — extraction + hoist | 768 → 265 |
| `.claude/scripts/check-theme-views.py` | modified — guarded-variable rule | +25 |

### 31.1 Why the form was deliberately NOT re-skinned

Confirmed with the user before writing any code. `public/js/quick-input.js` is
**1,360 lines** and is coupled to this markup far more tightly than any page
migrated so far:

- binds **~35 element ids** (`amount`, `quantity`, `unit`, `fuel_category`, `vehicle_type`, `scope2_method`, `calculate-btn`, `calculation-result`, …)
- **traverses** `.form-group-stacked` / `.form-group-horizontal` / `.form-group` with `closest()` and `querySelectorAll()` to show and hide fields
- **injects** `.field-error` and reads `.form-help-text` at runtime — those classes appear **0×** in the view because the JS creates them
- posts to `/api/quick-input/calculate`, which produces **the emission numbers users save**

Re-skinning would break field show/hide, validation display and the calculation
preview — and those failures surface as **wrong numbers**, not as visible layout
breaks. So the form region was extracted **verbatim** to a shared partial that
both themes include, and the themed page still loads `public/css/quick-input.css`.

The chrome — header, year/location selector, results table, layout — is themed.

Plan gating lives inside the shared partial (the Scope 3 limit branch uses
`isAgencyWorkspace()`, `agencyLockedMessage()`, `upgradeRoute()`,
`upgradeButtonLabel()`), so it is preserved automatically and **cannot drift**.

### 31.2 A real regression my own extraction introduced — caught by the checker

`$editFuelCategory`, `$editFuelType`, `$editProcessType` and `$editUnit` are
defined at original line 161 — **inside the region I extracted** — but the five
hidden `*_initial_value` inputs that read them live in the parent, after the
include.

`@include` receives a **copy** of the parent scope; variables set inside a partial
do not flow back out. Left alone, those inputs would have rendered empty and
`quick-input.js` would have lost its edit-mode initial values — **silently
resetting fuel category, fuel type, process type and unit whenever a user edited
an entry**. No error, no visible breakage, wrong saved data.

Fixed by hoisting the four definitions into **both** parent pages ahead of the
include, leaving the partial's own copy untouched so it stays verbatim.

### 31.3 A pre-existing bug found, documented, and deliberately NOT fixed

`$industryLabel` is built in `QuickInputController` (line ~282) but is **absent
from the `compact()`** at line ~364, so it never reaches the view. Because the
original reads it inside a `??` chain, PHP suppresses the notice and it falls back
to `$emissionSource->instructions`.

Net effect, **in the live app today**: the industry-specific description, the
"Common Equipment" panel and the "Typical Units" chip are **dead code that never
renders**. Reproduced exactly rather than silently fixed — fixing it would change
what the live page shows, which is a product decision, not a migration one.

**Worth raising separately:** someone wrote three UI features that have never
appeared. Adding `'industryLabel'` to the controller's `compact()` is a one-word
change that would switch them all on.

### 31.4 Checker improvement — narrowed, not suppressed

The checker flagged `$industryLabel`. Rather than add a per-file suppression, it
now understands the actual rule: **a name reached only through `??` / `?->` /
`isset()` / `empty()` cannot cause a fatal.** The rule is block-aware — uses
inside an enclosing `@if(isset($x))…@endif` are recognised as guarded, which a
naive line-based check missed.

Verified the narrowing did not blunt the check: injecting a genuinely unguarded
`{{ $totallyBogusVar->name }}` is still reported. Removed after the test.

### 31.5 Verification

Because the form moved into a partial, the themed page was verified **composed**
(themed chrome + shared partial) against the pre-extraction original:

- **element ids — identical set** (this is what `quick-input.js` binds)
- **routes — identical set**
- **form field names — identical set**
- `$gate` calls, `<x-…>` components, `@csrf` / `@method` — identical (the only count differences are header comments)
- **8 `resolved-field-help` includes and 3 `x-field-help`** — all preserved (the §20 bug class)
- structural classes the JS traverses — unchanged in live markup
- extraction verified **byte-identical** by `diff`; parent diff is **4 insertions, 522 deletions**
- Pre-flight checker: **27 files, 0 problems**

### 31.6 Needs runtime confirmation by the user

This page carries more runtime risk than any other in the migration:

1. **Edit an existing entry** — confirm fuel category, fuel type and unit are pre-filled (this is exactly what 31.2 would have broken).
2. **The calculate button** — confirm the preview returns a number and it matches what saves.
3. **Field show/hide** — pick a vehicle source and toggle "know amount of fuel"; fields should appear and disappear as before.
4. **Validation** — submit an invalid value and confirm the inline error still appears under the field.
5. Scope 3 limit on a Free plan — expect the upgrade panel instead of the form.

### 31.7 Remaining bodies

`locations/*`, `profile`, `subscriptions/billing`, `roles` — all conventional
server-rendered pages with no comparable JS coupling, so they should migrate at
the pace of §30 rather than this one.

---

### 31.8 POSTMORTEM — ParseError in production

**Reported by the user** on `/quick-input/1/refrigerants?edit=4&...`:
`ParseError: syntax error, unexpected end of file` at
`quick-input/partials/entry-form.blade.php:536`. My extraction shipped broken.

#### Two defects, both mine

1. **A duplicated `@if`.** During the re-extraction in §31 I rebuilt the partial
   with `sed -n '1,29p'` to keep the header, but the header was 28 lines. Line 29
   — the region's opening `@if(($scope3LimitReached ?? false) && !$editEntry)` —
   was kept as part of the "header" **and** re-added by the region append. Result:
   the directive opened twice and closed once.

2. **Directive names inside a `{{-- --}}` comment.** My header comment contained
   the literal text `28 @if/@endif`. **Blade compiles directives before it strips
   comments**, so those names are counted by the compiler. This is what made the
   file look balanced to my own check while being unbalanced to Blade.

#### Why my verification missed it

Three compounding gaps, worth stating plainly:

- I ran the balance check on the **comment-stripped** body. Blade does not strip first, so my check disagreed with the compiler in exactly the case that mattered.
- I ran that check on the **region before prepending the header**, so the header's directive names were never counted at all.
- **The checker never scanned `partials/`.** It globs `resources/views/themes/**` only. Every shared partial the migration created — `entry-form`, `source-icon`, `index-scripts`, `enterprise-scripts` — was outside its scope. The one file class introduced by this project was the one class never checked.

#### Fixes applied

- Partial rebuilt cleanly from the pristine original; verified **byte-identical** to lines 99–620 and balanced 28/28.
- All six quick-input views verified with a stack-based simulation of Blade's own pairing: **all balanced**.
- Checker now performs directive balance on **raw source**, matching the compiler.
- Checker now scans **shared partials outside `themes/`** (directive balance only — a partial legitimately uses variables its parent defines).
- Rule refined for two real Blade forms it initially misread: `@php(...)` single-line takes no `@endphp`, and `@hasSection` / `@sectionMissing` close with `@endif`.
- **Verified the fix catches the real bug:** reintroducing one unpaired `@if` in the comment now reports `29 @if vs 28 @endif`. Reverted after the test.

#### Rule for the remaining migration

**Never write a Blade directive name inside a Blade comment.** The compiler counts
it. The partial's header now says so explicitly, in place of the text that broke it.

---

### 31.9 SECOND POSTMORTEM — `unexpected token "else"`

Reported on `/quick-input/1/natural-gas?edit=28&...` after the §31.8 fix.
Same file, **different defect**. My §31.8 fix was incomplete.

#### The defect: I cut the region at the wrong boundary

The original structure was:

```
line  98   @if($selectedLocationId && $selectedFiscalYear && $measurement)
line  99     @if(($scope3LimitReached ?? false) && !$editEntry)   <-- form region starts
line 604     @endif                                               <-- form region ends
line 605   @else                                                  <-- belongs to line 98
line 620   </div>
line 621   @endif                                                 <-- closes line 98
```

I extracted **99–620**, which swallowed the parent's `@else` branch — the
"Action Required" prompt shown when no year/location is selected. The partial
then contained an `@else` with no enclosing `@if`.

**The correct region is 99–604.** The caller owns the condition and its
else-branch.

#### Why §31.8's fix did not catch it

The §31.8 check counted open/close pairs. **The counts still balanced** — one
`@else` is neither an open nor a close. Counting can never detect a misplaced
branch; only a stack walk can. I shipped a fix for the symptom I had seen
rather than for the class of defect.

#### Fixes applied

- Partial rebuilt from lines **99–604**; verified structurally valid.
- The `@else` branch restored **verbatim** to `quick-input/show.blade.php`, so the old theme again shows "Action Required". The new theme already had its own themed equivalent.
- **Composed** verification (parent + partial vs the pre-extraction original) for **both** themes: element ids, routes, form field names and field-help includes all **identical sets**.
- The partial's header now documents the ownership boundary explicitly.

#### The checker was rebuilt, not patched

Counting is gone. It now walks directives with a **stack**, on raw source,
reporting: a close that does not match the innermost open, a branch
(`@else` / `@elseif` / …) with no branchable block open, and anything unclosed
at EOF. It handles the single-line `@php(...)` and two-argument `@section(...)`
forms, `@hasSection` closing with `@endif`, and `@empty` as a `@forelse` branch.

**Scope widened to the whole repo.** Both ParseErrors came from files *outside*
`themes/`, which the checker never scanned. It now checks Blade structure on all
**254** views (227 non-theme + 27 themed).

#### That widened scan immediately found a third latent crash

`resources/views/errors/partials/home-action.blade.php` — written in Phase 5.8 —
named `@auth/@elseauth` inside its header comment. Blade counted them, leaving an
**unclosed `@auth`** and a stray `@elseauth`.

**Every 403, 404, 419 and 500 would have thrown a ParseError while rendering the
error page.** It had not surfaced only because no error page had been hit since.
Fixed; all seven error views verified clean.

#### Verified against both real bug shapes

Reintroduced each defect and confirmed the checker reports it, then reverted:

| Bug shape | Reported |
|---|---|
| orphan `@else` (natural-gas crash) | `line 546: @else with innermost open = NOTHING` |
| directive name in a comment (refrigerants crash) | `unclosed @auth opened at line 4` |

#### Standing rules

1. **Never write a Blade directive name inside a Blade comment.** The compiler counts it.
2. **An extracted partial must be a self-contained, balanced block.** If the caller owns a condition, it owns that condition's branches too.
3. **Verify structure with a stack, never with counts.** Counts are blind to misplaced branches.

---

## 32. Checker hardening after the two ParseErrors

Before resuming migration, the pre-flight checker was extended to cover the
failure classes that actually reached production, plus two adjacent ones.

**Note on limits:** this machine has no PHP CLI and no `vendor/`, so nothing here
truly compiles a template. These checks simulate Blade's compiler closely enough
to catch structural breakage — which is what both crashes were — but they are not
a substitute for loading the page.

| Check | Catches | Scope |
|---|---|---|
| Directive structure (stack walk) | orphan `@else`, mismatched close, unclosed block, directive names in comments | all 254 views |
| `@include` target resolves | "View not found" 500s | all 254 views |
| `@php` brace balance | ParseError from an unclosed `{` | all 254 views |
| Undefined variables | fatal on unguarded deref | 27 themed views |
| Dropped routes / fields / field-help | silent feature loss | 27 themed views |
| CSS class + token defined | invisible styling loss | 27 themed views |

Dynamic includes (`@include('x.' . $step)`) are skipped — they cannot be resolved
statically and are legitimate. One exists: `emission-form/step.blade.php`.

**Each new check was verified against a deliberately introduced fault, then
reverted:**

| Injected fault | Reported |
|---|---|
| orphan `@else` | `line 546: @else with innermost open = NOTHING` |
| `@auth` named in a comment | `unclosed @auth opened at line 4` |
| `@include` of a missing view | `@include target does not exist: '…does-not-exist'` |
| unclosed `{` in `@php` | `line 270: @php block has unbalanced braces (1 open, 0 close)` |

### 32.1 Re-verification of everything already shipped

Both quick-input partials were rebuilt during the postmortems, so the composed
output (parent + shared partial) was re-checked against the pre-extraction
originals for **both themes**:

| Page | Theme | element ids | routes | field names | gate calls |
|---|---|---|---|---|---|
| `quick-input/show` | old | OK | OK | OK | OK |
| `quick-input/show` | new | OK | OK | OK | OK |
| `quick-input/index` | old | OK | OK | OK | OK |
| `quick-input/index` | new | OK | OK | OK | OK |

Current state: **254 views, 0 structural problems; 27 themed views, 0 problems.**

---

## 33. Body migration — Business locations

Completed **2026-08-26**. One new file; **no existing file modified**.

`resources/views/themes/new/locations/index.blade.php` (206 lines), from a
165-line original.

### 33.1 Straightforward, unlike the last two

No external JS, no plan gating (verified: the original has zero `$gate` calls),
no shared-partial extraction needed. This is the pace §31.7 predicted for the
remaining bodies.

### 33.2 Dependency checked before writing

The row overflow menu uses Alpine (`x-data` / `x-show` / `x-transition` /
`@click.away`) and the `.dropdown-menu` / `.dropdown-item` classes.

`portal-design-system.css` scopes its dropdown rules to `.app-shell` /
`.consultant-shell`, and **the new shell sets neither**. That would have been a
silent styling loss. It is not a problem here because `app-shell.css` defines
`.dropdown-menu` / `.dropdown-item` **unscoped** and the new shell loads it — but
rather than depend on that, the themed page styles its own `.loc-menu` with theme
tokens.

**Worth remembering for later bodies:** any old markup relying on an
`.app-shell`-scoped rule will lose it in the new theme. Grep
`portal-design-system.css` for `.app-shell ` before assuming a class carries over.

### 33.3 Verification

- routes, `name="…"` fields, `@csrf`, `<x-…>` components — **identical sets**
- Alpine directives — one of each in live markup, matching the original (the raw grep over-counts by 1 because the header comment names them)
- both POST toggles (`locations.toggle-head-office`, `locations.toggle-status`) preserved with their csrf tokens
- Pre-flight checker: **28 themed views + 227 non-theme views, 0 problems**

Deliberate change: `x-cloak` replaces the original's inline `style="display:none"`
on the menu — same intent, and `[x-cloak]` is defined in `app-shell.css`.

### 33.4 Remaining bodies

`profile`, `subscriptions/billing`, `roles`. All conventional server-rendered
pages.

---

## 34. Body migration — My Profile

Completed **2026-08-26**.

| File | Status | Lines |
|---|---|---|
| `resources/views/profile/partials/index-scripts.blade.php` | **new** (shared) | 136 |
| `resources/views/themes/new/profile/index.blade.php` | **new** (themed body) | 382 |
| `resources/views/profile/index.blade.php` | modified — extraction only | 510 → 404 |

### 34.1 Two scripts extracted, boundaries checked first

The page carried two inline `<script>` blocks. Both are page logic identical in
either theme, so they moved to one shared partial (§22 precedent):

1. **Sector → industry → subcategory cascade** — reads ids `sector`, `industry`, `business_subcategory` and the `data-id` on each option; calls `/api/industries` and `/api/subcategories`.
2. **`showTab()`** — drives the Personal / Company / Password tabs. Called from inline `onclick`, so it must stay a global function.

**Applying §31.9's rule paid off immediately.** My first attempt at the cascade
block took lines 373–486 — which swallowed a stray `@endif` and a closing `</div>`
belonging to the surrounding markup. That is precisely the defect that caused the
second production ParseError. Checking the block for self-containment *before*
extracting caught it; the true boundaries are **373–459** and **488–508**.

### 34.2 The tab contract

`showTab()` needs `.tab-button` / `.tab-content`, the `.active` / `.inactive`
pair, and id pairs `{name}-tab` / `{name}-content`. The themed page reproduces all
of them and supplies its own CSS — only the class *names* are shared. Renaming any
of them stops the tabs switching, so both the partial and the themed page document
this explicitly.

### 34.3 Verification

Composed (themed page + shared partial) against the pre-extraction original:

| Dimension | Result |
|---|---|
| routes | SAME |
| form field names | SAME |
| tab + cascade ids | SAME |
| `data-id` attributes | SAME |
| `@csrf` (3 forms) | SAME |
| `showTab()` calls | SAME |
| `/api/*` endpoints | SAME |
| `enctype="multipart/form-data"` | SAME |

The enctype check matters: dropping it would break the **logo upload** silently —
the form would still submit, just without the file.

The old view was also verified composed, and its diff is **2 insertions, 108
deletions** — pure extraction.

Checker: **29 themed views + 228 non-theme views, 0 problems.**

### 34.4 Noted for later

The cascade script here is ~81 lines in common with the one in
`dashboard/index.blade.php`'s setup form, but not identical (107 vs 81 significant
lines). They were **not** merged — after two extraction-caused outages, unifying
two near-identical blocks is not worth the risk mid-migration. Worth revisiting
once the redesign is finished.

### 34.5 Remaining bodies

`subscriptions/billing`, `roles`.

---

## 35. Body migration — Plan & billing

Completed **2026-08-26**. One new file; **no existing file modified**.

`resources/views/themes/new/client/subscriptions/billing.blade.php` (396 lines),
from a 311-line original.

### 35.1 My own §33.4 note was wrong about this one

It listed "`subscriptions/billing`" as a single remaining page. Neither that file
nor that path exists — the name came from `github.md`, which has been unreliable
throughout (see §27.1, §28.1). The real surface is
`resources/views/client/subscriptions/`: **9 files, 1,295 lines** — billing,
upgrade, checkout, current-plan, payment-history, request-package, index and two
partials, all revenue-facing.

**Scope decision (user):** migrate `billing.blade.php` only. It is the sole
nav-reachable page in the cluster — verified, both the old and new nav link
`subscriptions.billing` and nothing else from that group. The other eight sit
deeper in the upgrade funnel and keep rendering old bodies inside the new shell,
which works correctly today.

### 35.2 Two showTab() functions now exist, deliberately

`profile/partials/index-scripts` defines a global `showTab()`; so does this page.
**They are not the same function:**

| | profile | billing |
|---|---|---|
| Panel toggle | `.active` on `.tab-content` | `hidden` on `.tab-content` |
| Button state | `.active` / `.inactive` | `.active` + Tailwind colour classes |

They never load on the same page, so this is safe — but it is exactly the sort of
thing that looks like duplication and invites a careless "let's share it". This
page keeps its own copy, and both files say why. Unifying them would require
reconciling two different contracts.

The themed copy drops the hard-coded Tailwind colour utilities
(`border-blue-500` / `text-blue-600`) and drives the same states from the
`.active` class alone, styled with theme tokens.

### 35.3 Revenue-critical elements verified

| Dimension | Result |
|---|---|
| routes (5) | SAME |
| `@csrf` | SAME |
| POST forms | SAME |
| tab ids | SAME |
| `showTab()` calls | SAME |
| `onclick` functions | SAME |
| `@include` targets | SAME |
| controller variables used | SAME |

The **cancel confirmation is byte-identical**, including the interpolated date:

> `Your plan stays active until {{ $subscription->expires_at->format('F d, Y') }} and will not renew. Continue?`

That string is the user's last checkpoint before a paid plan stops renewing;
paraphrasing it would change what they consented to.

The billing-method modal is **already shared and self-contained** — its own
`<script>` defines `openAddBillingMethodModal()` and it owns its ids — so it is
included unchanged by both themes. No extraction was needed or attempted.

Checker: **30 themed views + 228 non-theme views, 0 problems.**

### 35.4 Needs runtime confirmation by the user

- **Cancel at renewal** — confirm the dialog names the right date, and that cancelling schedules rather than terminating
- **Keep my plan** (resume) — only appears when a cancellation is already scheduled
- Both tabs switch, and `session('active_tab')` still lands on the right one after a redirect
- **Add card** opens the modal

### 35.5 Remaining

`roles/` (team & access) is the last item on the §29.6 list. The eight remaining
`client/subscriptions/*` bodies are deliberately unmigrated and recorded here so
they are not mistaken for oversights.

---

## 36. Body migration — Team & Access (roles)

Completed **2026-08-26**. Script extracted first, then all three bodies.

| File | Status | Lines |
|---|---|---|
| `resources/views/roles/partials/index-scripts.blade.php` | **new** (shared, verbatim) | 295 |
| `resources/views/roles/index.blade.php` | modified — extraction only | 723 → 468 |

### 36.1 This page is dual-context — the defining constraint

`roles/*` renders in **both** the client portal and the consultant portal.
`TeamAccessService` supplies:

- `layoutFor()` → `'layouts.app'` or `'consultant.layouts.app'` (hence `@extends($teamLayout ?? 'layouts.app')`)
- `routesFor()` → a **`$teamRoutes` array** that swaps every route name between the two portals

So route names are never hard-coded in these views. **Hard-coding a client route
name would silently break the consultant portal, and vice versa.** All 8 keys are
accounted for: 5 in the body, 3 in the shared script.

Checked before extracting: **neither themed shell renders `@yield('page-title')`**,
and the page provides its own `<h1>`. So one themed body serves both contexts
without the duplicate-heading bug from §16 rule 2.

### 36.2 The script — 256 lines, 11 globals

Extracted **byte-identical** (`diff`-verified) to a shared partial. Parent diff is
**2 insertions, 257 deletions**. It defines `openAddUserModal`,
`closeAddUserModal`, `submitAddUserForm`, `viewUser`, `closeViewUserModal`,
`editUserRole`, `closeEditUserRoleModal`, `resendInvitation`, `cancelInvitation`,
`togglePassword` and `showUpgradeMessage` — all called from inline `onclick`
handlers, so all must stay global.

### 36.3 Accepted limitation: two modals will keep the old look

`viewUser()` and `editUserRole()` **inject their modal markup as JS template
literals** with hard-coded Tailwind classes. They are not static HTML, so the
theme cannot restyle them — those two modals will look old in the new theme,
visible only after clicking View or Edit on a user.

Restyling them means editing JS that drives **role assignment** — the edit-role
modal posts to `$teamRoutes['staff.update_role']`. Confirmed with the user:
leave it. A styling inconsistency is cheaper than a mistake in who can access
what.

### 36.4 Verification so far

- script extraction **byte-identical** to lines 464–720 of the original
- both files structurally valid
- all 8 `$teamRoutes` keys still referenced, split 5 (body) / 3 (script)
- checker: **30 themed views + 229 non-theme views, 0 problems**

### 36.5 What remains for this page

Not yet done, and **not** to be mistaken for finished:

1. `themes/new/roles/index.blade.php` — 467 lines of body (roles grid, staff table, pending invitations, add-user modal). Must keep form fields `email`, `phone`, `notes`, `custom_role_id` and all 7 `onclick` handlers.
2. `themes/new/roles/create.blade.php` — 143 lines (permission matrix).
3. `themes/new/roles/edit.blade.php` — 165 lines.

Each must render correctly under **both** shells, and must read route names from
`$teamRoutes` rather than hard-coding them.

### 36.6 The three bodies — completed

| File | Lines |
|---|---|
| `themes/new/roles/index.blade.php` | 419 |
| `themes/new/roles/create.blade.php` | 153 |
| `themes/new/roles/edit.blade.php` | 175 |

All three `@extends($teamLayout ?? 'layouts.app')` and read route names from
`$teamRoutes`, with the same defensive fallbacks the originals used — so a single
themed body serves both portals.

**Permission matrix (create / edit) — the security-critical part.** The set of
checked boxes *is* the role's grant. Preserved exactly: `name="permission_ids[]"`
on every box, the `.module-checkbox` class the select-all script queries,
`id="selectAll"`, and the view/add/edit/delete column order.

`edit` additionally keeps `@method('PUT')`, the `is_active` toggle, and the
`$selectedPermissionIds` preselection. **That preselection is what shows an admin
the role's current grant** — drop it and every box renders unchecked, so a
careless save would strip the role's permissions.

**Seat-limit gate** on index: `$canAddUser['allowed']` decides between the live
invite button and `showUpgradeMessage()`. Rendering the live button
unconditionally would let a company exceed its paid seat count.

Verified for all three, against their originals: form fields, `$teamRoutes` keys,
`@csrf` / `@method('PUT')`, `.module-checkbox`, `#selectAll`,
`$selectedPermissionIds`, `is_active`, `@extends` expression, `onclick` handlers
and required element ids — **all identical sets**.

### 36.7 Checker gap closed: service-provided view payloads

The checker reported `showConsultantTrialNotice` and `userLimitMessage` as
undefined. Both are real and supplied by `TeamAccessService::viewShared()`
(lines 189/191), which controllers `array_merge` into the view data — so they
never appear in a controller `compact()`.

The checker derived its known-variable list from `app/Http/Controllers/**` only.
It now also scans `app/Services/**`, which fixes this for **any** service that
assembles a view payload this way, not just this one. Verified the widening did
not blunt the check: an injected `$neverDefinedAnywhere` is still reported.

**Checker: 33 themed views + 229 non-theme views, 0 problems.**

### 36.8 Needs runtime confirmation by the user

Test in **both** portals — client and consultant — since one body serves both:

1. **Edit an existing role** — confirm its current permissions come up pre-ticked. This is the §36.6 risk.
2. Save a role and confirm the grant is unchanged when you tick nothing new.
3. **Invite team member** — the modal opens; on a workspace at its seat limit, the button should be inert and show the limit message.
4. View / Edit-role modals still open (they will look old — §36.3).
5. Resend and cancel a pending invitation.

---

## 37. Body migration — ESG dashboard

Completed **2026-08-26**. One new file; **no existing file modified**.

`resources/views/themes/new/disclosures/esg-dashboard.blade.php` (253 lines),
from a 210-line original.

### 37.1 Why this one next

With the §29.6 list finished, the highest-value remaining target is the landing
page for the **E / S / G tabs** — users hit it every time they open Environmental,
Social or Governance. No plan gating and no scripts (verified: zero `$gate` calls,
zero `<script>` blocks), so it is low-risk relative to its visibility.

### 37.2 The shared year-select partial is included unchanged

`disclosures.partials.year-select` is self-contained: its own form, its own PHP
block, options from `$availableYears` via `ReportingYearsComposer`, and a
fallback span for companies with no history. It styles itself with inline
Tailwind, which the new shell still loads — so it **works correctly but keeps the
old look**.

Forking it per theme would duplicate the year-fallback logic that several
disclosure pages depend on. Left shared deliberately.

### 37.3 The checker caught me breaking my own rule

My first draft wrote `@php` inside the header comment while *describing* the
partial. The structural walk failed it immediately:

```
FAIL themes/new/disclosures/esg-dashboard.blade.php
      unclosed @php opened at line 9
```

This is exactly the §31.8 defect that reached production twice. It is worth
recording that the rule is easy to break even when you wrote it — the check, not
the discipline, is what caught it. Fixed by saying "inline PHP block" instead.

### 37.4 Verification

Against the original — all **identical sets**:

| Dimension | Result |
|---|---|
| routes | SAME |
| `@include` targets | SAME |
| `$dashboard[...]` keys | SAME |
| `$t[...]` target fields | SAME |
| `ghg_summary` fields | SAME |
| `scorecard` fields | SAME |
| `$row[...]` fields | SAME |
| target status keys | SAME |

The status keys matter: `achieved`, `on_track`, `off_track`, `missed`, `no_data`,
`incomplete` drive both the chip colour and the progress-bar colour. Dropping one
would silently render an off-track target in neutral styling.

Checker: **34 themed views + 229 non-theme views, 0 problems.**

### 37.5 Remaining unthemed nav destinations

Small disclosure landing pages, all 72–203 lines and the same low-risk shape:
`disclosures/hub` (106), `disclosures/overview` (81), `disclosures/s1-overview`
(72), `disclosures/gri-overview` (101), `disclosures/uae-esg-overview` (121),
`disclosures/esg-scorecard` (203). Then `client/consultants/*` and `help/*`.

---

## 38. Body migration — disclosure hub and IFRS overviews

Completed **2026-08-26**. Three new files; **no existing file modified**.

| File | Lines | From |
|---|---|---|
| `themes/new/disclosures/hub.blade.php` | 122 | 106 |
| `themes/new/disclosures/overview.blade.php` (IFRS S2) | 108 | 81 |
| `themes/new/disclosures/s1-overview.blade.php` | 105 | 72 |

### 38.1 Correction to §37.5

That note said these pages had no gating. **Four of the six do.** Verified
counts: `overview` 2, `s1-overview` 2, `gri-overview` 7, `uae-esg-overview` 4
`$gate` calls — all `canDisclosureExportType` / `disclosureExportMessage`
wrapping `x-plan-gated-link`.

These are **paid disclosure PDF exports**, the same risk class as the SASB export
(§25) and the Reports exports (§26.6). Both migrated pages keep their
`x-plan-gated-link` wrapper and export code intact:

| Page | Export code |
|---|---|
| `overview` | `ifrs_s2_pdf` |
| `s1-overview` | `ifrs_s1_pdf` |

The **Preview** link beside each is deliberately ungated, exactly as in the
originals — only the download is paid.

### 38.2 Shared header partial included unchanged

`disclosures.partials.header` owns the framework label, the reporting-year form
and the disclosure sub-nav, and is self-contained. Included **unchanged** for the
same reason as `year-select` in §37.2: every disclosure overview depends on its
year-fallback logic, and forking it per theme would duplicate that. It keeps the
old look but works correctly.

### 38.3 Verification

Both overviews, against their originals — all **identical sets**: routes, `$gate`
calls, export codes, `<x-…>` components, `@include` targets, `$completeness[…]`
keys, `$item[…]` keys, and the **route-map keys** that link each completeness row
to its editor (`governance`, `strategy`, `risk_management`, plus the
framework-specific ones). A dropped route-map key would render a completeness row
linking to `#`.

`hub` verified on routes, includes and the four `*Completeness` variables.

Checker: **37 themed views + 229 non-theme views, 0 problems.**

### 38.4 Remaining

`gri-overview` (101 lines, **7** gate calls) and `uae-esg-overview` (121 lines,
4 gate calls) — more gating than the two done here, so they warrant their own
pass rather than being batched. Then `esg-scorecard` (203), `client/consultants/*`
and `help/*`.

---

## 39. Body migration — GRI and UAE ESG overviews

Completed **2026-08-26**. Two new files; **no existing file modified**.
These were held back from §38 because they carry the heaviest gating in the app.

| File | Lines | From | Gate calls |
|---|---|---|---|
| `themes/new/disclosures/gri-overview.blade.php` | 158 | 101 | 7 |
| `themes/new/disclosures/uae-esg-overview.blade.php` | 186 | 121 | 4 |

### 39.1 Three distinct gating shapes — and why they must stay distinct

Both pages mix gating styles, and the difference is user-visible:

| Shape | What a disallowed tier sees |
|---|---|
| `x-plan-gated-link` | a **locked button** with an upgrade message |
| outer `@if($gate->…)` | **nothing at all** |

Collapsing the second into the first would change what those tiers are told
exists. That is a product decision already made in the original, so both shapes
were reproduced verbatim rather than "tidied" into one.

**GRI — the Enterprise Index is double-gated on purpose:** an outer `@if` hides
the button entirely, and the inner `x-plan-gated-link` is then passed
`:allowed="true"`. Verified: 4 gated links, exactly 1 `:allowed="true"`.

**UAE ESG — three shapes on one page:**

| Entitlement | Shape | Guards |
|---|---|---|
| `assurance_upload` | outer `@if` | the **whole** assurance card |
| `uae_esg_pdf` | `x-plan-gated-link` | standard PDF |
| `uae_esg_pdf_enterprise` | outer `@if` | Enterprise PDF button |

Verified: 1 gated link vs 2 conditional gates — the counts are what prove the
shapes were not merged.

### 39.2 The assurance upload block

A multipart upload plus a delete form. Preserved: `enctype="multipart/form-data"`,
**both** `@csrf` tokens, `@method('DELETE')`, and the `confirm('Remove assurance
PDF?')` text. Dropping the enctype would break the upload **silently** — the form
still submits, just without the file (same failure mode as the profile logo,
§34.3).

### 39.3 Verification

| Dimension | GRI | UAE ESG |
|---|---|---|
| routes | SAME | SAME |
| `$gate` calls | SAME | SAME |
| export codes | SAME | SAME |
| route-map keys | 12/12 | 8/8 |
| `'section' =>` params | SAME | SAME |
| `<x-plan-gated-link>` count | 4 = 4 | 1 = 1 |
| conditional gates | — | 2 = 2 |
| `@csrf` / `@method('DELETE')` / `enctype` | — | 2/1/1, all SAME |
| `confirm()` text | — | SAME |

Cross-framework route-map entries were checked rather than assumed: GRI's
`gri_305` links to `reports.index`, and UAE ESG's `materiality` links into the
**GRI** section editor while `ghg_inventory` links to `reports.index`. All three
are correct — the frameworks share underlying data.

Checker: **39 themed views + 229 non-theme views, 0 problems.**

### 39.4 Remaining

`esg-scorecard` (203 lines, has gating), `client/consultants/*`, `help/*`.

---

## 40. Body migration — ESG scorecard

Completed **2026-08-26**. One new file; **no existing file modified**.

`themes/new/disclosures/esg-scorecard.blade.php` (270 lines), from 203.

### 40.1 Four gates, four different scopes

The pattern from §39.1 again, but this page has the widest spread of what a gate
actually hides:

| Entitlement | Shape | Hides |
|---|---|---|
| `esg_scorecard` | `x-plan-gated-link` | nothing — shows a **locked** button |
| `esg_scorecard_enterprise` | outer `@if` | the Export Enterprise **button** |
| `hris_kpi_import` | outer `@if` | the **entire HRIS card** |
| `energy_from_activity` | outer `@if` | **one explanatory line** of body copy |

That last one is easy to miss — it is a single `<span>` inside a paragraph,
telling Enterprise tiers that Quick Input energy feeds their export. Verified
present. Counts confirm the shapes were not merged: **1 gated link, 3
conditional gates**.

### 40.2 Four POST forms, two multipart

| Form | Route | enctype |
|---|---|---|
| Sync snapshots | `esg-scorecard.sync` | — |
| Import CSV | `esg-scorecard.import` | **multipart** |
| Import HRIS CSV | `esg-scorecard.hris-import` | **multipart** |
| Save manual metrics | `esg-scorecard.update` | — |

Verified 4 `@csrf` and 2 `enctype`. Dropping an enctype breaks that upload
silently — the form still submits, just without the file (§34.3).

### 40.3 Dynamic field names

The manual-metrics inputs post as `metrics[<row key>]` and repopulate from
`old('metrics.<key>')`. **Both halves must stay in step** — if the input name and
the `old()` path diverge, a validation bounce silently discards whatever the user
typed. Verified both present and matching.

`x-field-help key="scorecard.manual_intro"` preserved — the invisible-in-a-
screenshot loss class from §20.

### 40.4 Verification

routes · `$gate` calls · export codes · form fields · `@csrf` (4) · `enctype` (2)
· POST forms (4) · gated links (1) · conditional gates (3) · `x-field-help` ·
`$scorecard[…]` keys · `$row[…]` keys · `old()` path — **all SAME**.

The `year-select` partial is included unchanged, here carrying
`'hidden' => ['category' => $activeCategory]` so the active tab survives a year
change.

Checker: **40 themed views + 229 non-theme views, 0 problems.**

### 40.5 Remaining

`client/consultants/*` (revenue-facing marketplace) and `help/*`.

## 41. Consultant marketplace (`client/consultants/*`)

Five bodies themed, no existing file modified. This is the revenue-facing
funnel, so tier behaviour and payment contracts were verified, not assumed.

| View | New | Old | Notes |
|---|---|---|---|
| `index` | 181 | 87 | directory + teaser upsell |
| `show` | 203 | 101 | profile, paid pack + free intro |
| `orders` | 97 | 49 | escrow ledger, read-only |
| `checkout` | 111 | 44 | gateway selection form |
| `payment-checkout` | 164 | 100 | 3 payment SDKs |

### 41.1 Two independent layers of name protection

`ConsultantDirectoryService::presentForClient()` already masks the teaser tier
**server-side** — `blurredName()` turns the name into `A•••••z` and nulls bio,
specialties, emirates, experience and contact. The `blur-sm` in the original is
a **second, cosmetic** layer. Dropping it would leak nothing, but the bullet
string would read as corrupt data instead of a deliberate upsell. Kept as
`.cd-name--blur`.

### 41.2 Two booleans the controller currently aliases

`canBookPack` and `canRequestIntro` are both set from `canRequestIntro()` today.
They are read as **two independent flags**, exactly as the original does.
Collapsing them would look equivalent now and break silently the moment the
controller stops aliasing them — and the service already has a distinct
`canSeeFullProfile()` / `canSeeContact()` pair, so divergence is anticipated.

The intro block's `@elseif($level === 'teaser')` is preserved as a real
`@elseif`: a tier that is neither must render **nothing**, not a broken form.

### 41.3 Payment contracts

`payment-checkout` verified **byte-identical** across the 55-line gateway
region (Razorpay / Cashfree / Stripe) via `diff` — extracted with `sed`, never
retyped. All 5 DOM ids (`payBtn`, `razorpayForm`, `rzp_payment_id`,
`rzp_order_id`, `rzp_signature`) and all 11 `@json` calls match.

`checkout` keeps `@checked($loop->first)` and `required` on the gateway radio.
Without the former the form has no default and `required` blocks submit — a
silent conversion drop, not an error. The empty-gateways `@if/@else` is a
guard: it prevents rendering a submittable form with no gateway.

**Currency asymmetry preserved**: `orders` is AED-fixed at the escrow layer;
`payment-checkout` is currency-aware via `CurrencyService::symbol()`.

### 41.4 Three defects caught pre-deploy

1. **Invented CSS classes.** First draft used `mnz-note` and `mnz-link` —
   **neither exists** in `mnz-ui.css`. Silent visual loss. Replaced with the
   panel-based flash convention the disclosure pages use.
2. **`@php` inside a Blade comment** in `payment-checkout` — the exact defect
   behind ParseError #1 and the latent error-page crash. Blade counts the
   directive name, so this would have thrown an unclosed-`@php` fatal **on the
   live payment page**. All directive names are now stripped from all five
   comment headers.
3. **Wrong attribution for `.hidden`.** The comment claimed `app-shell.css`
   defines it; it actually comes from the **Tailwind CDN**. Both shells load
   that CDN so behaviour was correct, but relying on a remote stylesheet to
   hide a payment relay form is fragile — if the CDN is slow or blocked, four
   raw inputs flash on screen mid-payment. Added inline `display:none` as a
   local hide, kept `class="hidden"` for parity.

### 41.5 Verification

274 views (229 non-theme + 45 themed), **0 with problems**. All contract counts
(`route(` · `@csrf` · POST · `name=` · `id=` · `enctype` · `old(` · `@json` ·
`@checked` · `required`) SAME across all five, comments excluded. Route names,
DOM ids and field names compared as **sets**, not just counts — all SAME.

### 41.6 Runtime verification needed

- A **teaser**-tier account: names blurred, "Upgrade to connect", no intro form.
- A **partial+** account: real names, intro form submits.
- A real **Razorpay** payment — confirm the SDK still finds `#payBtn` and the
  relay form submits. This is the one thing static checks cannot prove.

### 41.7 Remaining

`help/*` (3 pages + 3 partials, 547 lines) is the last unthemed nav group.

## 42. Help & guide (`help/*`) — final nav group

Five files themed, one shared partial deliberately left alone.

| View | New | Old | Notes |
|---|---|---|---|
| `company` | 33 | 17 | client shell |
| `consultant` | 32 | 18 | consultant shell |
| `consultant-company-guide` | 38 | 21 | consultant shell, company content |
| `partials/guide-body` | 245 | 154 | the substance |
| `partials/guide-highlight` | 84 | 36 | preview frame |
| `partials/guide-mock` | — | 301 | **SHARED, untouched** |

### 42.1 Scope decision: the 23 mock variants

`guide-mock` is a switch of 23 miniature UI replicas — a fake KPI card, a fake
location row, a fake invite form — each imitating a real screen using
old-theme classes (`ent-kpi-card`, `card`, `btn`, `callout-panel`).

**Decision: share as-is, do not re-skin.** Same call as quick-input's entry
form and the roles script. Re-skinning would duplicate ~300 lines that must be
re-synced every time a real screen changes, and a stale mock never throws an
error — it just quietly shows the wrong UI. Previews look slightly dated
inside the new shell; that is the accepted trade.

**Verified safe**: both themed shells load exactly the same four stylesheets
as their old counterparts (`app-shell`, `portal-design-system`,
`portal-enterprise`, plus `consultant-shell` for the consultant pair), so
every class the mocks use resolves. `cd-pack-card` is consultant-shell-only
and is used only by consultant-portal variants.

### 42.2 Three contracts preserved

**Portal flag is not cosmetic.** `portal` flows page → guide-body →
guide-highlight → guide-mock, where it becomes the mock's *theme*. Drop it and
the company guide starts showing consultant-flavoured previews.
`consultant-company-guide` deliberately pairs a **consultant shell** with
`portal => 'company'` — that mismatch is the point of the page.

**Support route keys off the guard, not the portal.**
`auth('consultant')->check() ? 'consultant.support' : 'client.support'` — a
consultant reading the company guide still gets consultant support.

**`is_file()` runs at render time.** A config entry may name a screenshot not
yet uploaded; the guide silently falls back to a mock. Replacing this with
`!empty($src)` would emit broken images. Preserved with `public_path()`.

### 42.3 Three defects caught pre-deploy

1. **Wrong include convention.** First draft used a literal
   `themes.new.help.partials.*` dotted path. It resolves today but bypasses
   the finder and hard-pins the include to one theme. Switched to the
   registered `theme-new::` namespace, which every other themed include uses.
2. **Lost responsive grid.** Renaming the highlights wrapper to `.hg-mocks`
   dropped `.portal-guide-highlights`, a defined `auto-fit minmax(16rem, 1fr)`
   grid. Multiple previews in one section would have stacked single-column
   instead of sitting side by side. Grid restored and the class re-added.
3. **`@elseif` in a comment** — harmless alone (a branch, not an opener) but
   it violates the standing rule. Removed.

### 42.4 Checker gap fixed

`guide-highlight` was reported `undefined: ['highlight']`. **False positive** —
`$highlight` is an `@include` parameter supplied by both call sites, and the
file guards itself with `@if(!empty($highlight))` on line 1. The checker
harvested variable names from controllers and services but **not from
`@include(..., [...])` arrays in views**, so no shared partial's parameters
were ever recognised.

Fixed by harvesting include-array keys across all views. **Validated both
ways**: injecting `$totallyBogusVar` is still reported, and breaking an
include target is still reported.

### 42.5 Pre-existing, not introduced

`.portal-guide` and `.portal-guide-sections` are undefined in the CSS — in the
**original too**. Inherited no-op hooks, carried across for parity.

### 42.6 Verification

274 views (229 non-theme + 50 themed), **0 with problems**. Parity SAME across
routes · includes · every conditional · `!empty` guards · `is_file` ·
`public_path` · `asset(` · `$loop->first` · `Str::slug` · `array_merge` ·
`site_support_email`. `<details>`/`<summary>` open+close counts, the `open`
attribute and the deep-link `id` all match at element level.

### 42.7 Runtime verification needed

- `/help` in both portals; confirm the first section is expanded and the rest
  toggle (native `<details>`, no Alpine).
- A section with **two or more highlights** — confirm they sit side by side.
- A config entry naming a **missing screenshot** — confirm it falls back to a
  mock rather than a broken image.

### 42.8 Status

**All nav-reachable pages are themed.** Remaining unmigrated work is
deliberate scope: 8 of 9 `client/subscriptions/*` bodies, the onboarding
re-skin, and bulk-import column mapping.

## 43. Rollout runbook (Phase 6 operations)

All nav-reachable pages are themed (§42.8). Building is done; what remains is
operational. This section is the checklist to operate from.

### 43.1 Precedence — and the one trap in it

`ThemeResolver::current()` resolves in strict order:

| # | Tier | Source | Beats |
|---|---|---|---|
| 1 | **Session** | `?theme=` → `mnz_theme` | everything |
| 2 | **Company** | `settings.theme` (admin card) | default |
| 3 | **Default** | `THEME_DEFAULT` env | — |

**THE TRAP.** Session beats company. Anyone who has ever visited `?theme=new`
— every developer and tester — has `new` pinned in their session. If a company
is opted in and then rolled back via the admin card, **those people keep seeing
the new theme and the card looks broken.**

This precedence is deliberate and correct: an explicit `?theme=` must beat a
company default, or testing both themes on one account becomes impossible. It
is a support-response gap, not a bug.

**The fix is one URL:** `/theme/reset` (or `?theme=reset`). `SESSION_LIFETIME`
is 120 minutes, so a stuck override otherwise survives two hours of inactivity
— long enough to outlast a rollback and generate a confusing ticket.

**Before judging any rollback, load `/theme/reset` first.**

### 43.2 Rollout order

1. **Opt one internal company in** — admin card on the company page.
   Confirm from a **fresh session** (not one carrying `?theme=new`).
2. Run §43.3 against it.
3. **Friendly clients**, a few at a time, same card.
4. **Flip `THEME_DEFAULT=new`** once §43.3 is clean.

Reverse at any tier: card → `default`, or env → `old`. Instantly reversible,
no deploy. Master kill-switch: `THEME_SWITCH_ENABLED=false` kills `?theme=`
entirely without a deploy.

### 43.3 Runtime verification — accumulated across all phases

Static checks proved structure and contracts; none of this is provable
statically. **Highest risk first:**

| # | Check | Why it cannot be checked statically |
|---|---|---|
| 1 | **One real Razorpay payment** | 3 SDKs bind `#payBtn` + 4 hidden inputs at runtime |
| 2 | **Edit an entry** — fuel category/type/unit pre-fill | scope-flow regression class (§ entry-form) |
| 3 | **Edit a role** — permissions pre-ticked | same class |
| 4 | Calculate button on quick-input | posts to `/api/quick-input/calculate` |
| 5 | Scope 3 locked on Free | gate shape: locked button vs hidden |
| 6 | Teaser tier on consultant directory | blur + upsell + no intro form |
| 7 | Help: 2+ highlights sit side by side | grid regression caught in §42.3 |
| 8 | Help: missing screenshot → mock, not broken image | `is_file()` at render time |
| 9 | Bulk delete; cancel-at-renewal date | destructive + date arithmetic |
| 10 | A 404 **while signed out** | error pages must not call `isAdmin()` on null |
| 11 | Outlook rendering of the email wrapper | MSO conditional |
| 12 | **`APP_DEBUG=false` in production** | stack traces leak file paths |

### 43.4 D7 — still outstanding, and the risky half

Production nginx/Cloudflare must be verified on two counts:

- **Stripping** `?theme=new` — annoying, obvious, harmless.
- **Caching a themed response and serving it to other users** — this is the
  dangerous one. A cached `theme=new` HTML response served to a user who never
  asked for it makes the rollout look random and uncontrollable.

Vary/cache-key behaviour on the query string must be confirmed **before**
opting in any real client. This is environment configuration and cannot be
verified from the codebase.

### 43.5 Deliberately unmigrated

Not oversights — recorded scope decisions:

- 8 of 9 `client/subscriptions/*` bodies (only `billing` was in scope)
- Onboarding re-skin (the 413-line `$needsCompanySetup` branch)
- Bulk-import column mapping (deferred as a feature, not a redesign)

All fall back to the old shell automatically (requirement 11) — they render,
they just look like the old app.

### 43.6 Open product decision, unrelated to the redesign

`$industryLabel` is built in `QuickInputController` (~line 282) but **absent
from its `compact()`** (~line 364). Inside `??` chains PHP suppresses the
notice, so the industry description, the "Common Equipment" panel and the
"Typical Units" chip are **dead code that has never rendered in production.**

Adding `'industryLabel'` to the `compact()` is a one-word change that switches
on three built-but-invisible features. That is a product decision, not a
redesign fix — flagged, not taken.

## 44. `$industryLabel` — the one-word fix (NOT a redesign change)

Applied the change flagged in §43.6. **This is a product change, not part of
the theme migration**, and it affects BOTH themes identically.

### 44.1 What was wrong

`QuickInputController::show()` built `$industryLabel` at line ~282, directly
alongside `$userFriendlyName` — which WAS passed. It was simply missing from
the `compact()` at line ~364.

Three pieces of UI consumed it and therefore **never rendered in production**:

| Feature | View lines (both themes) |
|---|---|
| Industry-specific description | falls back to `$emissionSource->instructions` |
| "Common Equipment" panel | entire block |
| "Typical Units" chip | entire block |

### 44.2 Why it survived

Every consumption site is guarded — `?->`, `isset()`, truthiness. PHP
suppresses the notice and the fallback wins, so there was no error, no log
entry, and no visual defect. The page looked complete because the description
silently fell back to the generic `instructions` text.

### 44.3 Verified before applying

- `getIndustryLabel()` returns an `EmissionIndustryLabel` **or null** — null
  when the company has no `industry_category_id`, or no matching label row.
- All three fields (`user_friendly_description`, `common_equipment`,
  `typical_units`) exist in the model's `$fillable`.
- The assignment is at method-body indentation (unconditional), and `show()`
  has exactly **one** `return view(...)` — so no path reaches the compact with
  the variable undefined.
- Both themes already guard with `isset() && $industryLabel && ->field`, so a
  null renders nothing rather than erroring.

**Diff is 6 lines, one of them functional.**

### 44.4 A checker bug this change exposed

Adding a comment containing `getIndustryLabel()` inside the `compact()` broke
the checker: it harvested names with `compact\(([^)]*)\)`, and `[^)]*`
truncates at the **first** `)` — including one inside a comment between the
arguments. Every name after it was lost, and `editEntry`, `existingEntries`
and `yearsWithMeasurements` were reported undefined.

Replaced with a **balanced-paren scan** from `compact(` to its matching close.

**Validated both ways:**
- Removing an *unguarded* variable (`existingEntries`) → still reported.
- Removing `industryLabel` again → correctly NOT reported, because it is
  guarded everywhere. That is precisely why this bug went unnoticed for so
  long, and it is the intended behaviour of the guarded-variable rule.

### 44.5 Runtime verification

Needs a company **with an `industry_category_id`** that has a matching
`EmissionIndustryLabel` row. On a company without one, nothing changes —
which is the safe default and also means a quick smoke test may show no
difference. Check on quick-input for a source that has label data.

## 45. Sidebar feeds indicators — TRIED AND REVERTED

Prompted by "these three pages look like reports, should they move under
Reports?" The answer to the move is still **no** (§45.1). The proposed
alternative — surfacing the feeds relationship in the sidebar — was built,
rejected on sight, and reverted. Recorded so it is not re-attempted.

### 45.1 Why the pages should NOT move to Reports (unchanged)

The nav is organised by **what you are doing**, not by subject:

| | Environmental / Social / Governance | Reports |
|---|---|---|
| You | **enter** data | **read** output |

Climate risks / Opportunities / Climate targets are all "Add ..." forms.
Reports holds GHG inventory, Disclosure hub, UAE ESG, GRI, IFRS S2, IFRS S1 —
all outputs. The IFRS S2 report already lives in Reports; Climate risks is one
of its **inputs**. Moving inputs beside outputs makes Reports half forms, half
documents.

The URL `/disclosures/ifrs-s2/climate-risks` reflects **which framework
consumes the data**, not where you enter it. If URLs drove placement,
`disclosures.hub` and `disclosures.gri` would move too.

Moving them would also break a deliberate pairing (`config/navigation.php:89`):
`climate_risks` and `sustainability_risks` are identical column-for-column
except their discriminator, kept as separate tables, and the only thing
stopping users confusing them is that they sit under different pillars. Same
for Climate targets vs Social > ESG targets.

### 45.2 What was tried

`NavigationMap::items()` resolved each item's existing `feeds` keys to short
framework labels, rendered in the sidebar beside the nav label.

### 45.3 Why it was reverted

It broke the labels — the one thing a sidebar must get right:

- **"Materiality" rendered as "M..."**, its tag (`IFRS S1 · GRI · UAE ESG`)
  three times longer than the label it was annotating
- **"Policies" → "Po..."**, **"ESG scorecard" → "ESG scoreca..."**
- 11 of 24 items carried a tag; the three-framework ones dominated the row

The `flex-shrink:0` added to protect the tag is what caused it: it protected
the annotation and let the **label** collapse. Backwards. A nav item you
cannot read is worse than one that does not say where its data goes.

### 45.4 Why no simpler variant works either

The sidebar is ~200px. It cannot carry a label AND up to three framework
names. That is a space constraint, not a styling problem:

| Variant | Fails because |
|---|---|
| Cap at one framework | "Materiality `IFRS S1`" is now **wrong** — it feeds three |
| Abbreviate to `S1·GRI·UAE` | still ~60px; "Materiality" still truncates |
| Dot / count badge | "Materiality ③" is not actionable |
| Second line under label | doubles sidebar height for 11 of 24 items |

**Do not re-attempt without widening the sidebar**, which is a much larger
change than the problem justifies.

### 45.5 What already solves this

The on-page `register-lineage` partial (`FEEDS IFRS S1 · GRI · UAE ESG`)
renders at the top of every register page, with room to breathe and real
links to each report. It answers the same question one click later, and it
works today.

If discoverability is still a problem, the better direction is the **reverse**
link — on each report page, list the registers that populate it. That is the
"I'm working on S2, where do I enter this?" journey, it currently dead-ends,
and a report page has the width to carry it.

## 46. Six-pillar tab IA (both themes)

Replaces the single scrolling rail with a **tab bar + filtered sidebar**: six
tabs (Overview · Environmental · Social · Governance · Reports · Settings),
and the sidebar shows only the active tab's items.

**Structure only — no pages added, no routes changed.** All 30 nav
destinations (24 in pillar groups + 6 promoted from the footer) stay
reachable. `config/navigation.php` remains the single source of truth.

### 46.1 Overview has one item — deliberate (Option B)

Overview's sidebar shows a single link today. Chosen over hiding the rail so
the layout does not change shape when Overview pages are added later.

### 46.2 NavigationMap::tabs()

New method beside `build()`. Same config, same gates, same items; only the
shape differs. `build()` returns every group for one rail, `tabs()` returns
one tab per group plus the active tab's items.

- **Settings is promoted** from the `footer` block to the sixth tab. Items are
  identical; only placement changes, so it is not duplicated into config.
- **Active tab** = the group owning the current route, using the per-item
  `active` flag `items()` already computes. Falls back to the first tab, so an
  unrecognised route still renders a usable nav.
- A tab whose items are all gated away is dropped, exactly as `build()` drops
  such a group — a user never sees a tab opening onto nothing.
- Returns `pillar` for the active tab so neither view needs a loop.

**No JavaScript.** A tab links to its first item; that route makes the tab
active next request. Works with JS disabled.

### 46.3 The two shells differ, on purpose

| | New theme | Old theme |
|---|---|---|
| Tab bar | full-width band between topbar and body | inside the sidebar, above the section |
| Why | `.mnz-tabs` already existed, incl. active state and mobile scroll | `.sidebar` is `position:fixed; top:0` and `.main-content` is offset by its width — **there is no full-width band above both** |

Putting a full-width bar in the old shell would mean restructuring the whole
layout. Same information, same source, laid out to suit each shell.

**Emission-source tree preserved** in the old theme (6 `quick-input.show`
links), now keyed off `$nav['active'] === 'environmental'` instead of
`$group['key']`. The new theme has never carried it (documented omission).

### 46.4 A checker bug this exposed

`@include('theme-new::layouts.partials.nav-tabs')` was reported missing. The
checker stripped the namespace and looked up the bare remainder, which only
resolved when a **non-themed** view of the same name happened to exist — true
for `help.partials.guide-highlight` in §42, false for a themed-only partial.

Fixed to map `theme-new::a.b` → `themes.new.a.b`. **Validated**: a genuinely
bad target is still reported.

### 46.5 Runtime verification

- Click all six tabs in **both themes**; sidebar shows only that pillar.
- **Overview** shows its single item, not an empty rail.
- **Settings** tab reaches all six items (Reporting, Team & access, Profile,
  Billing, Find a consultant, Help & guide).
- **Environmental in the old theme** still shows the Scope 1/2/3 tree, with
  Scope 3 locked on Free.
- A **gated-away tab** (e.g. Reports without the `disclosures` entitlement) is
  absent, not empty.
- Deep-link into a sub-page and confirm the correct tab is active.

## 47. Aligning to the design canvas

Source of truth: `Menetzero-Redesign/MeNetZero Redesign.dc.html`. Earlier
sections were built from screenshots; this reconciles against the file.

### 47.1 Shell fixes applied

| # | Design | Was | Now |
|---|---|---|---|
| 1 | Tab row is the **second row of `<header>`** (42px under the 56px topbar) | rendered AFTER `</header>` | inside `<header>` |
| 2 | Right-hand meta on the tab row | missing | `Assurance: <level>` via `.mnz-tabs__meta` |
| 3 | Sidebar head (kicker + title) in its own block, `0 20px 14px` | inside the link group | own `.mnz-side__head` |

**#1 was the visible bug** — outside `<header>` the tabs read as a detached
strip on their own hairline.

**Sync time deliberately omitted.** The design shows `LAST SYNC 09:41`;
nothing in the app records it, so it is left out rather than faked. Assurance
reads the company's stored value and falls back to **"None"** — most companies
have had no external assurance, and overclaiming it in a compliance product
would be worse than showing nothing.

### 47.2 The design's sidebar is NOT the current nav restyled

It specifies different items in **sub-groups**. Bold = does not exist:

- **Environmental** — MEASURE: Scope 1/2/3, Locations, Bulk import ·
  MANAGE: Targets & pathway, **Energy & water**, **Waste & circularity**,
  Climate risk (S2)
- **Social** — WORKFORCE: **Headcount & turnover**, **Diversity & inclusion**,
  **Health & safety**, **Training & development** · VALUE CHAIN: Supply chain
  labour, **Community investment**, **Human rights**
- **Governance** — OVERSIGHT: **Board & committees**, **ESG accountability**,
  **Remuneration link** · CONDUCT: Policies register, **Ethics &
  anti-bribery**, **Data & cyber**, Risk register
- **Overview** — SNAPSHOT / PROGRAMME / HANDOFF, all new

**Social is the extreme case: 7 items, 1 of which exists.** The current Social
nav (Summary, Stakeholders, Supply chain, ESG scorecard, ESG targets) is a
different list.

So "use the design's structure" and "show the submenus we have" are, for
Social and Governance, two different navs. The user's instruction was the
latter, so the current items stand until pages are built.

### 47.3 Not yet done, and why

- **Sub-group headings** (MEASURE / MANAGE ...) — needs a `section` key per
  item in `config/navigation.php`. Can be done with existing items; deferred
  pending confirmation of which existing item belongs in which sub-group.
- **Item counts** (`9`, `6/15`, `!`) — each needs a real query. `6/15` is
  Scope 3 coverage, `!` is a warning state. Not faked.
- **Readiness meter** — the design pins a % meter to the sidebar foot. The
  denominator ("required fields") is not defined anywhere in the app; it needs
  a rule before it can be computed.

### 47.4 HANDOFF is not customer-facing

The Overview group's HANDOFF section ("All mockups" 11, "Implementation map"
37) refers to the design canvas's own artboards. It is project tooling, not
product, and must not ship in a client's nav.

### 47.5 Old theme: tabs moved out of the sidebar

The first old-theme attempt put the switcher inside the 236px sidebar, where
six entries **wrapped to three rows** — a stack of pills, not a tab bar.

Moved to where the design puts it: a second band under the header, spanning
the content width.

**How, without restructuring the shell.** `.header` and the tab row are wrapped
in a new `.header-stack` that carries the `position: sticky`; `.header` inside
it becomes `position: static`. Sticking the stack rather than each band is what
stops the two detaching and scrolling apart. `.sidebar` is untouched — it is
`position: fixed` and never participated.

- One row, `overflow-x: auto`, `scrollbar-width: none` — scrolls, never wraps
- Active tab is an **underline**, not a pill
- 42px tall with a 1.5rem gutter, matching the canvas

**Pillar colours** now come from the canvas (`E #0f7a4a`, `S #1a6c9e`,
`G #5b5aa8`) rather than `--brand` (`#10b981`), which is a brighter emerald
than the logo or the design use. Each pillar tab's dot and active underline
use its own colour.

**One specificity bug fixed on the way**: `.pillar-tab.active .pillar-tab__dot`
(0,2,1) outranks `.pillar-tab__dot[data-pillar]` (0,2,0), so an active pillar
tab's dot rendered dark instead of its colour. The dark rule is now scoped
`:not([data-pillar])`, which is exactly the three non-pillar tabs (Overview,
Reports, Settings) since Blade only emits `data-pillar` for E/S/G.

Verified: 232 non-theme views scan clean, shell `<div>` count 30/30 (one pair
more than before — the wrapper), CSS braces 314/314.

## 48. ESG performance cards — Overview Pass 1

Three E / S / G cards above the existing dashboard. **Additive** — nothing
removed; the enterprise panel below is untouched.

### 48.1 Standards audit (asked before building)

9 of 12 designed metrics carry real framework codes and already exist:

| Metric | Standard |
|---|---|
| Gross emissions | GHG Protocol / IFRS S2 |
| Intensity | GRI 305-4 |
| Renewable share | GRI 302-1 |
| Headcount | GRI 2-7 |
| Turnover | GRI 401-1 |
| LTIFR | **GRI 403-9** |
| Women in management | **GRI 405-1** |
| Women on board | GRI 405-1 |
| Scope 3 coverage | GHG Protocol — 15 categories |

**Three are NOT standard and were withheld:**

1. **Board independence.** The app stores `board_diversity_percent` = *women*
   on the board. Independence = *non-executive* directors. Different measures.
   Rendering one under the other's label is **a misstatement in a regulated
   disclosure**, not a cosmetic slip. Omitted until the field exists.
2. **Policies "4 of 9".** `gov.policies` is a disclosure **section editor**,
   not a register — no rows to count, and no standard defines nine.
3. **"ESG on board agenda: Quarterly".** Not a GRI or SCA metric.

### 48.2 The headline number is completeness, not a rating

The design shows 82 / 47 / 38. There is **no recognised GRI or SCA scoring
methodology** behind those, and a client would reasonably read "82" as an
external ESG score.

Shown instead as **"% data complete"**, taken straight from
`EsgDashboardService::scoreEnvironmental/Social/Governance()`, which already
computed exactly this from named disclosure checks. Honest, config-derived,
and it tells the user what is left to fill in.

`+6 vs FY24` is likewise omitted — it implies a rated trend.

### 48.3 Reuse, not reinvention

`EsgDashboardService` **already** returned per-pillar percents, GHG totals and
the full scorecard. `EsgPerformanceCardService` only reshapes that plus
`EmissionsIntensityService::forYear()`. No new queries beyond a site count.

**Null renders "not collected" in amber, never 0** — a zero would read as a
measured result. The design does the same for LTIFR / "not disclosed".

### 48.4 Four wrong assumptions caught by checking

1. `renewable_share_percent` → the real key is **`renewable_energy_percent`**
2. `Company::reportingSetting` **does not exist** — queried
   `CompanyReportingSetting` directly, as `EmissionsIntensityService` does
3. `DashboardController` has **three** `view('dashboard.index')` calls; two
   are the onboarding path. Only the real one was wired.
4. The new theme has **no `dashboard/index` override** — it overrides
   `dashboard.partials.enterprise`. One insertion serves both themes.

### 48.5 Safety

- Service call wrapped in try/catch → `report()` + null. The panel can never
  take the dashboard down.
- `@if (!empty($esgCards))` → renders nothing on the onboarding path.
- CSS is self-contained (no theme tokens) and appended to **both**
  stylesheets, so one partial serves both shells.

Verified: 233 non-theme views scan clean; partial balanced 2/2/2; controller
braces 42/42 (+2 for try/catch); both stylesheets brace-balanced.

### 48.6 Next passes

- **Pass 2** (mechanical): Scope 3 coverage `6 of 15`, sites/consolidation in
  the context line (built, needs data), `Compare FY24` / `Export board pack`.
- **Pass 3** (needs your decisions): board independence field, ESG agenda
  enum, policies register.

## 49. Framework readiness — Overview Box 3

Five readiness bars under the E/S/G cards. **Almost pure reuse** — no
completeness logic was written.

### 49.1 Three rows are direct lookups

`DisclosureService` already computes a **weighted** percent for IFRS S2, IFRS
S1 and GRI (`completenessResult()`), and `EsgDashboardService` already returns
all three under `frameworks`. The bars read those values.

This matters beyond saving code: a percent here **cannot disagree** with the
percent on the framework's own disclosure page, because it is the same number.

### 49.2 Two rows needed a rule, and say so

**GHG Protocol inventory.** No completeness weighting exists for the
inventory, so readiness = share of the three scopes carrying data. A company
with Scope 1 and 2 but no Scope 3 reads **67%** — the honest answer, since its
inventory is incomplete under the Protocol.

**UAE ESG (SCA).** `UaeEsgReportService` composes its report FROM S2, S1 and
GRI and publishes no percent of its own, so this is the **mean of those
three**. Marked `derived` in the UI with a tooltip, so it is never mistaken
for a separate assessment.

### 49.3 Details

- A row whose route is missing renders **without a link** rather than
  disappearing — readiness is information in its own right.
- Percents clamped 0–100.
- Bars use the pillar palette (E green, S blue, G purple) from the canvas.
- Labels link to that framework's own page, carrying the fiscal year.

Verified: 233 non-theme views scan clean; partial balanced 5/5, 3/3, 3/3;
service braces 16/16; both stylesheets balanced.

## 50. Emissions pathway — Overview Box 1

Chart of actual emissions against the reduction pathway, with four stat tiles.
Renders only when the company has an **active ReductionTarget**.

### 50.1 The standard

**Linear annual reduction** — a straight line from the target's base year to
its target year. This is the convention the GHG Protocol, SBTi and IFRS S2 all
use for presenting a trajectory.

Three consequences, all deliberate:

1. **The line ends at the TARGET's tonnage, not at zero** (unless the target
   is itself zero). Drawing to zero would render a 50% reduction target as a
   net-zero commitment — a materially different claim.
2. **Scope coverage is the target's, not the total.**
   `ReductionTargetProgressService::actualForCoverage()` already sums only the
   scopes a target covers, so a Scope 1+2 target is never measured against a
   total including Scope 3. The coverage is labelled on the card.
3. **SBTi is surfaced, not asserted.** `sbti_aligned` shows a badge because
   SBTi validation means the slope was checked against a minimum annual rate.
   This code does **not** validate that rate; it draws the target as set.

### 50.2 Projection is labelled, never presented as a finding

The tile reads **"2039 · at current rate"**, with the qualifier styled down so
it cannot be read with the same weight as the number.

It extrapolates the average annual change across observed years — possibly a
handful of points across decades. Guards, all verified against real cases:

| Case | Result |
|---|---|
| Falling trend | year |
| Flat | **omitted** |
| Rising | **omitted** — no crossing point exists |
| One observation | **omitted** |
| Negligible decline (>100y out) | **omitted** — noise, not insight |

When omitted the tile reads "not on current trend" rather than blank.

**The design's "2047" is not reproducible from its own chart.** Its series
(5,102 → 4,183 over three years) projects to **2039**. Ours is arithmetic from
the actual data, which is exactly why the qualifier is required.

### 50.3 Design

Card structure matches the canvas: header with title/subtitle plus scope and
SBTi tags, chart body, and a 4-up stat strip divided by hairlines. Collapses
to 2-up under 720px.

**Inline SVG, no chart library.** Both shells load Chart.js, but this is two
polylines and a few ticks — inline keeps the partial theme-agnostic and avoids
a second chart instance competing with the dashboard's own. Geometry verified:
axes correct, all points inside the plot box.

`derived target` is marked when the tonnage was computed from a reduction
percentage rather than entered directly.

Verified: 233 non-theme views clean; partial 11/11, 6/6, 5/5; service braces
28/28; both stylesheets balanced.

### 50.4 Layout: pathway and readiness paired 50/50

Stacked full-width, the chart dominated the page — a 720x220 plot above a
short list. They now sit side by side in `.esg-row`, each a normal-sized card,
collapsing to one column under 1100px. Plot reduced to 560x180 to suit half
width; `align-items: start` stops the shorter card stretching.

Geometry re-verified at the new size against live data: axes correct, all
points inside the plot box.

### 50.5 Empty state instead of a hidden card

`pathway()` now returns `['empty' => true, 'reason' => ..., 'cta_url' => ...]`
rather than null when there is no usable target. The card renders with an
inert placeholder plot (gridlines and a dashed slope) plus "Set a reduction
target to chart your emissions against a pathway" and a link to add one.

Hiding the card left no clue the feature existed. Three distinct reasons are
surfaced: no target, target without baseline tonnage, target without a valid
base/target year pair.

### 50.6 A data problem the chart exposed

On a live workspace (Falcon Industrial Parks, PRY 2026) the card shows
baseline **FY2024 = 120 t** against **FY2026 actual = 1,714 t** — 14x the
baseline, rising steeply.

This is not a rendering fault; the y-axis correctly scales to the actual
series while the pathway hugs zero. Either the boundary genuinely expanded
since baseline (new sites or scopes added, which under the GHG Protocol
requires a **base-year recalculation**), or the baseline is wrong. Until one
is corrected the target is unreachable.

The projection guard behaved correctly: a rising trend produced **no**
projected year rather than a fabricated one.

## 51. Environmental pillar dashboard (/environmental)

New `EnvironmentalDashboardService` + `EnvironmentalDashboardController` +
`environmental/index` view. **Nothing removed.**

### 51.1 Why a new controller

`/environmental` reused `EsgDashboardController`, which answers *"how is my
whole ESG programme doing"* across all three pillars — so an Environmental URL
rendered E+S+G content. The new controller answers *"what are my emissions"*.

`EsgDashboardController` is **unchanged** and still serves its own pages. The
**E+S+G scorecards on /dashboard are untouched** — different controller,
different view, different partial.

### 51.2 Standard: GHG Protocol Corporate Standard

- **Scope 1/2/3 split** — required reporting.
- **Scope 2 location-based**, the default presentation. Market-based is a
  separate disclosure; `buildScope2Split()` already computes both.
- **Scope 3 against the standard's 15 categories**, using the slug→category
  mapping already in `buildScope3CoverageMatrix()`. Coverage also counts how
  many categories the company's **policy** includes — an excluded category is
  not a gap, and the standard requires exclusions to be justified.
- **Base-year comparison** from the company's own `ReductionTarget`, never a
  guessed prior year. Null renders "no baseline set".

### 51.3 Data quality: no invented status

The design shows `VERIFIED / DRAFT / ESTIMATED / MISSING`. The real enum is
`draft / submitted / under_review / not_verified / verified`.

**"Estimated" was not added.** No estimated-vs-measured flag exists in the
schema, and inventing one would misrepresent data quality. Flagging estimated
data *is* a GHG Protocol expectation, so this is a genuine gap — but a schema
change, not a dashboard one.

**A source line shows the WEAKEST status among its measurements**, not the
latest: if any measurement feeding a source is still a draft, the line is not
verified. Reporting the strongest would overstate assurance.

### 51.4 Reuse

`buildResultsBreakdown()` (per-source tonnes by scope) and
`buildScope3CoverageMatrix()` (15-category mapping) already existed. The
service merges them across the year's measurements — `buildResultsBreakdown()`
takes one `Measurement` — and joins prior year by source name, the only stable
key the breakdown exposes.

### 51.5 Verified

- **Donut geometry**: arcs sum to exactly the circumference (339.29), so no
  gaps or overlaps; shares reproduce 31/48/21 from the design's numbers.
- Two wrong assumptions caught: `Measurement::status_label` does not exist
  (it is **`status_display`**); `SCOPE3_CATEGORIES` confirmed to hold 15.
- Inline SVG donut, no chart library — three arcs need none, and it keeps the
  page theme-agnostic.
- 234 non-theme views scan clean; view balanced 7/7, 3/3, 4/4; service braces
  22/22; both stylesheets balanced.

### 51.6 Not yet built

Emissions trend over years (`yearlyTrend()` exists — reuse from the
dashboard), Recalculate action, and per-source drill-through.

### 51.7 Emissions trend added; Recalculate deliberately not built

**Trend chart** — stacked bars by scope, straight reuse of
`DashboardInsightsService::yearlyTrend()`, the same computation the main
dashboard's chart uses. A year's total here therefore cannot disagree with the
one shown there.

**Stacked, not a single line**, because the composition shift is the story: a
falling total with rising Scope 3 is a different situation from a falling
total overall. Shows **all years**, not just up to the selected one — the
point of a trend is the shape over time.

With one year of data the chart is replaced by "A trend appears once a second
reporting year is recorded", rather than a lone bar.

Verified: segments sum to exactly 100% per year, the tallest bar fills the
track, and FY2025's 21.1/48.2/30.8 split matches the donut on the same page.

**"Recalculate" was NOT built.** The design shows the button; the app has **no
route, no controller method and no service** for it. Building it would mean
inventing the operation — re-running every measurement against current
emission factors, which changes historical figures and has real versioning and
audit-trail implications under the GHG Protocol (a recalculation must be
disclosed, not silent). That is a feature with its own design, not a dashboard
button. Omitted rather than wired to nothing.

## 52. De-duplicating the main dashboard

Three sections removed from `/dashboard` now that `/environmental` owns them.
**One requested removal was NOT made** — see 52.2.

### 52.1 Removed (both themes)

| Section | Now lives on |
|---|---|
| KPI cards — Total / Scope 1 / 2 / 3 | `/environmental` (same four, **plus** Scope 3 category coverage) |
| Emissions Trend | `/environmental` (stacked by scope) |
| Scope Breakdown | `/environmental` (donut) |

Both themes have their own `enterprise.blade.php`, so both were edited.

**The chart JS is safe.** `dashboard/partials/enterprise-scripts` is shared by
both themes and guards every binding with
`if (ctx && typeof Chart !== 'undefined')`. Removing the canvases makes it
no-op rather than error. The partial is left in place — it still serves other
charts and the guards make it inert for the removed ones.

### 52.2 Net Zero Progress KEPT — it is not a duplicate

The request was to remove it "as we moved it". **It was not moved.** The
Overview pathway chart (§50) is on `/dashboard`, in
`dashboard/partials/esg-performance` — the same page. `/environmental` has no
net-zero section at all.

Removing it would have deleted the feature rather than relocating it.
Verified by grep before acting: `esg-path` appears only in the dashboard
partial.

### 52.3 Dead code left deliberately

`$kpiCards`, `$trendClass`, `$trendArrow`, `$sparklineTrend` and `$sparklines`
are now built but unused in both partials. Left in place: `$sparklines` is
guarded with `?? []`, so they are inert, and deleting assignments inside a
live `@php` block risks breaking a working page for no user-visible gain.

### 52.4 Trend chart moved and restyled

Moved from above the source/scope-mix row to **full width below it**, as
requested. At half width the bars were cramped; the previous styling also let
columns float with no baseline.

- `justify-content: center` with `flex: 0 1 120px` — bars stay a sensible
  width whether a company has two years or eight
- Track raised to 168px with a **baseline rule**, so columns sit on something
- Gap 2rem, wider bars (72px cap), subtle shadow

Verified: both partials balanced (old 40/40 divs, new 41/41), 234 non-theme
views scan clean, both stylesheets balanced.

### 52.5 Net Zero Progress and Compliance Status removed — same-page duplication

Correcting §52.2. The earlier note argued Net Zero Progress was not a
duplicate because `/environmental` lacks it. That missed the actual
duplication: **it was duplicated on `/dashboard` itself.**

`dashboard/index` includes `esg-performance` (line 306) then `enterprise`
(308), so the Overview **pathway card renders directly above** Net Zero
Progress. Same reduction target, same baseline, same target year, twice on one
screen.

**Nothing was lost.** The three figures only that panel carried are preserved:

| Figure | Now |
|---|---|
| Progress % toward baseline reduction | `achieved_percent` → "N% toward target" in the pathway card header |
| Years remaining | `years_remaining` → under the projection tile |
| Intensity (tCO2e / AED m) | already the Environmental dashboard's Intensity KPI |

`achieved_percent` comes from `ReductionTargetProgressService`, which the
pathway card already consumed — no new computation.

**Compliance Status** removed too: Framework readiness on the same Overview row
supersedes it with five frameworks rather than four, `DisclosureService`
weighted percentages, and a link to each framework's page.

Verified: no orphaned `$netZeroProgress`, `$compliance` or `$currentIntensity`
references in either partial; both balanced (old 9/9 divs, new 13/13); 234
non-theme views scan clean; both stylesheets balanced.
