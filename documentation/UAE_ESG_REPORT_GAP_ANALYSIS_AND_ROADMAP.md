# UAE ESG Report — Gap Analysis & Phased Roadmap

| | |
|---|---|
| **Version** | 1.2 |
| **Created** | July 2026 |
| **Status** | **Phases 0–E (English) shipped** · Arabic (E.2g) and full CSRD (E.2h) deferred |
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
8. [Phased implementation plan](#8-phased-implementation-plan) — includes [Phase E spec](#phase-e--enterprise-polish-spec-only)
9. [Regression & entitlement safeguards](#9-regression--entitlement-safeguards)
10. [Open decisions (discuss before build)](#10-open-decisions-discuss-before-build)
11. [Success criteria per phase](#11-success-criteria-per-phase)
12. [Appendices](#appendix-a--file-touch-map-phase-a) — includes [English UAT checklist](#appendix-d--english-uat-checklist)

---

## 1. Executive summary

**MenetZero today** is strongest as:

- UAE **GHG inventory** (Scope 1, 2, 3 data entry & calculation)
- **MOCCAE / IEQT** federal Scope 1+2 submission export
- **IFRS S2** climate disclosure workspace (governance, strategy, risks, targets)
- **IFRS S1** broader sustainability narrative workspace
- **Starter GRI** module (manual E/S/G metrics + partial content index)
- **ESG Dashboard** (completeness %, not a full KPI scorecard)

**MenetZero (English, July 2026)** delivers a credible **UAE ESG Report** for Growth and Enterprise tiers — not full DP World parity (regional KPI breakdowns, 100+ page design polish, Arabic bilingual).

**Honest positioning for Scope 3 clients:**

| Tier | Deliverable |
|------|-------------|
| **Starter** | GHG + MOCCAE / IEQT + core measurement |
| **Growth** | Integrated UAE ESG Report PDF, GRI, IFRS S1/S2, Scorecard (~25 KPIs), SASB — English |
| **Enterprise** | Growth + 80+ GRI index, 80+ KPI scorecard, assurance PDF, HRIS CSV import, white-label PDF, auto energy GJ — English |
| **Deferred** | Arabic bilingual PDF (E.2g), full CSRD workflow (E.2h), live HRIS API |
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

**Shipped (Phases 0–D) — Growth tier and above:**

| Feature code | Constant | Purpose | Tier |
|--------------|----------|---------|------|
| `disclosures.access` | — | View disclosure forms | Free+ |
| `disclosures.export` | — | PDF/CSV downloads (master gate) | Growth+ |
| `ghg_pdf` | `EXPORT_GHG_PDF` | GHG Inventory PDF | Starter+ |
| `moccae_pdf` | `EXPORT_MOCCAE_PDF` | MOCCAE S1&2 PDF | Starter+ |
| `ieqt` | `EXPORT_IEQT` | IEQT CSV | Starter+ |
| `excel` | `EXPORT_EXCEL` | Excel breakdown | Starter+ |
| `ifrs_s2_pdf` | `EXPORT_IFRS_S2_PDF` | IFRS S2 PDF | Growth+ |
| `ifrs_s1_pdf` | `EXPORT_IFRS_S1_PDF` | IFRS S1 PDF | Growth+ |
| `gri_pdf` | `EXPORT_GRI_PDF` | GRI PDF | Growth+ |
| `gri_content_index` | `EXPORT_GRI_CONTENT_INDEX` | GRI index CSV (4-col + full cross-walk) | Growth+ |
| `uae_esg_pdf` | `EXPORT_UAE_ESG_PDF` | UAE ESG Report PDF | Growth+ |
| `esg_scorecard` | `EXPORT_ESG_SCORECARD` | ESG Scorecard Excel | Growth+ |
| `sasb_index` | `EXPORT_SASB_INDEX` | SASB index CSV | Growth+ |

**Planned (Phase E) — Enterprise tier only** — see [Phase E spec](#phase-e--enterprise-polish-spec-only):

| Feature code | Constant | Purpose | Shipped |
|--------------|----------|---------|---------|
| `gri_content_index_extended` | `EXPORT_GRI_CONTENT_INDEX_EXTENDED` | 80+ row GRI index CSV | ✅ |
| `esg_scorecard_enterprise` | `EXPORT_ESG_SCORECARD_ENTERPRISE` | 80+ KPI Excel | ✅ |
| `uae_esg_pdf_enterprise` | `EXPORT_UAE_ESG_PDF_ENTERPRISE` | White-label cover PDF (English) | ✅ |
| `assurance_upload` | `FEATURE_ASSURANCE_UPLOAD` | Verifier assurance PDF attach | ✅ |
| `hris_kpi_import` | `FEATURE_HRIS_KPI_IMPORT` | HRIS CSV → scorecard snapshots | ✅ |
| `energy_from_activity` | `FEATURE_ENERGY_FROM_ACTIVITY` | Auto GJ from Quick Input | ✅ |

Enterprise plan keeps `exports: ['*']` wildcard — new codes must be registered in `PlanEntitlementAdminService` and excluded from Growth defaults explicitly.

---

## 5. Section-by-section gap matrix

Legend: **✅** = can fill accurately now · **🟡** = partial / manual · **❌** = gap (new build) · **📎** = upload/narrative only

| # | Section | Status | MenetZero source | Gap notes |
|---|---------|--------|------------------|-----------|
| 1 | Cover Page | ✅ / 🟡 | UAE ESG PDF + Enterprise white-label cover | Growth: standard cover; Enterprise: branded cover |
| 2 | Message from Leadership | ✅ | `esg_report` → `leadership_message` | Narrative in UAE ESG Report |
| 3 | About the Company | ✅ | `esg_report` → `about_company` | |
| 4 | About This Report | ✅ | `esg_report` → `about_report` | Assurance text; Enterprise PDF upload |
| 5 | ESG Strategy | ✅ | `esg_report` → `esg_strategy` | |
| 6 | Materiality Assessment | 🟡 | Phase C materiality matrix | Basic matrix; full CSRD deferred |
| 7 | Stakeholder Engagement | ✅ | Phase C register + GRI 2-29 | |
| 8 | ESG Governance | 🟡 | IFRS S1/S2 + GRI general | Merged in UAE ESG PDF, not single chapter |
| 9 | Environmental Performance | 🟡 | GHG + GRI + Scorecard | Water/waste manual GRI; Enterprise auto energy GJ |
| 10 | Social Performance | 🟡 | GRI + Scorecard + HRIS import | Enterprise regional KPIs via CSV |
| 11 | Governance Performance | 🟡 | GRI governance_metrics | |
| 12 | Sustainable Supply Chain | 🟡 | Phase C supplier register | Not full supplier portal |
| 13 | Climate Risk (IFRS S2) | ✅ | `ClimateRisk`, S2 sections | |
| 14 | Sustainability Performance Dashboard | ✅ | ESG Scorecard 3-year tables | Growth ~25 KPIs; Enterprise 80+ |
| 15 | UN SDGs | ✅ | `esg_report.sdg_map` + GRI cross-walk | |
| 16 | ESG Targets | ✅ | `ReductionTarget` + Phase C targets | |
| 17 | Awards & Recognition | ✅ | `esg_report` → `awards` | |
| 18 | Future Outlook | ✅ | `esg_report` → `future_outlook` | |
| 19 | GRI Content Index | 🟡 | 22 (Growth) / 95 (Enterprise) rows | Not full DP World 80+ on Growth |
| 20 | IFRS S1 Disclosure Index | ✅ | UAE ESG PDF appendix | |
| 21 | IFRS S2 Disclosure Index | ✅ | UAE ESG PDF appendix | |
| 22 | SASB Index | ✅ | Phase D SASB module | Optional sector |
| 23 | GHG Inventory | ✅ | `GhgReportService`, Quick Input | Core strength |
| 24 | Independent Assurance | 🟡 | Text + Enterprise PDF upload | Not in-report PDF embed |
| 25 | Appendices | 🟡 | Methodology in GHG / UAE PDF | No separate appendix manager |

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
| 0.5 | ✅ Regression checklist in §9 + [Appendix D](#appendix-d--english-uat-checklist) |

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

### Phase E — Enterprise polish (English shipped)

**Goal:** DP World–grade depth for large / multi-site clients **without** changing Growth deliverables or breaking lower tiers.

**Status:** E.2a–E.2f and E.2d shipped (English). E.2g (Arabic) and E.2h (CSRD) deferred.

**Principle:** Phase E is **additive**. Growth keeps today’s 22-row GRI index, ~35 KPI scorecard, English UAE ESG PDF, and text-only assurance. Enterprise unlocks extended indexes, uploads, integrations, and design polish via **new entitlement codes** — never by moving existing Growth exports behind Enterprise.

#### E.0 — Tier matrix (locked)

| Capability | Free | Starter | Growth | Enterprise |
|------------|------|---------|--------|------------|
| Disclosure forms (view/edit) | ✅ | ✅ | ✅ | ✅ |
| GHG / MOCCAE / IEQT exports | ❌ | ✅ | ✅ | ✅ |
| IFRS / GRI / UAE ESG / Scorecard / SASB | ❌ | ❌ | ✅ | ✅ |
| GRI index 80+ rows | ❌ | ❌ | ❌ (22 rows) | ✅ |
| Scorecard 80+ KPIs | ❌ | ❌ | ❌ (~35 KPIs) | ✅ |
| Assurance verifier PDF upload | ❌ | ❌ | ❌ (text only) | ✅ |
| HRIS / API KPI feed | ❌ | ❌ | ❌ (CSV import) | ✅ |
| White-label report cover | ❌ | ❌ | ❌ | ✅ |
| Arabic / bilingual UAE ESG PDF | ❌ | ❌ | ❌ | ⏸️ deferred (E.2g) |
| Energy auto from Quick Input | ❌ | ❌ | ❌ | ✅ |
| Full CSRD double-materiality workflow | ❌ | ❌ | 🟡 matrix only | ⏸️ deferred (E.2h) |

**Consultant managed clients:** Mirror direct-client tier of their agency pack (Growth-equivalent today). Enterprise polish only when agency pack explicitly includes `client_enterprise` entitlements.

#### E.1 — Entitlement implementation rules

1. Add constants to `PlanEntitlementService` (see §4.6 table).
2. Add to `PlanEntitlementDefaults::enterprise()` `exports` array explicitly **or** document that `*` includes new codes once registered.
3. **Do not** add Phase E codes to `growth()` defaults.
4. Gate routes with `requireDisclosureExport()` or new `requireEnterpriseFeature()`.
5. Gate UI with `PlanGate` / `x-plan-gated-link` — hide nav items on lower tiers (don’t show locked enterprise links on Growth hub).
6. Services must **omit** enterprise sections when entitlement missing or data empty — never throw.

```php
// Pattern: UaeEsgReportService (enterprise sections optional)
'assurance_attachment' => $this->assuranceService->attachmentIfAllowed($company, $fiscalYear),
'cover' => $this->enterpriseCoverService->buildIfAllowed($company, $fiscalYear),
// Growth build() unchanged — enterprise keys null/absent when not entitled
```

#### E.2 — Sub-phases (build order)

| Sub-phase | Deliverable | Depends on | Est. effort |
|-----------|-------------|------------|-------------|
| **E.2a** | GRI index expansion 22 → 80+ | `config/disclosure.php`, new GRI section fields | Medium — mostly config + forms |
| **E.2b** | Scorecard 80+ KPI pack | `config/esg_scorecard.php`, GRI/social breakdown fields | Medium — config + regional/gender fields |
| **E.2c** | Assurance PDF upload | `CompanyDisclosure` or `company_documents`, reuse `MeasurementDocumentService` pattern | Small |
| **E.2d** | HRIS KPI feed v1 | CSV SFTP/email template + optional REST ingest → `esg_kpi_snapshots` | Medium |
| **E.2e** | Energy from Quick Input | Sum fuel + electricity activity → GRI 302 GJ estimate in scorecard | Small |
| **E.2f** | White-label cover | Company logo + cover template in `uae-esg-pdf-enterprise.blade.php` | Small |
| **E.2g** | Arabic UAE ESG PDF | `lang/ar` strings, RTL PDF CSS, bilingual narrative fields | Large |
| **E.2h** | Full CSRD double materiality (optional) | Extend Phase C matrix with documentation workflow | Large — defer if not contracted |

**Recommended ship order:** E.2a → E.2b → E.2c → E.2e → E.2f → E.2d → E.2g → E.2h.

#### E.3 — Data & flow readiness

| Item | Ready today | Phase E work |
|------|-------------|--------------|
| GRI index pipeline | ✅ `GriContentIndexService` | Add rows + `resolveStatus` sources for new sections |
| KPI storage | ✅ `esg_kpi_snapshots` + CSV import | Expand `config/esg_scorecard.php`; add breakdown fields to GRI `social_hr` / `diversity` |
| GHG single source of truth | ✅ `Measurement` / `GhgReportService` | E.2e reads activity — never duplicate totals |
| Assurance narrative | ✅ text in `about_report` | E.2c adds file path + PDF embed page |
| File upload infra | ✅ Quick Input docs, company logo | E.2c new `assurance_documents` storage disk path |
| SASB / cross-walk | ✅ Phase D | Enterprise PDF includes same; no tier change |
| Arabic | ❌ English only (v1 complete) | E.2g deferred until native reviewer |
| Live HRIS API | ❌ | E.2d v1 = enhanced CSV + audit log; v2 = REST webhook (Enterprise API roadmap) |

#### E.4 — Routes (proposed)

All under `disclosures.` + `disclosureAccess` middleware + `fiscal_year` param.

| Route | Name | Entitlement |
|-------|------|-------------|
| `GET /disclosures/gri/content-index-enterprise.csv` | `gri.content-index-enterprise` | `gri_content_index_extended` |
| `GET /disclosures/esg-scorecard/export-enterprise.xlsx` | `esg-scorecard.export-enterprise` | `esg_scorecard_enterprise` |
| `GET /disclosures/uae-esg-report/report/pdf-enterprise` | `uae-esg.report.pdf-enterprise` | `uae_esg_pdf_enterprise` |
| `POST /disclosures/uae-esg-report/assurance` | `uae-esg.assurance.upload` | `assurance_upload` |
| `POST /disclosures/esg-scorecard/hris-import` | `esg-scorecard.hris-import` | `hris_kpi_import` |

Growth routes (`content-index.csv`, `content-index-full.csv`, `uae-esg.report.pdf`, etc.) **unchanged**.

#### E.5 — Config additions (proposed files)

| File | Purpose |
|------|---------|
| `config/gri_content_index_enterprise.php` | 80+ disclosure rows (extends base index, does not replace) |
| `config/esg_scorecard_enterprise.php` | Additional KPI definitions (regional workforce, age bands, etc.) |
| `config/esg_report_enterprise.php` | Cover templates, bilingual section keys, assurance doc settings |

Base configs (`disclosure.php`, `esg_scorecard.php`, `esg_report.php`) remain the Growth source of truth.

#### E.6 — Regression contract (Growth must not change)

Before merging each E sub-phase:

| Check | Expected |
|-------|----------|
| Growth `uae-esg.report.pdf` | Byte-identical layout/sections vs pre-E (except bugfixes) |
| Growth `gri.content-index.csv` | Still 4 columns |
| Growth `gri.content-index-full.csv` | Still 22 rows + UNGC/WEF/SDG |
| Growth scorecard Excel | ~35 metrics unchanged |
| Starter GHG / IEQT | Unaffected |
| Free disclosure forms | Unaffected |
| Enterprise without E data | Falls back to Growth-equivalent output |

#### E.7 — Progress

| Task | Status |
|------|--------|
| E.0 Tier matrix + entitlement codes | ✅ Spec (this doc) |
| E.2a GRI 80+ | ✅ Enterprise CSV (`content-index-enterprise.csv`); Growth index unchanged |
| E.2b Scorecard 80+ | ✅ Enterprise Excel (`export-enterprise.xlsx`); Growth scorecard UI unchanged |
| E.2c Assurance upload | ✅ Enterprise PDF upload + download; Growth text-only unchanged |
| E.2d HRIS feed | ✅ HRIS CSV import + audit log; Growth scorecard import unchanged |
| E.2e Energy from activity | ✅ Enterprise scorecard metric; Growth GRI manual energy unchanged |
| E.2f White-label cover | ✅ Enterprise PDF (`report/pdf-enterprise`); Growth PDF unchanged |
| E.2g Arabic PDF | ⏸️ Deferred — no Arabic reviewer; English-only v1 complete |
| E.2h CSRD workflow | ⏸️ Deferred — basic materiality matrix sufficient for v1 |

#### E.8 — English release complete (July 2026)

All **English** deliverables for Phases 0–E are shipped. No further English feature work is required before client UAT / sales demos.

**Deferred until product decision + native Arabic reviewer:**

- E.2g bilingual / RTL UAE ESG PDF
- E.2h full CSRD double-materiality workflow
- HRIS REST webhook (CSV import is v1)

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
| GRI enterprise index CSV | `/disclosures/gri/content-index-enterprise.csv` — Enterprise only; 80+ rows |
| Growth index row count | `build()` = 22 rows; `buildEnterprise()` = 80+ rows |
| Growth scorecard row count | `build()` = base metrics only; `buildEnterprise()` = 80+ KPIs |
| Plan gates Free vs Growth | Disclosure export blocked on Free |
| Plan gates Growth vs Enterprise (Phase E) | Enterprise routes 403 / hidden; Growth PDFs unchanged |
| Consultant agency context | `getActiveCompany()` on all new routes |

**Never:**

- Change `MeasurementData.calculated_co2e` formula in report layer
- Store duplicate GHG totals in `CompanyDisclosure` JSON
- Bypass `disclosureAccess` middleware on new routes
- Break existing `config/disclosure.php` keys (extend only)

---

## 10. Open decisions (discuss before build)

| # | Question | Decision (July 2026) |
|---|----------|----------------------|
| 1 | **Target client tier** | Phases 0–D = **Growth** (SME + integrated report). Phase E = **Enterprise only**. |
| 2 | **SASB required for UAE?** | Optional — sector picker (Phase D). Unchanged. |
| 3 | **Minimum scorecard KPIs** | Growth ~35 KPIs. Enterprise 80+ (Phase E.2b). |
| 4 | **Who signs narrative?** | Software = draft; client = legal sign-off. Disclaimer on all PDFs. |
| 5 | **Assurance v1** | Growth: text fields only. Enterprise: PDF upload (Phase E.2c). |
| 6 | **New plan tier?** | No new tier — Enterprise plan gets Phase E entitlements; Growth unchanged. |
| 7 | **Arabic report** | **English only for v1** (Growth + Enterprise). Arabic deferred (E.2g) until native reviewer available. |
| 8 | **Water/waste data** | Growth: GRI forms. Enterprise: optional auto energy from Quick Input (E.2e). |
| 9 | **Move Growth exports to Enterprise?** | **No** — explicit product decision. Enterprise is superset, not gatekeeper. |
| 10 | **HRIS v1 vs API** | CSV/SFTP template first (E.2d); REST webhook with Enterprise API roadmap later. |

---

## 11. Success criteria per phase

| Phase | Measurable outcome |
|-------|-------------------|
| **0** | `UaeEsgReportService::build()` returns merged array; zero new user-facing bugs |
| **A** | One PDF covers all 25 client index sections (placeholder OK for 📎 sections); GHG matches MOCCAE report |
| **B** | Scorecard shows 3-year Scope 1/2/3; ESG dashboard links to scorecard |
| **C** | Social safety KPIs + stakeholder register in report |
| **D** | SASB optional index for logistics sector pilot client |
| **E** | Enterprise: 80+ GRI index + assurance PDF + enterprise scorecard + white-label PDF; Growth unchanged |
| **E (English)** | ✅ Complete — see [Appendix D](#appendix-d--english-uat-checklist) |

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
| Plan entitlement defaults | `app/Data/PlanEntitlementDefaults.php` |
| DP World reference | External — not in repository (see `.gitignore`) |

---

## Appendix C — File touch map (Phase E)

| Action | Likely files |
|--------|--------------|
| New entitlements | `app/Services/PlanEntitlementService.php`, `app/Data/PlanEntitlementDefaults.php`, `PlanEntitlementAdminService.php` |
| Enterprise configs | `config/gri_content_index_enterprise.php`, `config/esg_scorecard_enterprise.php`, `config/esg_report_enterprise.php` |
| Extend services | `GriContentIndexService` (merge enterprise rows when entitled), `EsgScorecardService`, `UaeEsgReportService` |
| New services | `AssuranceDocumentService`, `EnterpriseCoverService`, `HrisKpiImportService`, `EnergyFromActivityService` |
| Controllers | Extend `GriReportController`, `EsgScorecardController`, `UaeEsgReportController` |
| Migration | `assurance_documents` or JSON on `company_disclosures`; optional `hris_import_logs` |
| Views | `uae-esg-pdf-enterprise.blade.php`, assurance upload form on UAE ESG overview |
| Routes | `routes/web.php` — enterprise-only routes with entitlement middleware |
| Pricing UI | `SubscriptionPlanMatrix` — flip “White Label Reports” / API from `coming_soon` when shipped |
| Tests | Growth regression: PDF/CSV snapshots before/after each E sub-phase |

---

## Appendix D — English UAT checklist

Run on **staging or production** before selling Growth / Enterprise ESG. All paths assume `fiscal_year` query param.

### Starter (MOCCAE path — must not break)

| # | Check | Path |
|---|--------|------|
| 1 | Quick Input → diesel / electricity calculate | `/quick-input` |
| 2 | GHG PDF totals match report view | `/reports/export/pdf` |
| 3 | IEQT CSV exports | `/reports/export/ieqt` |

### Growth (integrated ESG — English)

| # | Check | Expected |
|---|--------|----------|
| 4 | Disclosure forms load (no export) | `/disclosures` on Free; exports gated on Growth |
| 5 | UAE ESG Report PDF | `/disclosures/uae-esg-report/report/pdf` |
| 6 | GRI content index 4 columns, 22 rows | `/disclosures/gri/content-index.csv` |
| 7 | GRI full index + UNGC/WEF/SDG | `/disclosures/gri/content-index-full.csv` |
| 8 | ESG Scorecard ~25 KPIs, 3-year columns | `/disclosures/esg-scorecard` + Export Excel |
| 9 | SASB index (sector optional) | `/disclosures/sasb` |
| 10 | GHG in UAE PDF = standalone GHG report | Compare Scope 1/2/3 totals |

### Enterprise (English add-ons only)

| # | Check | Route / action |
|---|--------|----------------|
| 11 | GRI index 95 rows | `content-index-enterprise.csv` |
| 12 | Scorecard 80+ KPIs | `export-enterprise.xlsx` |
| 13 | White-label UAE ESG PDF | `report/pdf-enterprise` (no MenetZero on cover) |
| 14 | Assurance PDF upload/download | UAE ESG overview → assurance section |
| 15 | HRIS CSV import + audit log | Scorecard → HRIS feed; verify `hris_kpi_import_logs` |
| 16 | Energy GJ from Quick Input | Enterprise scorecard row after electricity/fuel entered |
| 17 | Enterprise buttons **hidden** on Growth account | Repeat checks 11–16 — should 403 / not visible |

### Regression rule

If any Growth export from checks 5–9 changes row count, columns, or GHG totals vs pre–Phase E baseline, treat as **release blocker**.

---

*Last updated: July 2026 (v1.2 — Phases 0–E English complete; Arabic deferred).*
