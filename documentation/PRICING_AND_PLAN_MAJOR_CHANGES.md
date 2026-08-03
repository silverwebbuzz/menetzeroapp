# MENetZero — Pricing & Plan Major Changes

| | |
|---|---|
| **Document** | `PRICING_AND_PLAN_MAJOR_CHANGES.md` |
| **Status** | Approved for phased implementation |
| **Date** | August 2026 |
| **Audience** | Product, engineering, sales, admin ops |
| **Source pricing sheet** | `documentation/MENetZero_Features_Pricing.xlsx` |
| **Related** | `CONSULTANT_AGENCY_PLAN_V1.md` (agency mechanics), existing `PlanEntitlementService` / `PlanGate` |

This is the **single source of truth** for the commercial model redesign: Free for all, no public prices, logged-in package/slot requests, offline payment, admin activation, driver-based extras.

Implement **one phase at a time**. Check off items in §12 when done.

---

## 1. Goals

1. Stop self-serve paid plan checkout and public price display.
2. Let every company and consultant **start on Free** and explore the product.
3. After login, replace “upgrade / buy pack” with **Request a package** (company) and **Request slots** (consultant).
4. Users pick a **predefined package**, optionally add **extras**, then request **activation / quote**.
5. Sales negotiates and collects payment **offline**; **admin activates** entitlements.
6. Avoid unfair packaging: different businesses need different mixes of sites, Scope 3 depth, and ESG outputs — use packages as templates + extras, not rigid public carts.

---

## 2. Locked product decisions

| # | Decision | Detail |
|---|----------|--------|
| 1 | Public site | **No prices.** Message: **Explore Free** / Start free only. Remove `/pricing` (or replace with Free CTA only — prefer **remove**). |
| 2 | Free — Company | Scope **1 & 2 always included**. Scope **3: all categories, max 1 entry per category**. Dashboards + data entry. **Watermarked** test reports only (not for submission). |
| 3 | Free — Consultant | **1 managed-client slot**. Same Free rules inside that workspace. |
| 4 | Scope 1 & 2 | Always complementary in Free **and every paid package** — never sold as an add-on. |
| 5 | Logged-in company UX | Replace Plan & billing / upgrade catalog with **Request a package**. |
| 6 | Logged-in consultant UX | Replace Agency packs self-checkout with **Request slots**. |
| 7 | Request flow | User selects **predefined package** → sees features → optionally adds **extras** → **Request activation** (as-is) or **Request quote/activation** (with extras). |
| 8 | Payment | **Offline only** for this model (invoice / bank / sales-handled). No self-serve unlock on click. |
| 9 | Admin | Receive request → quote (editable) → mark paid → **Activate** entitlements from the request. |
| 10 | Forms | **Separate** company package request vs consultant slot request (different panels — no single “Company or Consultant?” field). |
| 11 | Pricing after login | **Features only** — no AED in Request UI. Sales confirms price offline. |
| 12 | Packages | Keep xlsx named tiers as **predefined templates** for the request UI and admin. |
| 13 | Extras | Driver-based add-ons after a base package is selected (sites, Scope 3 intensity, ESG outputs, white-label, etc.). |
| 14 | Consultant billing grain | **Per slot / per managed client** entitlement profile, not one global type for every client forever. |
| 15 | Voice / help docs | Update later: no “buy Growth online”; point to Request a package / Request slots. |

---

## 3. Commercial flow (end-to-end)

```text
Public site
  → “Explore Free” (no prices)
  → Signup / login

Free active (company or consultant 1-slot)
  → User hits a gate (clean PDF, more sites, more Scope 3, more slots, ESG suite…)

Logged-in
  → Company: Request a package
  → Consultant: Request slots

Pick predefined package + optional extras
  → Submit request (activation or quote)

Admin inbox
  → Review → quote / negotiate → offline payment
  → Mark paid → Activate
  → Watermark off; entitlements match package ± extras
```

---

## 4. Glossary

