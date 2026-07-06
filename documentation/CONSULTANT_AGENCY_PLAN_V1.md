# MenetZero — Consultant Agency Plan v1

| | |
|---|---|
| **Version** | 1.0 |
| **Date** | June 2026 |
| **Status** | Approved for implementation |
| **Audience** | Product, sales, development |
| **Related** | [COMMERCIAL_PLAN_V1.md](./COMMERCIAL_PLAN_V1.md) (direct SME plans), Consultant directory (C9), Marketplace (C10) |

This document is the **single source of truth** for how **consultants / freelance consultants** provision and manage **multiple end-client workspaces** on MenetZero. Read this before any consultant-channel coding.

---

## 1. Executive summary

**Problem:** UAE carbon consultants serve many SMEs per year. Selling each client a separate direct Growth subscription (2,499 AED) is awkward; consultants want **one login**, **multiple client panels**, **wholesale pricing**.

**Solution:** **Consultant Agency Packs** — consultant org buys **N slots** per **calendar contract year**. Each **slot** = **one managed client** + **one Primary Reporting Year (PRY)** with **full Growth entitlements**. Consultant staff work inside the **same client UI** (Quick Input, disclosures, exports) by **switching workspace context**. End SMEs **do not** get separate logins (v1).

**Anti-churn design (Model B):** One subscription does **not** grant two full reporting years of exports. **Next year** = preview only until **renewal** or **paid reporting-year unlock** — aligned with how UAE accounting software treats fiscal periods ([Zoho Books / Wafeq](https://www.fintrackuae.com/blogs/post/best-accounting-software-for-small-business-zoho-books-wafeq): pay for access period; full work centres on the **active reporting year**).

**One product:** Individual freelancer and multi-consultant agency use the **same product**. Optional **directory listing** (C9) and **marketplace** (C10) are add-ons on the same consultant account — not a separate business line.

---

## 2. Glossary

| Term | Meaning |
|------|---------|
| **Consultant org** | Consultancy org (freelancer or agency) with `company_type = consultant` |
| **Managed client** | End SME workspace (`company_type = client`, `consultant_id` set, `is_direct_client = false`) |
| **Slot** | License for **one managed client** with **one PRY** on the **active consultant contract** |
| **PRY** | **Primary Reporting Year** — e.g. `2026`; only PRY gets **full Growth** exports for that engagement |
| **Contract year** | Consultant subscription period — **calendar-aligned: 1 Jan – 31 Dec** (Option B) |
| **Engagement** | Consultant ↔ managed client link for a specific PRY (and contract year) |
| **Archive** | Engagement ended; data **read-only** for that consultant; does not consume slots |

---

## 3. Product rules (locked)

### 3.1 Slot = client × one PRY (Model B)

| Rule | Detail |
|------|--------|
| 1 slot | 1 managed client + 1 **PRY** with **full Growth** |
| Prior year | **Read-only** / baseline import (optional) |
| Next year | **Preview only** (forms, completeness %) — **no PDF/Excel/IFRS/GRI exports** |
| Next year full work | Requires **contract renewal** + carry-forward **or** **Reporting Year Unlock** add-on |

**Example — Consultant 5, Jun 2026 signup (pro-rata to 31 Dec 2026):**

| Client | PRY | Jun–Dec 2026 | 2027 before renew |
|--------|-----|--------------|-------------------|
| Al Noor | 2026 | Full Growth on 2026 | 2027 preview only |
| Dubai Steel | 2026 | Full Growth on 2026 | 2027 preview only |

Uses **2 slots**, not 4. Consultant cannot deliver **2027 exports** for free inside the same annual payment.

### 3.2 Contract period — calendar-aligned (Option B)

| Item | Rule |
|------|------|
| Standard term | **1 January – 31 December** |
| Mid-year purchase | **Pro-rata** fee from purchase date through **31 Dec** of that calendar year |
| Renewal | Full pack price for next calendar year |
| Extra slots / unlocks | Same **31 Dec** expiry as the consultant contract (pro-rata charge) |

Aligns with UAE annual inventory / MOCCAE rhythm and reduces “Jun–May covers two client reporting cycles” abuse.

### 3.3 Managed client entitlements

Every **active engagement** (slot consumed) receives **`client_growth` entitlements**:

- GHG, MOCCAE, Excel, IEQT, bulk import/export, full help  
- IFRS S1/S2 PDF, GRI PDF + content index  
- Scope 3: 1 entry per category (same as direct Growth)  
- **Consultant directory hidden** on managed clients (consultant *is* the consultant)

**PRY gate:** Export routes check `engagement.primary_reporting_year` — exports allowed for PRY; adjacent years per preview/read-only rules in §3.1.

### 3.4 Authentication & users

| Actor | v1 behaviour |
|-------|----------------|
| **Consultant users** | Up to **10 users** on consultant org; single consultant login area |
| **End SME users** | **No login** — consultant operates workspace on their behalf |
| **MenetZero support** | Supports **consultant org**, not end SMEs (channel model) |

### 3.5 Data ownership & consultant change

| Event | Rule |
|-------|------|
| Consultant downgrade / cancel | Engagements **archive**; consultant retains **read-only** access to historical PRY data |
| SME switches consultancy | **New engagement** under new consultant; **no auto data migration** |
| Same legal SME, new consultant | New managed client record (or new engagement row); old consultant keeps archived history |
| Direct client later | Out of scope v1; optional future “graduate to direct” |

### 3.6 Cannibalization

Wholesale pricing is **not** on the public pricing page. End clients never see 999 vs 2,499. Only **consultant portal** shows agency packs after login.

---

## 4. Commercial packaging

**Display:** Consultant pricing shown **only after consultant login** (not on public `/pricing`).

### 4.1 Pack matrix

| Pack code | Slots | Per-slot price (AED) | Annual total (AED) | Target |
|-----------|-------|----------------------|--------------------|--------|
| `consultant_5` | 5 | 1,299 | **6,495** | Freelancer / solo consultant |
| `consultant_10` | 10 | 999 | **9,990** | Small practice |
| `consultant_25` | 25 | 899 | **22,475** | Growing agency |
| `consultant_50` | 50 | 799 | **39,950** | Large agency |
| `consultant_enterprise` | Custom | Quote | Manual invoice | 50+ slots, SLA, API |

All packs: each slot = **one managed client × one PRY** with **Growth** features for that PRY.

### 4.2 Add-ons

| Add-on | Price (AED) | When |
|--------|-------------|------|
| **Extra slot** | **1,299** per slot | Need more clients than pack size; **pro-rata** to contract 31 Dec |
| **Reporting year unlock** | **999** per client per year | Same client, new PRY (e.g. 2027) **before** annual renewal |
| **Pack upgrade** (e.g. 10→25) | Difference **pro-rata** | Same logic as direct client plan upgrades |

### 4.3 Payment

| Type | Method |
|------|--------|
| Pack purchase / renewal | Online checkout (Razorpay / Cashfree / Stripe) — whole pack. See [PAYMENT_GATEWAYS.md](./PAYMENT_GATEWAYS.md) |
| Enterprise | Manual invoice |
| Extra slot / year unlock | Checkout or invoice after payment |

### 4.4 Worked examples

**A — Freelancer, Consultant 5, starts 15 Jun 2026**

- Pays pro-rata: `6,495 × (199/365) ≈ 3,541 AED` through 31 Dec 2026  
- Adds 2 clients, PRY 2026 each → 2/5 slots used  
- Jan 2027: wants 2027 exports for Al Noor → **renew Consultant 5 for 2027** and select Al Noor with PRY **2027**, **or** buy **Year unlock 999** if still on 2026 contract window (if offered mid-contract)

**B — Agency on Consultant 10, needs 12 clients**

- Buy **Consultant 10** (9,990) + **2 extra slots** @ 1,299 pro-rata — **not** forced to Consultant 25  
- All slots expire **31 Dec** same year

**C — Renewal: 20 active clients, renew Consultant 10**

- Renewal UI: pick **10 clients** + **PRY** (e.g. 2027) for next year  
- Other 10 → **archived** (read-only); slots freed

**D — Mid-year pack upgrade 5 → 10**

- Pro-rata credit/charge same as `SubscriptionService::resolvePlanChange` pattern for direct clients

---

## 5. Renewal & downgrade flow

### 5.1 When renewal is required

- Consultant contract approaching **31 Dec**  
- Or active engagements > available slots after pack change  

### 5.2 Renewal screen (mandatory if active > new slot count)

```
┌─ Engagements ending 31 Dec 2026 ─────────┐   ┌─ Continue in 2027 (max N slots) ─┐
│ ☑ Al Noor LLC      PRY 2026             │   │ ☑ Al Noor LLC      PRY 2027     │
│ ☑ Dubai Steel      PRY 2026             │   │ ☑ Dubai Steel      PRY 2027     │
│ ☐ Finished project PRY 2026             │   │                                 │
└─────────────────────────────────────────┘   └─────────────────────────────────┘
```

| Selection | Result |
|-----------|--------|
| **Carried forward** | New engagement for **contract year 2027**, PRY as chosen; full Growth on that PRY |
| **Not selected** | **Archived**; consultant read-only; no slot cost in 2027 |

### 5.3 Downgrade (e.g. 25 → 10)

Same picker: max **10** continue; rest archive.

---

## 6. Relationship to Consultant programme (C9 / C10)

| Layer | Purpose | Same login? |
|-------|---------|---------------|
| **Agency pack** | Multi-client workspaces, wholesale | consultant portal |
| **Directory profile** (C9) | Public listing, leads | Optional module on consultant account |
| **Marketplace** (C10) | Client buys review pack, escrow | Separate from agency slots |

**Unification path:** Extend `/consultant` (or ) so approved consultants can **subscribe to agency packs** without a second account.

---

## 7. Technical architecture

### 7.1 Existing code to reuse

| Piece | Use |
|-------|-----|
| `companies.company_type` (`consultant` / `client`) | Consultant org vs managed client |
| `companies.is_direct_client` | `false` for managed clients |
| `user_active_context` + account selector | Consultant switches into managed client workspace |
| `PlanEntitlementService` | Growth gates; extend with `ConsultantAgencyEntitlementResolver` |
| `SubscriptionService` / checkout | Pack purchase, pro-rata upgrades |
| Disabled  routes + `ExternalClientController` stub | Starting point for client CRUD |
| `consultants` table (C9) | Link optional `agency_company_id` on consultants |

### 7.2 New concepts (not in DB yet)

| Concept | Purpose |
|---------|---------|
| `consultant_id` on managed `companies` | Who owns the relationship |
| `consultant_subscriptions` | Calendar-year pack + slot limit + expiry |
| `consultant_client_engagements` | Client + PRY + status (active/archived) per contract year |
| `consultant_slot_usage` | Derived: count active engagements ≤ slot limit |
| PRY export gate | Middleware or `PlanEntitlementService` branch |

### 7.3 Entitlement resolution order

For a request in managed client context:

1. Resolve **active engagement** for (`company_id`, `contract_year` or date)  
2. If none / archived → deny write / export  
3. If active → apply **`client_growth` entitlements**  
4. If export → require `reporting_year == engagement.primary_reporting_year` (PRY)  
5. If `reporting_year == PRY + 1` → preview-only (no export routes)  
6. If `reporting_year < PRY` → read-only unless configured for import

Direct clients: unchanged (existing `client_subscriptions`).

---

## 8. Database design (proposed)

### 8.1 `companies` (alter)

| Column | Type | Notes |
|--------|------|-------|
| `consultant_id` | FK nullable → `companies.id` | Set on managed clients; consultant org has `null` |
| `managed_by_consultant` | bool | Shortcut: `consultant_id IS NOT NULL` |

Consultant org: `company_type = consultant`. Managed client: `company_type = client`, `is_direct_client = false`, `consultant_id = <consultant_org_id>`.

### 8.2 `consultant_subscriptions` (new)

| Column | Type | Notes |
|--------|------|-------|
| `id` | PK | |
| `agency_company_id` | FK | |
| `plan_code` | string | `consultant_5`, `consultant_10`, … |
| `contract_year` | smallint | e.g. `2026` (calendar year) |
| `slot_limit` | int | 5 / 10 / 25 / 50 + purchased extras |
| `extra_slots_purchased` | int | Cumulative add-ons |
| `starts_at` | date | Usually Jan 1 or purchase date |
| `expires_at` | date | **31 Dec** contract_year |
| `status` | enum | `active`, `expired`, `cancelled` |
| `payment_transaction_id` | FK nullable | |

### 8.3 `consultant_client_engagements` (new)

| Column | Type | Notes |
|--------|------|-------|
| `id` | PK | |
| `agency_company_id` | FK | |
| `managed_company_id` | FK → `companies` | |
| `consultant_subscription_id` | FK | Contract year link |
| `primary_reporting_year` | smallint | PRY e.g. 2026 |
| `status` | enum | `active`, `archived`, `transferred` |
| `archived_at` | timestamp nullable | |
| `previous_engagement_id` | FK nullable | Same client renewed from prior year |
| `display_name` | string nullable | Consultant's label for client |

**Unique constraint (active):** one `active` engagement per (`managed_company_id`, `agency_company_id`, `contract_year`) — or one active per managed company per consultant at a time.

### 8.4 `consultant_subscription_addons` (new, optional)

| Column | Type | Notes |
|--------|------|-------|
| `consultant_subscription_id` | FK | |
| `addon_type` | enum | `extra_slot`, `reporting_year_unlock` |
| `quantity` | int | |
| `managed_company_id` | FK nullable | For year unlock |
| `reporting_year` | smallint nullable | Unlock target |
| `amount_aed` | decimal | |
| `payment_transaction_id` | FK | |

### 8.5 `subscription_plans` (seed)

Add rows:

| plan_code | price_annual | category |
|-----------|--------------|----------|
| `consultant_5` | 6495 | `consultant` |
| `consultant_10` | 9990 | `consultant` |
| `consultant_25` | 22475 | `consultant` |
| `consultant_50` | 39950 | `consultant` |

Entitlements JSON: Growth template + `consultant_slot_count` + channel flags.

### 8.6 Link C9 consultants (optional)

| `consultants` column | Notes |
|---------------------|-------|
| `agency_company_id` | FK nullable — when consultant buys agency pack |

---

## 9. Implementation phases (execute in order)

| Phase | Scope | Priority | Depends on |
|-------|--------|----------|------------|
| **P11** | This doc + seed `consultant_*` plans + constants (`ConsultantAgencyPlanMatrix.php`) | P0 — **done** | — |
| **P12** | Migrations: `consultant_id`, `consultant_subscriptions`, `consultant_client_engagements`, addons | P0 — **done** | P11 |
| **P13** | `ConsultantAgencySubscriptionService` — slot limit, pro-rata, calendar expiry | P0 — **done** | P12 |
| **P14** | `ConsultantAgencyEntitlementService` + PRY export gate in `PlanEntitlementService` | P0 — **done** | P12 |
| **P15** | Unify auth: consultant account → **consultant portal** shell (nav, billing teaser) | P1 | P11 |
| **P16** | Managed client CRUD (create company, assign PRY, consume slot) | P0 — **done** | P12, P13 |
| **P17** | Workspace switch: consultant user → managed client context (reuse account selector) | P0 — **done** | P16 |
| **P18** | Wire **full client routes** for consultant context (`checkCompanyType` + managed client) | P0 — **done** | P17, P14 |
| **P19** | Consultant checkout: pack purchase + extra slot + year unlock (reuse payment stack) | P1 — **pack checkout done** | P13 |
| **P20** | Renewal UI: select clients + PRY for next contract year | P1 | P13, P16 |
| **P21** | Admin: consultant subscriptions, engagements, slot usage | P2 | P12 |
| **P22** | Archive read-only mode UI + export blocking for non-PRY years | P1 | P14 |
| **P23** | Merge consultant directory profile into consultant portal (optional) | P3 | P15, C9 |

**Suggested first coding task:** **P11 + P12 + P13** (schema + slot math + calendar contract).

---

## 10. Coding checklist (per phase)

### P12 Migration safety

- Backfill: existing `company_type = consultant` companies get placeholder subscription none (admin grant)  
- Managed clients: never call direct `/subscriptions/upgrade`  
- Index: `(agency_company_id, status)` on engagements  

### P14 PRY gate

- Add `reporting_year` to export controllers or read from company settings / session  
- `ConsultantAgencyEntitlementService::canExport($engagement, $exportCode, $reportingYear)`  
- Preview-only banner on non-PRY years (reuse `preview-only-banner` component)  

### P17 Context switch

- Consultant session stores `acting_as_company_id` (managed client)  
- Middleware: verify `managed_company.consultant_id == active_consultant_org.id`  
- Nav: “Back to consultant portal” / client switcher  

### P19 Payments

- `transaction_type`: `consultant_pack`, `consultant_extra_slot`, `consultant_year_unlock`  
- Complete via `PaymentCompletionService` branch → `ConsultantAgencySubscriptionService::completeTransaction`  

### P20 Renewal

- Block new year activation until renewal completed if `now > expires_at`  
- Cron reminder: 30 / 7 days before 31 Dec  

---

## 11. Admin & operations

**Yes — admins can see consultants and their activities.** Delivered in **P21** (full UI); data model in P12+ supports it.

| What admin sees | Phase | Where |
|-----------------|-------|-------|
| Consultant organisations (`company_type = consultant`) | Exists | Admin → Companies (filter: Consultant) |
| Active pack, slots used / remaining, contract year | **P21** | Admin → **Consultants** hub |
| Managed clients per consultant | **P21** | Consultant detail → engagements list |
| Engagements (client, PRY, active/archived) | **P12 + P21** | Consultant detail |
| Payment / checkout history for packs & add-ons | **P21** | Consultant detail → billing |
| Force-archive engagement | **P21** | Consultant detail → actions |
| Manual grant Enterprise pack | **P21** | Admin → Grant consultant subscription |
| Consultant approved but no pack | C9 | Directory only until pack purchased |

| Task | Where |
|------|-------|
| View consultant slot usage | Admin → Consultants (P21) |
| Manual grant Enterprise pack | Admin → Company → Grant consultant subscription |
| Force-archive engagement | Admin → Consultant → Engagements |
| Consultant approved but no pack | Directory only until purchase |

---

## 12. Decision log

| # | Decision | Rationale |
|---|----------|-----------|
| PA1 | Slot = 1 client × 1 PRY (Model B) | Stops “2 reporting years for 1 payment”; matches accounting software mental model |
| PA2 | Calendar contract Jan–Dec (Option B) | UAE annual reporting rhythm; clearer renewal |
| PA3 | Packs 5 / 10 / 25 / 50 | Freelancer entry at 5 slots |
| PA4 | Growth entitlements on every managed client | Consultants need full disclosure exports for PRY |
| PA5 | No end-client login v1 | Channel model; anti-cannibalization |
| PA6 | Renewal picks clients + PRY | Downgrade without data loss; archive rest |
| PA7 | Consultant change = new engagement | Clear data ownership |
| PA8 | Extra slot 1,299; year unlock 999 | Simple upsell without forcing next pack tier |
| PA9 | Consultant = Consultant product | One hub; directory/marketplace optional |
| PA10 | Wholesale pricing consultant-login only | Protect direct Growth pricing |

---

## 13. Open items (v2+)

- Calendar **Jan 1** mandatory first renewal for mid-year signups (vs pro-rata partial year only)  
- Managed client **invited read-only** SME login  
- **Graduate to direct** subscription  
- White-label PDF / consultant logo on exports  
- API access for Enterprise consultants  

---

## 14. Related documents

- [COMMERCIAL_PLAN_V1.md](./COMMERCIAL_PLAN_V1.md) — direct SME plans (Free / Starter / Growth / Enterprise)  
- Consultant directory & marketplace — phases C9, C10 (implemented)  
- Implementation tracking: add P11–P23 rows to COMMERCIAL_PLAN §10 when P11 starts  

---

*End of Consultant Agency Plan v1*
