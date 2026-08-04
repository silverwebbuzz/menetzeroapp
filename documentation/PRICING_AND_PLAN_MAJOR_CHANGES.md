# MENetZero — Pricing & Plan Major Changes

| | |
|---|---|
| **Document** | `PRICING_AND_PLAN_MAJOR_CHANGES.md` |
| **Status** | Approved for phased implementation (revised after commercial proposal Aug 2026) |
| **Date** | August 2026 |
| **Audience** | Product, engineering, sales, admin ops |
| **Source — company packages** | `documentation/MENetZero_Features_Pricing.xlsx` |
| **Source — consultant paid** | Commercial proposal email (Ojas Bohra, Aug 2026) — §6 |
| **Related** | `CONSULTANT_MULTI_PACKAGE_PLAN.md` (consultant multi-row / depth packages — **current**), `CONSULTANT_AGENCY_PLAN_V1.md` (legacy), `PlanEntitlementService` / `PlanGate` |

Single source of truth for: Free for all → no public prices → logged-in Request flows → offline payment → admin activation.

Implement **one phase at a time**. Check off §12 as you ship.

---

## 1. Goals

1. No public / self-serve paid prices.
2. Company + consultant start on **Free** and explore.
3. After Free: company **Request a package**; consultant **Request slots (entities)**.
4. Offline payment; admin activates entitlements.
5. Company packaging stays flexible (xlsx tiers + extras).
6. Consultant paid defaults to the **Aug 2026 Consultant Plan** (§6), not the old Essential/Complete pack grid.

---

## 2. Locked product decisions

| # | Decision | Detail |
|---|----------|--------|
| 1 | Public site | **No prices.** **Explore Free** only. Remove `/pricing`. |
| 2 | Free — Company | S1&2 full; S3 all cats **1 entry each**; watermarked GHG/MOCCAE/Excel/IEQT. |
| 3 | Free — Consultant | **1 entity (managed client)**. Same Free rules inside. |
| 4 | Scope 1 & 2 | Always included in Free and every paid package. |
| 5 | Company after Free | **Request a package** (xlsx tiers ± extras). No AED in UI. |
| 6 | Consultant after Free | **Request clients** with company package depth (Scope Basic…Enterprise) × count. No AED in UI. |
| 7 | Payment | Offline only. |
| 8 | Admin | Request → quote → paid → Activate. |
| 9 | Forms | Separate company vs consultant forms (consultant also has client count). |
| 10 | Company packages | Keep xlsx Scope Basic / Pro / ESG Starter / Complete. |
| 11 | Consultant paid pricing | Default suggest = **package list × clients**; sales may apply §6 preferential overrides offline. |
| 12 | Entity (consultant) | **1 entity = 1 managed client slot**; included **up to 5 sites**. |
| 13 | Min 10 companies / 12 months | Preferential-price **sales/contract policy only** — **no software enforcement** yet. |
| 14 | Enterprise | Fully custom (branding / white-label / implementation). MENetZero may invoice consultant. |
| 15 | Brand in product UI | **MENetZero** (sales email may say “Me Net Zero”). |

---

## 3. Commercial flow

```text
Explore Free (public, no AED)
        ↓
Free: company OR consultant (1 entity)
        ↓
Need more
  Company  → Request a package (xlsx ± extras)
  Consultant → Request entities (Consultant Plan or Enterprise)
        ↓
Admin quotes offline → payment → Activate
```

---

## 4. Glossary

| Term | Meaning |
|------|---------|
| **Site / location** | Physical place → Locations in app. |
| **Entity (company packages)** | Legal company under a direct subscription. |
| **Entity (consultant commercial)** | **One managed client** (same thing). Sales docs may say “entity”; **app UI says “managed client / clients”.** |
| **Package** | Direct company tier (Scope Basic … ESG Complete). |
| **Consultant Plan** | Paid consultant annual offer after Free (§6.2). |
| **Enterprise** | Custom / white-label deployment (§6.4). |
| **Watermark** | Free trial stamp on allowed downloads. |

---

## 5. Company packages (xlsx — unchanged by consultant email)

AED / year excl. 5% VAT. **Not shown publicly.**

| Package | AED/yr | Sites | Notes |
|---------|--------|-------|-------|
| **Free** | 0 | 1 | S1&2; S3 1/cat; watermarked basic exports |
| **Scope Basic** | 2,500 | Up to **3** | S1&2 + clean GHG/MOCCAE/Excel/IEQT |
| **Scope Pro** | 4,999 | Up to 10 | S1–3 + ESG disclosure suite |
| **ESG Starter** | 18,000 | Up to 5 | Full ESG + white-label/assurance |
| **ESG Complete** | 36,000 | Up to 10 | Larger + multi-entity consolidation |

Codes: `client_free`, `client_scope_basic`, `client_scope_pro`, `client_esg_starter`, `client_esg_complete`.

---

## 6. Consultant commercial (Aug 2026 proposal)

**Applies after Free.** Free remains **1 entity**. Consultant portal only.

### 6.1 Free

| | |
|--|--|
| Price | AED 0 |
| Entities | **1** |
| Client rules | Same as Company Free |