| Term | Meaning |
|------|---------|
| **Site / branch / location** | Physical place (office, warehouse, factory, shop). Maps to **Locations** in the app. |
| **Entity** | Legal company (trade licence). One LLC with 8 branches = **1 entity, 8 sites**. Parent + subsidiary = **2 entities**. |
| **Package** | Predefined company tier (Scope Basic, Scope Pro, ESG Starter, ESG Complete). |
| **Slot** | One managed client licence for a consultant (per client × reporting year rules as today). |
| **Slot type** | Essential / Standard / Complete / Complete Plus — feature profile for that slot. |
| **Extras / add-ons** | Capacity or feature drives beyond the chosen package (extra sites, Scope 3 unlock, white-label, etc.). |
| **Watermark** | Stamp on Free (and unpaid) exports: trial / not for regulatory submission. |
| **Activation** | Admin turns on entitlements after offline payment. |

---

## 5. Company packages (list prices for sales / admin — not public)

All prices **AED / year, excl. 5% VAT**. Source: `MENetZero_Features_Pricing.xlsx` → Client Plans.

| Package | List AED/yr | Sites | Entities | Team | Years | Scopes | Official GHG/UAE exports | ESG suite | Premium |
|---------|-------------|-------|----------|------|-------|--------|--------------------------|-----------|---------|
| **Free** | 0 | 1 | 1 | 2 | Current | S1&2 full + S3 all cats **1 entry each** | Watermarked test only | Form preview | — |
| **Scope Basic** | 2,500 | **Up to 3** sites (locked Phase 0; ignore xlsx intro “up to 2”) | 1 | 5 | 2 years | S1 & 2 | GHG PDF, MOCCAE, IEQT prep, Excel | — | — |
| **Scope Pro** | 4,999 | Up to 10 | 3 | 15 | 3 years | S1–3 | Full GHG/UAE set | UAE ESG, Scorecard, IFRS, GRI, SASB, ESG management | — |
| **ESG Starter** | 18,000 | Up to 5 | Unlimited | Unlimited | +2 baseline | S1–3 (broad) | Full set | Full suite | White-label, assurance |
| **ESG Complete** | 36,000 | Up to 10 | Unlimited | Unlimited | +4 baseline | S1–3 (broad) | Full set | Full + expanded KPI | White-label, assurance, multi-entity consolidation |

**Always included:** Scope 1 & 2 measurement, factors, calculation trail, guided Quick Input for entitled sources.

**Phase 0 locked:** Scope Basic = **up to 3 sites**. Update `MENetZero_Features_Pricing.xlsx` intro tagline when convenient so it matches.

### Company plan codes (locked)

| UI name | `plan_code` |
|---------|-------------|
| Free | `client_free` (extend entitlements in Phase 1) |
| Scope Basic | `client_scope_basic` |
| Scope Pro | `client_scope_pro` |
| ESG Starter | `client_esg_starter` |
| ESG Complete | `client_esg_complete` |

Legacy codes (`client_starter`, `client_growth`, `client_enterprise`) — migrate or map in a dedicated phase; do not leave two public naming systems.

---

## 6. Consultant slot types (list for sales / admin — not public)

Prices **AED per client-slot per reporting year, excl. VAT**. Source: xlsx → Consultant Agency Slots.

| Slot type | List / slot-yr | Sites/client | Entities | Years | Scopes | GHG/UAE | ESG | Premium | Pack rule |
|-----------|----------------|--------------|----------|-------|--------|---------|-----|---------|-----------|
| **Free trial** | 0 | Free company rules | 1 | Current | S1&2 + S3 1/cat | Watermarked | Preview | — | **1 slot** |
| **Essential** | 499 | Up to 2 | 1 | Current | S1 & 2 | GHG, MOCCAE, IEQT | — | — | **Min 5 slots** |
| **Standard** | 1,499 | Up to 5 | 1 | +1 baseline | S1 & 2 | + Excel, bulk | — | — | Any |
| **Complete** | 4,500 | Up to 5 | 1 | +2 baseline | S1–3 | Full | Full suite | — | Any |
| **Complete Plus** | 7,500 | Up to 10 | Up to 3 | +4 baseline | S1–3 | Full | Full + expanded KPI | White-label, assurance, multi-entity | Any |

