# UAE ESG Report — Gap Analysis & Phased Roadmap

| | |
|---|---|
| **Version** | 1.0 |
| **Created** | July 2026 |
| **Status** | Phase D shipped — SASB index, GRI cross-walk, KPI CSV import, B4SI community |
| **Reference report** | External UAE ESG report index (DP World 2024 used as structural reference only; file not stored in repo) |
| **Client input** | UAE Standard ESG Report Index (Scope 3 purchaser format) |

This document captures the gap analysis between MenetZero’s current capabilities and a **full UAE-style integrated ESG report** (as exemplified by DP World 2024). Use it as the single source of truth before building new modules — avoid re-analysing from scratch.

---

## Table of contents

1. [Executive summary](#1-executive-summary)
2. [What the reference report contains](#2-what-the-reference-report-contains)
3. [Client index → DP World mapping](#3-client-index--dp-world-mapping)
4. [MenetZero today (inventory of existing modules)](#4-menetzero-today-inventory-of-existing-modules)
5. [Section-by-section gap matrix](#5-section-by-section-gap-matrix)
6. [What we can state with 100% accuracy today](#6-what-we-can-state-with-100-accuracy-today)
7. [Architecture principles (360° linkage)](#7-architecture-principles-360-linkage)
8. [Phased implementation plan](#8-phased-implementation-plan)
9. [Regression & entitlement safeguards](#9-regression--entitlement-safeguards)
10. [Open decisions (discuss before build)](#10-open-decisions-discuss-before-build)
11. [Success criteria per phase](#11-success-criteria-per-phase)

---

## 1. Executive summary

**MenetZero today** is strongest as:

- UAE **GHG inventory** (Scope 1, 2, 3 data entry & calculation)
- **MOCCAE / IEQT** federal Scope 1+2 submission export
- **IFRS S2** climate disclosure workspace (governance, strategy, risks, targets)
- **IFRS S1** broader sustainability narrative workspace
- **Starter GRI** module (manual E/S/G metrics + partial content index)
- **ESG Dashboard** (completeness %, not a full KPI scorecard)

**MenetZero is not yet** a full **UAE ESG Report publisher** equivalent to DP World 2024 (63 pages, 100+ KPIs with 3-year trends, UNGC/SDG/SASB cross-walks, independent assurance statements).

**Honest positioning for Scope 3 clients:**

| Tier | Deliverable |
|------|-------------|
| **Now** | GHG + climate (IFRS S2) + partial GRI environmental + MOCCAE compliance |
| **Roadmap** | Unified UAE ESG Report PDF + ESG Scorecard + disclosure indexes |
| **Always client-owned** | CEO message, assurance sign-off, awards, community stories |

---

## 2. What the reference report contains

DP World Sustainability Report 2024 layers:

| Layer | Content | Pages (approx.) |
|-------|---------|-----------------|
| **Introduction** | About report, scope, boundary, frameworks, assurance scope | i–9 |
| **Our approach** | Strategy, governance, materiality, stakeholder engagement, sustainable finance | 10–29 |
| **Our world** | Safety, security, wellbeing, ethics, community, people development, climate | 30–77 |
| **Our future** | Women, education, water | 78–91 |
| **Data & assurance** | ESG scorecard, GRI index, IFRS S2 index, assurance statements, appendices | 94–115 |

**Frameworks referenced:** GRI, UN SDGs, UNGC, UN Women Empowerment Principles, UNGC Sustainable Ocean Principles, WEF Stakeholder Capitalism Metrics, IFRS S1/S2 (best-effort), SASB (sector: TR-MT / TR-RO), ISO 14064 GHG assurance.

**ESG Scorecard (core data product in report):**

- Environment: Scope 1/2/3 by category, intensity, waste, water, environmental incidents
- Social: workforce by gender/region/age, training, turnover, safety (LTIFR, fatalities), community investment
- Governance: ethics, collective bargaining, diversity in management

All with **2022 / 2023 / 2024** columns.

---

## 3. Client index → DP World mapping

| Client section (UAE Standard) | DP World equivalent | Primary data type |
|------------------------------|---------------------|-----------------|
| Cover Page | Branded cover | Design |
| Message from Leadership | CEO statement (p.1) | Narrative |
| About the Company | What we do / where we operate | Narrative + profile |
| About This Report | Purpose, scope, boundary, frameworks (p.i) | Narrative + metadata |
| ESG Strategy | Our World, Our Future (p.10–13) | Narrative |
| Materiality Assessment | Materiality & stakeholder (p.20–22) | Process + matrix |
| Stakeholder Engagement | GRI 2-29, engagement section | Narrative + register |
| ESG Governance | Sustainability governance (p.14–18) | Narrative + structure |
| Environmental Performance | ESG scorecard Environment + Climate (p.60–74, 94–99) | **Quantitative + narrative** |
| Social Performance | ESG scorecard Social (p.100–102) | **Quantitative + narrative** |
| Governance Performance | Ethics, compliance (p.42–45) | Narrative + metrics |
| Sustainable Supply Chain | Scope 3 Cat 1 + supplier due diligence | Quantitative + process |
| Climate Risk (IFRS S2) | Climate change + IFRS S2 index (p.60–68, 112–113) | Structured + narrative |
| Sustainability Performance Dashboard | ESG Scorecard (p.94–102) | **Multi-year KPI tables** |
| UN Sustainable Development Goals | SDG icons in GRI index columns | Mapping table |
| ESG Targets | SBTi, climate & social targets | Structured targets |
| Awards & Recognition | Narrative mentions | Narrative / media |
| Future Outlook | Forward-looking statements | Narrative |
| GRI Content Index | GRI mapping + UNGC + SDG + WEF + SASB (p.103–111) | Index table |
| IFRS S1 Disclosure Index | (DP World maps GRI → S1; dedicated S1 index optional) | Paragraph index |
| IFRS S2 Disclosure Index | IFRS S2.6–S2.37 → report sections (p.112–113) | Paragraph index |
| SASB Index (Optional) | TR-MT / TR-RO columns in GRI index | Sector metrics |
| GHG Inventory | Scorecard + ISO 14064 assurance | **Calculated inventory** |
| Independent Assurance Statement (Optional) | LRQA + B4SI (p.114–115) | External PDF |
| Appendices | Methodology, YoY community data | Tables + uploads |

---

## 4. MenetZero today (inventory of existing modules)

### 4.1 Data & calculation layer

| Module | Routes / entry | Key services / models |
|--------|----------------|----------------------|
| Quick Input (S1/S2/S3) | `/quick-input/*` | `QuickInputController`, `EmissionCalculationService`, `MeasurementData` |
| Bulk import S1/S2 | `/scope12-bulk-import/*` | `Scope12BulkImportService` |
| Reporting settings | `/settings/reporting` | `CompanyReportingSetting` (boundary, GWP, base year, Scope 3 policy) |
| Emission factors (admin) | `/admin/emissions/factors` | `EmissionFactor`, `EmissionFactorSelectionRule` |

### 4.2 GHG reporting (MOCCAE)

| Output | Route | Service |
|--------|-------|---------|
| GHG report web + PDF | `/reports/*` | `GhgReportService`, `ReportController` |
| IEQT CSV export | `/reports/export/ieqt` | `IeqtExportService` |
| Excel breakdown | `/reports/export/excel` | `ResultsBreakdownSheet` |

### 4.3 Disclosure workspace (ISSB / GRI)

| Framework | Routes | Config | Report service |
|-----------|--------|--------|----------------|
| IFRS S2 | `/disclosures/ifrs-s2/*` | `config/disclosure.php` → `ifrs_s2` | `IfrsS2ReportService` |
| IFRS S1 | `/disclosures/ifrs-s1/*` | `ifrs_s1` | `IfrsS1ReportService` |
| GRI | `/disclosures/gri/*` | `gri` | `GriReportService`, `GriContentIndexService` |
| Hub + ESG Dashboard | `/disclosures`, `/disclosures/esg-dashboard` | — | `EsgDashboardService`, `DisclosureService` |

### 4.4 Structured registers (not full scorecard)

| Register | Model | IFRS link |
|----------|-------|-----------|
| Climate risks | `ClimateRisk` | S2 §10 |
| Climate opportunities | `ClimateOpportunity` | S2 §10 |
| Reduction targets | `ReductionTarget` | S2 §33–37 |
| Sustainability risks | `SustainabilityRisk` | S1 |
| Material topics | `MaterialSustainabilityTopic` | S1 / GRI 3 |
| Narrative sections | `CompanyDisclosure` (JSON content) | S1/S2/GRI forms |

### 4.5 GRI content index coverage today

Configured in `config/disclosure.php` → `gri.content_index` (~18 disclosures):

- GRI 2-1, 2-2, 2-6, 2-9, 2-12, 2-29
- GRI 3-1, 3-2
- GRI 302-1, 303-3, 303-5, 305-1/2/3, 306-3
- GRI 401-1, 404-1, 405-1

DP World maps **80+** disclosures plus UNGC, SDG, WEF, SASB columns.

### 4.6 Plan entitlements (do not break)

| Feature code | Purpose |
|--------------|---------|
| `disclosures.access` | View disclosure forms |
| `disclosures.export` | PDF/CSV downloads |
| `EXPORT_GHG_PDF`, `EXPORT_MOCCAE_PDF`, `EXPORT_IEQT` | MOCCAE outputs |
| `EXPORT_IFRS_S2_PDF`, `EXPORT_IFRS_S1_PDF`, `EXPORT_GRI_PDF` | Framework PDFs |
| `EXPORT_GRI_CONTENT_INDEX` | GRI index CSV |

---

## 5. Section-by-section gap matrix

Legend: **✅** = can fill accurately now · **🟡** = partial / manual · **❌** = gap (new build) · **📎** = upload/narrative only

| # | Section | Status | MenetZero source | Gap notes |
|---|---------|--------|------------------|-----------|
| 1 | Cover Page | ❌ | — | No unified ESG report cover builder |
| 2 | Message from Leadership | 📎 | — | No dedicated chapter; could use new narrative section |
| 3 | About the Company | 🟡 | `Company` model | Not ESG-report structured |
| 4 | About This Report | 🟡 | `CompanyReportingSetting` | Missing frameworks list, assurance scope |
| 5 | ESG Strategy | 🟡 | IFRS S1 strategy fields | No dedicated “ESG Strategy” chapter |
| 6 | Materiality Assessment | 🟡 | GRI 3 + material topics | No matrix / double materiality |
| 7 | Stakeholder Engagement | 🟡 | GRI `stakeholder_engagement` field | No stakeholder register |
| 8 | ESG Governance | 🟡 | IFRS S1/S2 + GRI 2 governance | Not merged into one chapter |
| 9 | Environmental Performance | 🟡 | GHG + GRI 302/303/306 | Water/waste manual; no auto energy from Quick Input |
| 10 | Social Performance | 🟡 | GRI 401/404/405 (sparse) | No safety LTIFR, regional workforce, etc. |
| 11 | Governance Performance | 🟡 | GRI ethics + IFRS governance | Narrative only |
| 12 | Sustainable Supply Chain | 🟡 | Scope 3 Cat 1 data (partial) | No supplier due diligence module |
| 13 | Climate Risk (IFRS S2) | ✅ | `ClimateRisk`, S2 sections | Strong |
| 14 | Sustainability Performance Dashboard | ❌ | `EsgDashboardService` (% only) | Not multi-year KPI scorecard |
| 15 | UN SDGs | ❌ | — | No SDG mapping |
| 16 | ESG Targets | 🟡 | `ReductionTarget` | GHG targets only |
| 17 | Awards & Recognition | 📎 | — | Manual |
| 18 | Future Outlook | 📎 | — | Manual |
| 19 | GRI Content Index | 🟡 | `GriContentIndexService` | ~18 rows vs 80+; no UNGC/SDG/WEF columns |
| 20 | IFRS S1 Disclosure Index | 🟡 | S1 report PDF | No ISSB paragraph index table |
| 21 | IFRS S2 Disclosure Index | 🟡 | S2 report PDF | No ISSB paragraph index table |
| 22 | SASB Index | ❌ | — | No sector SASB module |
| 23 | GHG Inventory | ✅ | `GhgReportService`, Quick Input | Core strength |
| 24 | Independent Assurance | 📎 | — | Upload slot only (future) |
| 25 | Appendices | 🟡 | GHG methodology in PDF | No appendix manager |

---

## 6. What we can state with 100% accuracy today

Only claim these when data exists in the system:

1. **GHG inventory** — Scope 1, 2 (location / market split where entered), Scope 3 (entered categories)
2. **GRI 305-1 / 305-2 / 305-3** — auto-mapped from inventory (`GriReportService` → `gri_305`)
3. **MOCCAE Scope 1+2** — IEQT export (`IeqtExportService`)
4. **IFRS S2 climate metrics** — emissions + targets from `IfrsS2ReportService` + `ReductionTarget`
5. **Climate risk register** — structured `ClimateRisk` entries
6. **Reporting boundary / GWP / base year** — `CompanyReportingSetting`
7. **Activity register** — per-line quantity, factor, methodology (`GhgReportService::buildActivityRegister`)

**Do not claim** full GRI Standards compliance, full IFRS S1/S2 index coverage, SDG alignment, or SASB compliance until corresponding modules ship.

---

## 7. Architecture principles (360° linkage)

New UAE ESG Report features **must** read from existing sources of truth — never duplicate GHG numbers in a second store.

```
┌─────────────────────────────────────────────────────────────────┐
│                     SOURCE OF TRUTH LAYER                        │
├─────────────────────────────────────────────────────────────────┤
│  MeasurementData + Measurement  →  GHG totals (kg CO₂e)          │
│  CompanyReportingSetting        →  boundary, GWP, base year      │
│  CompanyDisclosure (JSON)       →  narrative sections            │
│  ClimateRisk / ReductionTarget  →  S2 structured data            │
│  MaterialSustainabilityTopic    →  materiality list              │
└──────────────────────────┬──────────────────────────────────────┘
                           │ read-only aggregation
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                     REPORT AGGREGATION LAYER (NEW)                 │
├─────────────────────────────────────────────────────────────────┤
│  UaeEsgReportService  →  single payload for unified PDF          │
│  EsgScorecardService  →  KPI tables (current + prior years)      │
│  DisclosureIndexService → IFRS S1/S2 paragraph tables              │
│  SdgMappingService    →  topic → SDG goal                        │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                     OUTPUT LAYER                                   │
├─────────────────────────────────────────────────────────────────┤
│  Existing: GHG PDF, IFRS S1/S2 PDF, GRI PDF, IEQT CSV           │
│  New: UAE ESG Report PDF, Scorecard Excel, expanded index CSV    │
└─────────────────────────────────────────────────────────────────┘
```

### Rules for every new feature

1. **GHG numbers** — always from `Measurement` / `MeasurementData` via `GhgReportService` or `IfrsS2ReportService`; never hand-entered in report builder.
2. **Narrative** — stored in `CompanyDisclosure` or new `esg_report` framework sections using same pattern as `DisclosureService::saveSection`.
3. **Fiscal year** — all disclosure routes use `fiscal_year` query param; new routes must follow `Disclosure\*Controller` conventions.
4. **Entitlements** — new exports need `PlanEntitlementService` codes + admin matrix row; gate with `PlanGate` / `ExportReadinessService`.
5. **Consultant workspace** — agency clients use same company context; no separate ESG data model.
6. **Backwards compatibility** — existing `/reports/*` and `/disclosures/*` PDFs unchanged; UAE ESG Report is additive.

### Data flow (GHG → GRI 305 → UAE report)

```
Quick Input / Bulk Import
        → MeasurementData.calculated_co2e
        → Measurement.scope_*_co2e / total_co2e
        → GhgReportService.build()
        → GriReportService.gri_305 (auto)
        → UaeEsgReportService (future)
        → UAE ESG Report PDF
```

---

## 8. Phased implementation plan

### Phase 0 — Foundation & safety (Week 1)

**Goal:** Document, test harness, and guardrails before new UI.

| # | Task | Files / area | Risk if skipped |
|---|------|--------------|-----------------|
| 0.1 | Keep this document updated | `documentation/` | Repeated analysis |
| 0.2 | Add `config/esg_report.php` schema stub (sections, no UI yet) | New config | Ad-hoc hardcoding |
| 0.3 | Create `UaeEsgReportService` skeleton that **delegates** to existing services | `app/Services/` | Duplicate GHG logic |
| 0.4 | Feature flag `esg_report_uae` in `FeatureFlag` or plan entitlement | Admin / plans | Shipping to wrong tier |
| 0.5 | Regression checklist: Quick Input calc, IEQT export, IFRS/GRI PDFs still work | Manual QA script in doc | Break MOCCAE path |

**Exit criteria:** Skeleton service returns merged payload from `GhgReportService` + `GriReportService` + `IfrsS2ReportService` without new DB tables.

**Progress (July 2026):**

| Task | Status |
|------|--------|
| 0.1 | ✅ This document |
| 0.2 | ✅ `config/esg_report.php` — narrative sections, SDG map, IFRS S1/S2 index rows |
| 0.3 | ✅ `app/Services/UaeEsgReportService.php` — delegates to existing report services |
| 0.4 | ✅ `EXPORT_UAE_ESG_PDF` on Growth plan |
| 0.5 | ⏳ Regression checklist in §9 — run before Phase A merge |

---

### Phase A — UAE ESG Report v1 (Weeks 2–4)

**Goal:** One unified PDF matching client index (narrative chapters + auto GHG + indexes). Minimum new data entry.

| # | Deliverable | Implementation | Depends on |
|---|-------------|----------------|------------|
| A.1 | **Report metadata & “About This Report”** | Extend `CompanyReportingSetting` or new `esg_report` disclosure framework: reporting period, frameworks used, assurance status (none/planned/limited/reasonable), report approval | Phase 0 |
| A.2 | **Narrative chapters (rich text)** | New sections in `config/esg_report.php`: `leadership_message`, `about_company`, `esg_strategy`, `future_outlook`, `awards` — stored via `CompanyDisclosure` with `framework = 'esg_report'` | DisclosureService pattern |
| A.3 | **Unified hub nav** | `/disclosures/uae-esg-report` overview + completeness % | `DisclosureService` |
| A.4 | **UAE ESG Report PDF** | `UaeEsgReportController` + Blade/PDF template with client index sections; pull GHG from `GhgReportService`, climate from `IfrsS2ReportService`, GRI snippets from `GriReportService` | A.1–A.2 |
| A.5 | **IFRS S2 disclosure index table** | `IfrsS2IndexService` — map `config/disclosure.php` references + completion status → PDF section | Existing S2 sections |
| A.6 | **IFRS S1 disclosure index table** | `IfrsS1IndexService` — same pattern | Existing S1 sections |
| A.7 | **Expanded GRI content index** | Extend `config/disclosure.php` `gri.content_index` to ~40 core disclosures; add Status + Report location columns | GriContentIndexService |
| A.8 | **SDG mapping table** | `config/esg_report.php` `sdg_map` — material topic / GRI code → SDG goal; render in PDF appendix | Static config + material topics |
| A.9 | **Assurance upload** | `company_disclosures` or `Company` JSON: attach verifier PDF + scope text (GHG only / partial) | File storage pattern from Quick Input docs |
| A.10 | **Plan entitlement** | `EXPORT_UAE_ESG_PDF` on Growth+; preview on Free | PlanEntitlementService |

**Exit criteria:** Client can generate one PDF with all index sections; GHG numbers match standalone GHG report; no duplicate data entry for emissions.

**Explicitly out of scope for Phase A:** ESG Scorecard multi-year tables, SASB, social/safety KPI depth, supply chain module.

---

### Phase B — ESG Scorecard & environmental depth (Weeks 5–8)

**Goal:** “Sustainability Performance Dashboard” = KPI tables with YoY (like DP World p.94–102).

| # | Deliverable | Implementation | Depends on |
|---|-------------|----------------|------------|
| B.1 | **`esg_kpi_snapshots` table** | `company_id`, `fiscal_year`, `category`, `metric_key`, `value`, `unit` — annual snapshot | Migration |
| B.2 | **Auto-populate from GHG** | Snapshot job: Scope 1/2/3 totals, intensity (if denominator in GRI energy) | Measurement totals |
| B.3 | **Manual KPI entry UI** | `/disclosures/esg-scorecard` — forms per category (Environment, Social, Governance) | B.1 |
| B.4 | **3-year column display** | Scorecard service reads Y, Y-1, Y-2 snapshots | B.1 |
| B.5 | **Water Quick Input** (optional) | New source or GRI water form → snapshot | B.1 |
| B.6 | **Waste Quick Input** (optional) | Link existing `WasteData` model or GRI waste → snapshot | B.1 |
| B.7 | **Energy auto-link** | Sum electricity + fuel from `MeasurementData` → GRI 302 GJ estimate | EmissionCalculationService |
| B.8 | **Scorecard export** | Excel + PDF section in UAE ESG Report | A.4 |

**Exit criteria:** Environmental scorecard shows 3-year Scope 1/2/3; social/governance KPIs manually enterable; dashboard replaces simple % with table preview.

**Progress (July 2026):**

| Task | Status |
|------|--------|
| B.1 | ✅ `esg_kpi_snapshots` migration + model |
| B.2 | ✅ Auto-resolve GHG + GRI metrics live (no duplicate totals) |
| B.3 | ✅ `/disclosures/esg-scorecard` UI with manual entry |
| B.4 | ✅ 3-year columns (Y-2, Y-1, Y) |
| B.5–B.7 | 🟡 Water/waste/energy via GRI forms (not separate Quick Input yet) |
| B.8 | ✅ Excel export + scorecard section in UAE ESG PDF |

---

### Phase C — Social, supply chain & governance depth (Weeks 9–12)

| # | Deliverable | Notes |
|---|-------------|-------|
| C.1 | Social KPI pack | Training hours, turnover, workforce by gender, LTIFR, fatalities — scorecard + GRI 403 stubs |
| C.2 | Stakeholder register | Lightweight CRUD: stakeholder group, engagement method, frequency |
| C.3 | Materiality matrix | Visual or table: topic × impact × financial materiality |
| C.4 | Supply chain module | Scope 3 Cat 1 spend/activity + supplier screening checklist (GRI 308/414 narrative) |
| C.5 | ESG targets beyond GHG | Non-climate targets table (water, waste, diversity %) |
| C.6 | Governance KPI pack | Ethics incidents, compliance, board diversity |

**Progress (July 2026):**

| Task | Status |
|------|--------|
| C.1 | ✅ GRI 403 health & safety section + scorecard auto-link |
| C.2 | ✅ Stakeholder register CRUD |
| C.3 | ✅ Materiality matrix (impact × financial) + visual grid |
| C.4 | ✅ Supply chain supplier register + GRI 308/414 narrative |
| C.5 | ✅ Non-climate ESG targets table |
| C.6 | ✅ Governance metrics GRI section + scorecard link |

---

### Phase D — Enterprise optional (Weeks 13+)

| # | Deliverable | Notes |
|---|-------------|-------|
| D.1 | SASB index | Sector picker (e.g. TR-MT logistics); map metrics to scorecard |
| D.2 | UNGC / WEF cross-walk columns | Extra columns on GRI index export |
| D.3 | B4SI / community investment | Only if enterprise clients require |
| D.4 | Double materiality workflow | CSRD-style; larger UX |
| D.5 | HRIS / safety system import | CSV templates for bulk social KPIs |

**Progress (July 2026):**

| Task | Status |
|------|--------|
| D.1 | ✅ SASB sector picker (TR-MT, TR-RO, IF-RE, EM-EP) + index CSV + UAE PDF |
| D.2 | ✅ GRI index UNGC / WEF / SDG cross-walk columns + full CSV export |
| D.3 | ✅ Community investment narrative (B4SI optional) in UAE ESG report |
| D.4 | 🟡 Double materiality via Phase C matrix (full CSRD workflow deferred) |
| D.5 | ✅ ESG Scorecard manual KPI CSV import template |

---

## 9. Regression & entitlement safeguards

Before merging each phase:

| Check | Command / path |
|-------|----------------|
| Quick Input store/calculate | `/quick-input` — diesel factor, Scope 2 methodology override |
| Measurement totals | `MeasurementService::updateMeasurementTotals` |
| GHG PDF | `/reports/export/pdf` |
| IEQT | `/reports/export/ieqt` |
| IFRS S2 PDF | `/disclosures/ifrs-s2/report/pdf` |
| GRI PDF + index CSV | `/disclosures/gri/report/pdf`, `content-index.csv` |
| Plan gates Free vs Growth | Disclosure export blocked on Free |
| Consultant agency context | `getActiveCompany()` on all new routes |

**Never:**

- Change `MeasurementData.calculated_co2e` formula in report layer
- Store duplicate GHG totals in `CompanyDisclosure` JSON
- Bypass `disclosureAccess` middleware on new routes
- Break existing `config/disclosure.php` keys (extend only)

---

## 10. Open decisions (discuss before build)

| # | Question | Options | Recommendation |
|---|----------|---------|----------------|
| 1 | **Target client tier** | SME (MOCCAE + light ESG) vs enterprise (full DP World style) | Ship Phase A for SME; Phase B for enterprise |
| 2 | **SASB required for UAE?** | Yes / Optional / Phase D | Optional — sector-specific |
| 3 | **Minimum scorecard KPIs** | Full DP World (~100) vs UAE SME set (~25) | Start with ~25 env + 15 social + 10 gov |
| 4 | **Who signs narrative?** | Software = draft; client = legal sign-off | Always disclaimer on PDF |
| 5 | **Assurance v1** | Upload PDF only vs workflow | Upload only in Phase A |
| 6 | **New plan tier?** | Bundle UAE ESG PDF in Growth vs new “Enterprise ESG” | Growth export flag first |
| 7 | **Arabic report** | Phase A English only vs bilingual | English first; RTL later |
| 8 | **Water/waste data** | Manual GRI forms vs Quick Input sources | Manual in A; Quick Input in B |

---

## 11. Success criteria per phase

| Phase | Measurable outcome |
|-------|-------------------|
| **0** | `UaeEsgReportService::build()` returns merged array; zero new user-facing bugs |
| **A** | One PDF covers all 25 client index sections (placeholder OK for 📎 sections); GHG matches MOCCAE report |
| **B** | Scorecard shows 3-year Scope 1/2/3; ESG dashboard links to scorecard |
| **C** | Social safety KPIs + stakeholder register in report |
| **D** | SASB optional index for logistics sector pilot client |

---

## Appendix A — File touch map (Phase A)

| Action | Likely files |
|--------|--------------|
| New config | `config/esg_report.php` |
| New service | `app/Services/UaeEsgReportService.php`, `IfrsS2IndexService.php`, `IfrsS1IndexService.php` |
| New controller | `app/Http/Controllers/Disclosure/UaeEsgReportController.php` |
| New views | `resources/views/disclosures/uae-esg-report/*`, `resources/views/reports/uae-esg-pdf.blade.php` |
| Routes | `routes/web.php` under `disclosures.` group |
| Entitlements | `app/Services/PlanEntitlementService.php`, `config/plans-company.php` |
| Nav | `resources/views/disclosures/hub.blade.php`, `nav-client.blade.php` |
| Extend | `config/disclosure.php` (GRI index rows only — no breaking changes) |

---

## Appendix B — Related internal docs

| Document | Path |
|----------|------|
| Disclosure config schema | `config/disclosure.php` |
| Phase 0 reporting foundation migration | `database/migrations/2026_06_09_000100_phase0_reporting_foundation.php` |
| DP World reference | External — not in repository (see `.gitignore`) |

---

*Last updated: July 2026. Update this file when each phase ships.*
