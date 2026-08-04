# MENetZero — Consultant Multi-Package Plan (Option C / multi-row)

| | |
|---|---|
| **Document** | `CONSULTANT_MULTI_PACKAGE_PLAN.md` |
| **Status** | Locked for phased implementation |
| **Date** | August 2026 |
| **Audience** | Product, engineering, admin ops |
| **Related** | `PRICING_AND_PLAN_MAJOR_CHANGES.md`, `CONSULTANT_AGENCY_PLAN_V1.md` (legacy), xlsx company packages |
| **Inventoried DB** | `silverwebbuzz_in_menetzero` — Aug 2026 snapshot below |

Single source of truth for **consultant** packaging after offline-payment + per-depth capacity.  
**Company package catalog is unchanged** (except one legacy subscriber remap noted in §8).

Implement **one phase at a time**. Check off §10 as you ship.

---

## 1. Goals

1. Consultants can hold **multiple depth packages at once**, each with its own **slot count** and **expiry**.
2. Example: Basic ×5 (expires Dec) + later Basic ×5 (different term) + ESG Starter ×5 — **three subscription rows**.
3. `consultant_free` and `consultant_1` **remain** on the agency; paid depth rows are **additive**.
4. Request clients = **multi-line** (depth × qty) → offline quote → activate **one `consultant_subscriptions` row per line**.
5. Each managed client engagement binds to **one** paid (or free/demo) subscription row → entitlements from that plan’s mirrored company depth.
6. Retire confusing legacy packs (`consultant_5/10/25/50`, `consultant_entity`, single agency-wide `managed_client_package_code`).

---

## 2. Locked catalogs

### 2.1 Company (do not redesign)

| Code | Role |
|------|------|
| `client_free` | Free |
| `client_scope_basic` | Scope Basic |
| `client_scope_pro` | Scope Pro |
| `client_esg_starter` | ESG Starter |
| `client_esg_complete` | ESG Complete |
| `client_enterprise` | Enterprise |

Legacy (keep row until unused, then archive): `client_starter`, `client_growth` — see §8.

### 2.2 Consultant (target)

| Code | Role | Mirrors |
|------|------|---------|
| `consultant_free` | Free trial — **1** managed client | `client_free` (watermarked Free rules) |
| `consultant_1` | Demo / QA — **1** client full access | Admin-only; special consultancy |
| `consultant_scope_basic` | Paid depth capacity | `client_scope_basic` |
| `consultant_scope_pro` | Paid depth capacity | `client_scope_pro` |
| `consultant_esg_starter` | Paid depth capacity | `client_esg_starter` |
| `consultant_esg_complete` | Paid depth capacity | `client_esg_complete` |
| `consultant_enterprise` | Paid depth capacity | `client_enterprise` |

Rename: live `consultant_trial` → **`consultant_free`** (Phase 2).

Entitlement rule: consultant depth plans **delegate** to the matching `client_*` entitlements/limits (no divergent feature matrix).

---

## 3. Data model (multi-row)

```text
Agency org
├─ consultant_free              always (1 slot, Free rules)
├─ consultant_1                 only if admin granted
├─ consultant_scope_basic       slot_limit=5  starts/expires A   ← purchase 1
├─ consultant_scope_basic       slot_limit=5  starts/expires B   ← purchase 2 later
└─ consultant_esg_starter       slot_limit=5  starts/expires C
```

Table: `consultant_subscriptions` (existing) — **one row per purchase/depth/term**.

| Field | Use |
|-------|-----|
| `subscription_plan_id` | Points at `consultant_free` / `_1` / `consultant_scope_*` |
| `slot_limit` | How many managed clients this row can hold |
| `starts_at` / `expires_at` | **Per-row** term (solves mid-year top-ups) |
| `status` | active / expired / cancelled |
| `metadata` | request id, quote, notes |

Engagement (`consultant_client_engagements`):

| Field | Use |
|-------|-----|
| `consultant_subscription_id` | **Which** capacity row this client consumes |
| *(future)* optional denormalised depth | Prefer resolving via `subscription.plan.plan_code` |

**Create client flow:** pick a depth that has spare active slots → attach engagement to that subscription row → entitlements from that plan’s mirror.

**Do not** store a single agency-level `metadata.managed_client_package_code` as the live source of truth (legacy fallback only during cutover).

---

## 4. Request → quote → activate

### Request UX
- Multi-line: `{ package_code: client_scope_* \| client_enterprise, entity_count }[]`
- UI may show company depth labels; activation maps to **`consultant_scope_*` / `consultant_enterprise`**.
- Preferential ≥10 tip: sum of all line qtys (sales policy only).