**Volume discount (admin/sales):** 5–9 = list; 10–24 = −10%; 25–49 = −18%; 50+ = −25%.

Portal features for all paid types: portfolio dashboard, workspace switcher, managed access, agency team, directory, leads, mid-year slot upgrade path.

### Slot type codes (locked)

| UI name | Code |
|---------|------|
| Essential | `slot_essential` |
| Standard | `slot_standard` |
| Complete | `slot_complete` |
| Complete Plus | `slot_complete_plus` |

---

## 7. Extras / add-on drivers (admin price book — editable)

Customers do **not** shop a 40-item cart. After choosing a base package/slot type, they can request extras. Admin uses a **price book** (seed from ranges below; store editable list prices in DB).

### Company extras (suggested list AED/yr — negotiate per deal)

| Driver | Suggested list | When used |
|--------|----------------|-----------|
| Extra site / branch | 800–1,200 each | Above package site cap |
| Extra legal entity | 2,500–4,000 each | Above entity cap |
| Extra team seats (+5) | 500–1,000 | Above seat cap |
| Extra reporting / baseline year | 1,000–2,000 each | Beyond included years |
| Scope 3 unlock — light | 1,500–2,500 | Beyond Free 1/category |
| Scope 3 unlock — medium | 3,500–6,000 | |
| Scope 3 unlock — heavy | 8,000–15,000 | High volume / many variants |
| Official GHG pack (clean exports) | 2,500 | If somehow outside Basic path |
| UAE ESG Report | 4,000–6,000 | |
| ESG Scorecard | 2,000–3,000 | |
| IFRS S1 & S2 | 3,000–5,000 | |
| GRI (+ index) | 2,500–4,000 | |
| SASB | 1,500–2,500 | |
| Full ESG suite bundle | 12,000–15,000 | Prefer over many singles |
| White-label | 5,000–8,000 | |
| Assurance support | 2,000–3,500 | |
| Multi-entity consolidation | 5,000–10,000 | |
| Custom import templates | 2,000–4,000 | |
| HRIS import | 1,500–3,000 | |

### Consultant extras (suggested)

| Driver | Suggested | Notes |
|--------|-----------|-------|
| Extra site above slot cap | 400–800 / site / yr | |
| Mid-year slot type upgrade | Pay difference | No data loss |
| Extra year unlock | 800–2,000 / client-yr | |
| Scope 3 / ESG on lower slot | Prefer upgrade to Complete / Plus | Cleaner than stacking |
| White-label extras | Prefer Complete Plus or +3,000–5,000 | |
| Extra agency user | 300–600 / user / yr | |
| Essential below 5 slots | Custom or push Standard | Honour min-5 rule or document exception |

**Quote rule:** nearest package/slot total ± extras; admin can override any line.

---

## 8. Request UX (logged-in)

### 8.1 Company — Request a package

1. Show **Current: Free** (or current activated package) + short limits summary.
2. Cards for **Scope Basic / Scope Pro / ESG Starter / ESG Complete** with feature bullets (from xlsx).
3. Select one package.
4. Optional **extras** section (sites, Scope 3 intensity, ESG ticks, premium).
5. Notes + preferred go-live.
6. CTA:
   - Package only → **Request activation**
   - Package + extras → **Request quote / activation**
7. Confirmation: “We’ll confirm pricing and activate after payment.”

**Do not** auto-charge or auto-unlock.

### 8.2 Consultant — Request slots

1. Show current slot usage (e.g. 1/1 Free).
2. Choose **slot type** + **quantity**.
3. Optional: per-slot notes (client size, sites, ESG needs) / extras.
4. Same CTAs as company.
5. Essential: UI should warn **minimum 5 slots** (or block submit under 5).

### 8.3 What replaces old UI

| Old | New |
|-----|-----|
| `/pricing` public | Remove; CTA Explore Free |
| Company upgrade plan cards + checkout | Request a package |
| Consultant agency pack checkout | Request slots |
| PlanGate “Upgrade” deep links | Point to request screens |

Plan & billing (if kept) becomes: **current entitlements + expiry + Request a package** — not a shop.