### 6.2 Consultant Plan (introductory)

| Band | Rate (excl. VAT) |
|------|------------------|
| Up to **10 entities** | **AED 1,399 / entity / year** |
| **More than 10 entities** | **AED 1,199 / entity / year** |

| Commercial note | Product handling |
|-----------------|------------------|
| Special introductory offer | Sales wording; list in admin price book |
| Preferential pricing if **≥10 companies onboarded in 12 months** | **Sales/contract only** — no app gate yet |
| Applicable for entities with **up to 5 sites**; **>5 sites** may vary | Default limit **5 locations** per entity; extras quoted |
| Access only via consultant portal | Keep managed-client / agency model |

**Examples:** 5 × 1,399 = AED 6,995 · 10 × 1,399 = AED 13,990 · 15 × 1,199 = AED 17,985.

### 6.3 Default entitlements per paid entity = **Standard** (locked)

Maps to xlsx **Standard** slot profile (intro rate AED 1,399 ≈ list 1,499):

| Area | Included |
|------|----------|
| Sites | Up to **5** |
| Scopes | **1 & 2** (Scope 3 beyond Free trial = extras / higher quote) |
| Data | Guided Quick Input, bulk CSV/Excel import, evidence trail |
| Clean exports | GHG Inventory PDF, MOCCAE PDF, IEQT prep, Excel results |
| Not included by default | Full ESG suite (UAE ESG / IFRS / GRI / SASB), white-label — use **Enterprise** or extras |

Code target (Phase 5/8): seed a Standard template (e.g. mirror former `slot_standard` / Starter-like client exports — **not** Growth `client_growth`).

### 6.4 Enterprise (custom)

- Custom implementation  
- Client branding / consulting **white-label**  
- Consultant can deploy for clients; **MENetZero invoices consultant**; consultant invoices end-client  

No list price — Talk to us / Enterprise request → admin custom entitlements.

### 6.5 What this replaces

| Old (xlsx / packs) | New default |
|--------------------|-------------|
| Essential 499 / Standard 1,499 / Complete 4,500 / Complete Plus 7,500 | §6.2 entity rates |
| Packs of 5 / 10 / 25 / 50 self-checkout | Hide (Phase 3); Request entities instead |
| Essential min-5 rule in Request UI | **Removed** from default UX |

Old slot codes may remain as rare admin presets only.

### 6.6 Codes

| Name | Code |
|------|------|
| Free trial | `consultant_trial` (live) |
| Paid entity | `consultant_entity` (add in Phase 5/8) |
| Enterprise | custom / `consultant_enterprise` |

---

## 7. Extras (admin price book)

**Company:** extra sites, seats, Scope 3 intensity, ESG add-ons, white-label — as previously scoped.

**Consultant:**

| Extra | Notes |
|-------|--------|
| Sites beyond 5 on an entity | Quote by complexity |
| More entities | Pro-rata at 1,399 or 1,199 band |
| Enterprise / white-label | Custom §6.4 |
| Agency team seats | Optional fee |

---

## 8. Request UX

### Company
Current Free → pick Scope Basic/Pro/ESG… → optional extras → Request activation/quote (**no AED**).

### Consultant
Current 1/1 Free → enter **entity count** + notes (sites >5? Enterprise?) → Request activation/quote (**no AED**, no old pack cards).

### Replaces
Public `/pricing`, company upgrade checkout, consultant pack checkout.

---

## 9. Admin workflow

1. Inbox: company package **or** consultant entity count (± Enterprise flag).  
2. Suggest totals (company from xlsx; consultant from §6.2).  
3. Edit → mark paid → Activate → audit.

---

## 10–11. Free + Watermark

Phases **1–2 done**: Free rules + watermarked GHG/MOCCAE/Excel/IEQT. Paid activations = clean entitled exports.

---

## 12. Phased checklist

### Phase 0–2 ✅ DONE
Align codes · Free S3 · Watermarked exports.

### Phase 2.5 — Commercial proposal alignment ✅ DONE (doc)
- [x] Consultant Plan 1,399 / 1,199; entity = managed client; ≤5 sites; Enterprise custom.
- [x] Free unchanged; company xlsx unchanged; min-10 sales-only.
- [x] Confirm §6.3 = **Standard** entitlements (S1&2, ≤5 sites, GHG/MOCCAE/Excel/IEQT).

### Phase 3 — Hide public & self-serve ✅ DONE
- [x] Replace `/pricing` with Explore Free (no AED plan grid).
- [x] Hide company upgrade / checkout (soft-redirect to billing).
- [x] Hide consultant pack self-checkout (5/10/25/50); packs page → request messaging.
- [x] Point marketing CTAs to Explore Free / request / contact.

### Phase 4 — Company Request a package ✅ DONE
- [x] Logged-in features-only package chooser (no AED).
- [x] Persist `company_package_requests` + sales email notify.
- [x] Admin inbox (`/admin/package-requests`) with status notes.
- [x] PlanGate + billing CTA → `subscriptions.request-package`.