### Activate
For each line:
1. Map `client_scope_basic` → `consultant_scope_basic` (etc.).
2. Create **new** `consultant_subscriptions` row with `slot_limit = qty`, contract term, plan = consultant depth code.
3. Leave `consultant_free` / `consultant_1` untouched.
4. Audit via `AdminPackageAssignment` with full lines array.

Quote: Σ (company list or preferential band × qty) per line — `CommercialPriceBook`; sales may override offline.

---

## 5. Retire list

| Code | Disposition |
|------|-------------|
| `consultant_trial` | Rename → `consultant_free` |
| `consultant_entity` | Deactivate / delete after code cutover (0 live rows inventoried) |
| `consultant_managed_standard` | Retire as sellable; prefer limits from `client_scope_basic` |
| `consultant_5` / `_10` / `_25` | Deactivate / delete (0 live rows) |
| `consultant_50` | **Delete demo assignment + related seed data**, then deactivate/delete plan (§7) |
| Pack self-checkout | Remains hidden (already Phase 3 offline) |

Hard-delete a `subscription_plans` row only when **zero** FKs remain in `consultant_subscriptions`, `client_subscriptions`, assignments, coupons.

---

## 6. DB inventory (Aug 2026 — production-like)

### Consultant (`consultant_subscriptions`)

| plan_code | total | active | Notes |
|-----------|------:|-------:|------|
| `consultant_1` | 1 | 1 | Keep |
| `consultant_trial` | 5 | 3 | → rename `consultant_free` |
| `consultant_50` | 1 | 1 | **Silver Webbuzz Sustainability Practice** (company_id 17) — **delete demo** (§7) |

No rows: `consultant_entity`, `consultant_managed_standard`, `consultant_5/10/25`.

### Company (`client_subscriptions`)

| plan_code | total | active | Notes |
|-----------|------:|-------:|------|
| `client_free` | 14 | 14 | Keep |
| `client_enterprise` | 1 | 1 | Keep |
| `client_growth` | 2 | 1 | Remap active → `client_scope_pro` (§8); no unpaid/live commercial blocker |

Tables in use: `client_subscriptions`, `consultant_subscriptions`, `consultant_subscription_addons`, `subscription_plans`, coupons. **No** table named `subscriptions`.

---

## 7. Demo deletion (Silver Webbuzz / consultant_50)

**Decision:** Delete the full demo seed for **Silver Webbuzz Sustainability Practice** (`consultant_company_id` 17, `consultant_50` sub id 7, expires 2026-12-31). After multi-package work ships, **recreate a new demo** with Free + depth packages as needed.

Phase 2 migration must remove, in dependency order (typical):

1. Engagements / managed client companies under that consultant org  
2. Addons on that subscription  
3. `consultant_subscriptions` row(s) for that org (incl. trial if only demo) — **confirm** whether org 17 is demo-only before wiping the company  
4. Admin assignments / seed artifacts tied to that demo  
5. Optionally the `companies` row if fully synthetic  
6. Deactivate `consultant_50` plan  

Update/disable `ConsultantFullDemoSeeder` (and any `consultant_50` defaults) so it does not recreate the old pack until a new multi-package demo seeder exists.

---

## 8. Company legacy remap

| From | To | Why |
|------|----|-----|
| Active `client_growth` | `client_scope_pro` | Closest modern depth; no paid commercial blocker said for current growth row(s) |

Keep `client_growth` / `client_starter` plan rows **inactive** afterward for history, or delete only if unused.

**Company sellable catalog otherwise unchanged.**

---

## 9. Mapping helpers (code)

```text
client_scope_basic      ↔ consultant_scope_basic
client_scope_pro        ↔ consultant_scope_pro
client_esg_starter      ↔ consultant_esg_starter
client_esg_complete     ↔ consultant_esg_complete
client_enterprise       ↔ consultant_enterprise
client_free             ↔ consultant_free
```

Request form continues to use **company** codes for depth choice; activation writes **consultant_*** plan rows.

---

## 10. Phased checklist

### Phase 0 — Doc ✅
- [x] This document locked (catalog, multi-row, retire list, inventory, demo delete, growth remap).