---

## 9. Admin workflow

### 9.1 Request record (minimum fields)

- Type: `company_package` | `consultant_slots`
- Organisation / consultant org
- Selected package or slot type + quantity
- Extras (JSON lines)
- User notes
- Status: `submitted` → `quoted` → `negotiating` → `paid` → `activated` | `rejected`
- Suggested total / negotiated total / currency
- Invoice / payment reference
- Activated by / at
- Snapshot of entitlements applied on activation

### 9.2 Admin actions

1. Open request → see package + extras.
2. Apply template entitlements ± extras.
3. Edit quote lines / totals.
4. Mark paid (offline).
5. **Activate** → write subscription / slot entitlements; turn off watermark for clean exports.
6. Audit log.

### 9.3 Optional later

- Email quote PDF
- Renewal reminders
- CRM sync

---

## 10. Free entitlements (implementation target)

### Company Free

| Area | Value |
|------|--------|
| Locations | 1 |
| Users | 2 |
| Scope 1 & 2 | Full |
| Scope 3 mode | All categories allowed |
| Scope 3 records per category | **1** |
| Bulk import | Off (or keep off until Basic+) |
| Disclosure forms | Access / preview |
| Disclosure / official PDF exports | Off |
| GHG / selected test exports | **Allowed with watermark** |
| Clean exports | Off |

### Consultant Free

| Area | Value |
|------|--------|
| Slots | 1 |
| Client entitlements | Same as Company Free |
| Pack checkout | Hidden |

Update `PlanEntitlementDefaults::free()` and consultant trial defaults accordingly. Today Free has `scope3_mode => locked` and no watermark — **must change**.

---

## 11. Watermark

- Apply to Free (and any non-activated trial) PDF/Excel exports that are allowed for testing.
- Text e.g. `MENetZero Free Trial — Draft / Not for regulatory submission` + date.
- Paid activated packages: **no watermark**.
- Pre-download notice in UI explaining trial stamp.

**Note:** Watermark does not exist in codebase today — new work.

---

## 12. Phased implementation checklist

Do in order. Do not start phase N+1 until N is shippable unless noted parallel.

### Phase 0 — Align copy & codes ✅ DONE (Aug 2026)

- [x] Fix Scope Basic site count → **3 sites** locked in this doc (update xlsx intro when editing sheet).
- [x] Finalise `plan_code` / slot type codes (§5–6).
- [x] Logged-in Request UI → **features only, no AED**.

### Phase 1 — Free product rules ✅ DONE (Aug 2026)

- [x] Update Free entitlements: S3 all categories, 1 entry per category (`preview_per_category`).
- [x] Confirm S1&2 full on Free; Free users limit = 2.
- [x] Consultant trial = 1 slot with Free client rules (limits no longer incorrectly use Growth for trial).
- [x] Gate messages: “Request a package” / “Request slots” (routes still billing/packs until Phase 4–5).
- [x] Migration `2026_08_03_100000_phase1_free_plan_scope3_preview` syncs `client_free` + `consultant_trial` DB rows.

### Phase 2 — Watermarked trial exports ← **NEXT**

- [ ] Implement watermark on allowed Free exports.
- [ ] UI copy for trial downloads.
- [ ] Ensure clean export flags stay false on Free.

### Phase 3 — Hide public & self-serve commerce

- [ ] Remove (or gut) public `/pricing`.
- [ ] Public CTAs: Explore Free only.
- [ ] Disable/hide company subscription upgrade checkout UI.
- [ ] Disable/hide consultant pack self-checkout UI.
- [ ] Redirect old upgrade URLs to request screens (when those exist) or support/contact interim.

### Phase 4 — Company Request a package

- [ ] Request UI with predefined packages + feature lists.
- [ ] Optional extras UI.
- [ ] Persist `package_requests` (or equivalent).
- [ ] User confirmation email optional.

### Phase 5 — Consultant Request slots

- [ ] Request UI with slot types + quantity + Essential min-5 rule.
- [ ] Same request storage model with `type = consultant_slots`.