### Phase 5 — Consultant Request entities ✅ DONE
- [x] Entity count form (no AED / no pack grid) on consultant packs page.
- [x] Persist `consultant_entity_requests` + sales email notify.
- [x] Admin inbox (`/admin/entity-requests`).
- [x] Paid managed clients → **Standard** entitlements; demo pack keeps Growth.

### Phase 6 — Admin quote + activate ✅ DONE
- [x] Band / list calculator (`CommercialPriceBook`) on package & entity request inboxes.
- [x] Save quote · mark paid · activate from request (maps to live plans / nearest agency pack + extras).
- [x] Audit via `AdminPackageAssignment` metadata (`request_activate`).

### Phase 7 — Price book ✅ DONE
- [x] `commercial_price_book_entries` seeded (company xlsx + consultant 1,399/1,199 + extras).
- [x] Admin **Price book** UI edits amounts; quote calculator reads DB with constant fallbacks.

### Phase 8 — Live plan seeds ✅ DONE
- [x] Seed `client_scope_basic` / `pro` / `esg_starter` / `esg_complete` (+ keep legacy starter/growth).
- [x] Seed `consultant_entity` (Consultant Plan); deactivate legacy 5/10/25/50 packs.
- [x] Keep **`consultant_1` Demo / QA — 1 client full Growth** for admin testing.
- [x] Paid managed clients use `consultant_managed_standard` limits (**5 sites**).
- [x] Activation maps request codes → live plans; consultant activate → `consultant_entity` + extras.

### Phase 9 — Portal guides + ElevenLabs ✅ DONE
- [x] Company portal guide: Free / watermark / Request a package (Scope Basic…Enterprise); no public AED.
- [x] Consultant portal guide: Free 1 client / Request clients / Standard vs Enterprise; capacity terminology.
- [x] ElevenLabs knowledge + pre-questions synced (company + consultant).
- [x] Voice prompt: offline pricing; managed client wording; no invented AED grids.

### Phase 10a — Renewals (offline nudges) ✅ DONE
- [x] In-app company banner (45-day window) → Request a package.
- [x] Consultant renewal page retargeted offline → Request clients (no checkout form).
- [x] Dashboard / nav renewal CTAs copy updated for offline.
- [x] Email templates `company_renewal_reminder` / `consultant_renewal_reminder` (45/14/3 buckets).
- [x] Artisan `subscriptions:send-renewal-reminders` + daily schedule 08:00.

### Phase 10b — Min-10 preferential soft reminder ✅ DONE
- [x] Request clients tip when count &lt; 10 (hides at ≥10) — sales policy only, not a hard minimum.
- [x] Admin client-request quote note for &lt;10 counts.
- [x] No software gate / no blocking under 10.

---

## 13. Code touchpoints

| Area | Path |
|------|------|
| Free defaults / new packages | `PlanEntitlementDefaults.php` |
| Consultant Standard / entity plan | `ConsultantAgencyPlanMatrix.php` |
| Price book | `CommercialPriceBookEntry`, `CommercialPriceBook.php`, `Admin\PriceBookController` |
| Quotes / activate | `AdminRequestActivationService.php` |
| Phase 8 seed | `2026_08_03_160000_phase8_seed_scope_and_consultant_entity.php` |
| Watermark | `ExportWatermark.php`, reports export controllers |
| Public Explore Free | `/pricing`, `public/pricing.blade.php` |
| Company request | `PackageRequestController`, `CompanyPackageOptions` |
| Consultant request | `EntityRequestController` |
| Admin inboxes | package / client request controllers |

---

## 14. Front messaging

Public: Explore Free · Book demo · no AED.  
Logged-in: Request a package / Request clients · features only · pricing offline.  
**App UI term:** managed client(s). Sales docs may still say entity.

---

## 15. Success criteria

1. No public AED.  
2. Free works (company + 1 consultant client).  
3. Paid = Request → offline → Activate.  
4. Consultant quotes use §6.2 by default.  
5. Company quotes use §5 xlsx.

---

## 16. Open items

| Item | Status |
|------|--------|
| Paid consultant client feature depth | **Standard** (5 sites) via `consultant_managed_standard` |
| Demo / QA 1-client full Growth | **Kept** `consultant_1` (admin-only) |
| Min-10 preferential in software | Soft tip only (Phase 10b) — no enforcement |
| Legacy starter/growth / pack subscribers | Remain; new activations use scope_* / consultant_entity |

---

## 17. Document history

| Date | Change |
|------|--------|
| Aug 2026 | Initial doc + Phases 0–2 |
| Aug 2026 | **Aligned to Ojas commercial proposal** |
| Aug 2026 | Phases 3–7 (Explore Free, requests, quotes, price book) + client terminology |
| Aug 2026 | Phase 8: seed scope packages + consultant_entity; keep demo QA pack; Standard 5 sites |
| Aug 2026 | Phase 9: portal guides + ElevenLabs knowledge for Free / Request / Standard / offline pricing |
| Aug 2026 | Phase 10a: renewal in-app + email nudges (company + consultant) → Request flows |
| Aug 2026 | Phase 10b: min-10 preferential soft tips (Request clients + admin quote) |

---

**Next:** Ops polish / live verification, or new product workstream.