### Phase 1 — Catalog seed + matrix
- [x] Seed `consultant_free` (+ keep `consultant_trial` until Phase 2 rename).
- [x] Seed `consultant_scope_basic` … `consultant_enterprise` (category `consultant_agency`, entitlements mirrored from `client_*`).
- [x] Keep `consultant_1` active for admin QA.
- [x] Update `ConsultantAgencyPlanMatrix` constants / pack definitions for new codes; stop advertising legacy packs as live.
- [x] Deactivate sellable flags on `consultant_entity`, packs 5/10/25/50, `consultant_managed_standard` if still active.
- [x] Migration: `2026_08_04_150000_phase1_consultant_multi_package_catalog.php`
- [x] `CommercialPriceBook::suggestedConsultantPlanCode()` maps client depth → consultant_* plan.

### Phase 2 — Migrate existing rows + delete demo
- [x] Rename `consultant_trial` → `consultant_free` (plan_code + code references).
- [x] Delete Silver Webbuzz demo (`consultant_50` + related) per §7 — full org wipe.
- [x] Remap `client_growth` → `client_scope_pro` for remaining client subscriptions.
- [x] Soft-delete / deactivate unused legacy consultant plan codes with zero FKs.
- [x] Migration: `2026_08_04_160000_phase2_consultant_multi_package_migrate.php`
- [x] Disable `ConsultantFullDemoSeeder` until Phase 5 multi-package demo.

### Phase 3 — Request lines + activate → multi-row
- [x] Persist multi-line entity requests (`lines` JSON + backfill).
- [x] Admin quote Σ lines (`CommercialPriceBook::suggestConsultantLinesQuote`).
- [x] Activate creates **one subscription row per line** (`grantDepthSubscription`) — keeps free / demo.
- [x] Do not overwrite a single agency `managed_client_package_code` as sole depth.
- [x] Consultant Request clients UI: qty per package (mix Basic ×5 + ESG ×5).
- [x] Migration: `2026_08_04_170000_phase3_consultant_entity_request_lines.php`

### Phase 4 — Create client + entitlements
- [x] Create managed client: choose depth with spare capacity → bind `consultant_subscription_id`.
- [x] `ConsultantAgencyEntitlementService` resolves depth from engagement’s subscription plan (mirror `client_*`).
- [x] Free / demo paths unchanged (`consultant_free`, `consultant_1`).
- [x] UI: capacity by depth on clients index + package picker on create.
- [x] Aggregate `slotSummary` across all active subscription rows.

### Phase 5 — Guides + seed refresh
- [x] Portal guide / plans-consultant / ElevenLabs: multi-package consultant model.
- [x] New demo seeder — Free + all five depths × 5 slots + 25 firms with full module data (not `consultant_50`).
- [x] Point `PRICING_AND_PLAN_MAJOR_CHANGES.md` §6 toward multi-row capacity + this doc.
- [x] Run: `php artisan db:seed --class=ConsultantFullDemoSeeder` (login `demo.full@menetzero.com` / `FullDemo1!`)

---

## 11. Success criteria

1. Admin plan list for consultants is understandable: Free, Demo QA, five depths.  
2. One agency can have several active depth subscriptions with different counts and expiries.  
3. Request Basic×5 + ESG×5 activates **two** rows; clients can be created under each depth independently.  
4. Old pack SKUs gone from day-to-day ops; demo no longer on `consultant_50`.  
5. Company xlsx packages unchanged for selling; growth remapped.

---

## 12. Open / follow-ups

| Item | Status |
|------|--------|
| Preferential 1,399 / 1,199 vs list × depth | Still sales/price-book policy; activation shape is multi-row depth |
| Sites &gt;5 on an entity | Extras / offline quote (unchanged) |
| New full demo after Phase 4–5 | **Done** — `ConsultantFullDemoSeeder` (5 depths × 5 slots × full data) |
| Exact wipe scope for company_id 17 | Done in Phase 2; re-seed recreates Silver Webbuzz demo org |

---

## 13. Changelog

| Date | Note |
|------|------|
| Aug 2026 | Locked Option C multi-row; catalog; inventory; delete consultant_50 demo; remap client_growth → scope_pro |
| Aug 2026 | Phase 1 shipped: matrix + seed migration + price-book map to consultant_scope_* |
| Aug 2026 | Phase 2 shipped: trial→free merge, wipe Silver Webbuzz demo org, growth→scope_pro, disable old demo seeder |
| Aug 2026 | Phase 3 shipped: multi-line request + Σ quote + one depth subscription row per line |
| Aug 2026 | Phase 4 shipped: capacity buckets + create-client depth picker + entitlements from subscription plan |
| Aug 2026 | Phase 5 shipped: guides/ElevenLabs/§6 update + multi-package full demo seeder (25 clients) |