### Phase 6 — Admin inbox + quote + activate

- [ ] Admin list/filter requests by status.
- [ ] Quote editor (line items, totals).
- [ ] Mark paid + Activate → apply entitlements to company or slots.
- [ ] Audit fields (who/when/ref).

### Phase 7 — Price book seed

- [ ] Admin-editable price book for packages + extras (§7).
- [ ] Auto-suggest quote from package + extras (still editable).

### Phase 8 — Package definitions in DB

- [ ] Seed Scope Basic / Pro / ESG Starter / Complete (+ slot types) with entitlement JSON matching xlsx.
- [ ] Migrate or map legacy growth/starter/enterprise.
- [ ] Managed-client entitlement resolution: per-slot type when activated.

### Phase 9 — Docs & assistant

- [ ] Update portal guides / ElevenLabs knowledge: Free rules, Request flows, no self-serve prices.
- [ ] Update intro PDF marketing lines when reprinted (no pack grid / self-serve plans).

### Phase 10 — Polish

- [ ] Renewal flow (request renewal tied to previous package).
- [ ] Notifications when request status changes.
- [ ] Analytics: Free → request → activated conversion.

---

## 13. Engineering touchpoints (existing code)

| Area | Where to start |
|------|----------------|
| Free / plan defaults | `app/Data/PlanEntitlementDefaults.php` |
| Runtime gates | `app/Services/PlanEntitlementService.php`, `app/Support/PlanGate.php` |
| Admin entitlement UI | `app/Services/PlanEntitlementAdminService.php`, admin subscription-plan entitlements views |
| Company billing UI | `resources/views/client/subscriptions/*`, `SubscriptionController` |
| Consultant packs | `consultant/packs`, `ConsultantAgency*` services |
| Public pricing | `routes/web.php` `/pricing`, `resources/views/public/pricing.blade.php` |
| Marketing copy configs | `config/plans-company.php`, `config/plans-consultant.php` |
| Feature matrix xlsx | `documentation/MENetZero_Features_Pricing.xlsx` |

Prefer **extending entitlements + request/activation** over parallel gating systems.

---

## 14. Marketing / front messaging

**Allowed public:**

- Explore Free / Start free  
- Product value (Decree-Law 11, Scope 1–3 platform, etc.)  
- Talk to us / Book a demo (optional)  

**Not allowed public:**

- Plan price tables  
- “From AED X” (explicitly decided: **no prices without login**; and current decision is **no front prices at all**)  

**Logged-in:**

- Current Free (or active package) summary  
- Request a package / Request slots  
- Features of predefined tiers  
- “Pricing confirmed by MENetZero; activation after offline payment.”

---

## 15. Success criteria

1. Anonymous visitor never sees AED plan prices.
2. New company and consultant can use Free productively (S1&2 + limited S3 + watermarked tests).
3. Paid path is always Request → offline pay → admin Activate.
4. Sales can quote unfair edge cases via extras without inventing a one-off codebase fork.
5. Admin can see what was requested and what was activated.

---

## 16. Open items (deferred — later phases)

| Item | Status | Notes |
|------|--------|-------|
| Scope Basic sites | **Locked: 3** | Phase 0 |
| Logged-in show list prices? | **Locked: features only** | Phase 0 |
| Multi-entity in product | Open | Entitlement flag only vs real multi-company workspace — scope in Phase 8 |
| Legacy subscribers | Open | Map old starter/growth/enterprise → nearest new package — Phase 8 |

---

## 17. Document history

| Date | Change |
|------|--------|
| Aug 2026 | Initial major-changes doc from commercial workshops + `MENetZero_Features_Pricing.xlsx` |
| Aug 2026 | **Phase 0 complete:** Basic = 3 sites; plan/slot codes locked; Request UI = features only (no AED). Next = Phase 1 Free entitlements. |
| Aug 2026 | **Phase 1 complete:** Free S3 = 1 entry/category; S1&2 full; consultant trial Free limits; gate copy Request a package/slots. Next = Phase 2 watermark. |

---

**Next step:** After you review Phase 1, start **Phase 2** (watermarked Free trial exports).
