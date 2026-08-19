# MENetZero — Scope 3 Bulk Import & Guided Template

| | |
|---|---|
| **Document** | `SCOPE3_BULK_IMPORT_PLAN.md` |
| **Status** | Locked for phased implementation |
| **Date** | August 2026 |
| **Audience** | Product, engineering |
| **Related** | `PRICING_AND_PLAN_MAJOR_CHANGES.md`, `QUICK_INPUT_AUDIT.md`, `UAE_ESG_REPORT_GAP_ANALYSIS_AND_ROADMAP.md` |
| **Inventoried DB** | `silverwebbuzz_in_menetzero(10).sql` — 7 Aug 2026 snapshot |

Bring Scope 3 to parity with the existing Scope 1 & 2 bulk import: a downloadable
Excel/CSV template, a guided help page, and a validating importer.

Implement **one phase at a time**. Check off §9 as you ship.

---

## 1. Feasibility — settled

The concern was that Scope 3 has "too many variant combinations" to fit one sheet.
Measured against the live database, the opposite is true.

| | Scope 1 & 2 | Scope 3 |
|---|---|---|
| Factor-selection conditions | **6** (`region`, `fuel_category`, `fuel_type`, `unit`, `vehicle_category`, `vehicle_type`) | **2** (`fuel_type`, `unit`) |
| Form fields per source | varies per category | **4**, identical across all 15 |
| Emission factors | many, with vehicle sub-matrices | **66** |
| Ambiguous `(source, activity, unit)` combos | — | **0 of 66** |

`fuel_category`, `vehicle_category`, `vehicle_type` and `vehicle_size` are **100 % empty**
on every Scope 3 factor. Every Scope 3 source exposes the same four fields:

```
fuel_type (select) · unit_of_measure (select) · amount (number) · comments (textarea)
```

`EmissionCalculationService::selectEmissionFactor()` is scope-agnostic and already
resolves Scope 3 correctly with only those two conditions.

**Conclusion: one flat sheet covers all 15 categories.** Scope 3 is structurally
*simpler* to import than Scope 1 & 2.

---

## 2. Granularity — aggregate per category, not per employee

The open question was whether to store per-employee / per-flight detail.

**Evidence — DP World Sustainability Report 2024** (`documentation/final-web---dp-world-esg-2024-eng-v3.pdf`,
Appendix 1, p.120). A ~100 000-employee multinational, LRQA-assured, IFRS/GRI aligned.
Its *entire* Scope 3 disclosure is **ten numbers**:

```
Cat-1  Purchased goods & services                        497,330 tCO2e
Cat-2  Capital Goods                                     429,226
Cat-3  Fuel & Energy related activities                  704,154
Cat-4 & 9  Upstream/Downstream Transport & Distribution  683,352
Cat-5  Waste generated in operations                      54,387
Cat-6  Business Travel by air                              7,687
Cat-7  Employee Commuting                                 72,187
Cat-8  Leased assets upstream                             92,252
Cat-13 Leased assets downstream                          112,733
Cat-15 Investments                                       193,622
```

All employee commuting is **one** number. All air travel is **one** number. No employee
register, flight log, or vehicle list appears anywhere in 120 pages — and LRQA assured
categories 1,2,3,4,5,6,7,8,9,13,15 on that basis.

**Therefore: no new tables.** `measurement_data` already stores exactly what a compliant
report publishes (`activity_type + quantity + unit` per category per year). Per-employee
detail belongs in the **workbook**, not the database — see §5.3.

Two further details worth mirroring: DP World reports **11 of 15** categories (4 and 9
combined), and uses **mixed methods** — "unit-based" for Cat 3/5/6, spend-based elsewhere.
Both are already supported by `buildScope3CoverageMatrix()` and `buildScope3Categories()`.

---

## 3. Plan gating — DECIDED

### 3.1 The blocker

`scope3_records_per_form` is **1** on every plan except Enterprise. Live confirmation
(893 `measurement_data` rows): **max entries per (measurement, category) = 1, zero exceptions.**

With a cap of 1, an upload can insert **15 rows maximum**. A user uploading 12 months ×
15 categories (180 rows) would have 165 rejected. That does not justify a bulk importer.

Note the inconsistency this removes: Scope 1 & 2 already has bulk import **with no
per-form cap** on paid plans. The Scope 3 cap was almost certainly not deliberate.

### 3.2 Decision — raise the cap to 12 on paid plans

| Plan code | Cap now | Cap after | Rationale |
|---|---|---|---|
| `client_free` | 1 | **1** (unchanged) | Upgrade lever; `bulk_import` is already `false` |
| `client_scope_basic` | 1 | **12** | Monthly entries per category |
| `client_scope_pro` | 1 | **12** | " |
| `client_esg_starter` | 1 | **12** | " |
| `client_esg_complete` | 1 | **12** | " |
| `client_starter` (legacy, active) | 1 | **12** | Still has live subscribers |
| `client_growth` (legacy, inactive) | 1 | **12** | Consistency if reactivated |
| `client_enterprise` | -1 | **-1** (unchanged) | Already unlimited |
| `consultant_*` depth plans | delegate | delegate | Inherit from mirrored `client_*` |

12 = one entry per month per category, which covers monthly billing cycles while keeping
a sane ceiling.

### 3.3 Where the cap actually lives — IMPORTANT

`PlanEntitlementService::getScope3RecordsPerFormLimit()` resolves in this order:

1. `scope3_mode === 'locked'` → `0`
2. `scope3_mode === 'full'` → `-1`
3. **Managed clients** → `PlanEntitlementDefaults::forPlanCode(...)['limits']`
4. **Direct subscribers** → `subscription_plans.limits` JSON **in the database**
5. fallback → `1`

So the change must be applied in **two places** or it silently won't take effect:

- **`app/Data/PlanEntitlementDefaults.php`** — drives managed (consultant) clients
- **`subscription_plans.limits` JSON** — drives direct subscribers; needs a migration

Live audit of `subscription_plans` confirms all six active `client_*` plans carry an
explicit `scope3_records_per_form` in their `limits` JSON, so path 4 wins for them today.

---

## 4. What already exists (do not rebuild)

| Capability | Location |
|---|---|
| 15 categories, GHG-numbered | `emission_sources_master.subcategory` = `Cat 1 – …` … `Cat 15 – …` |
| Category breakdown + data quality | `GhgReportService::buildScope3Categories()` |
| Coverage matrix + exclusion reasons | `GhgReportService::buildScope3CoverageMatrix()` |
| Factor resolution | `EmissionCalculationService::selectEmissionFactor()` |
| Per-row plan enforcement | `SubscriptionService::canAddScope3Record()` |
| Bulk-import gate | `PlanEntitlementService::canBulkImport()` / `PlanGate::canBulkImport()` |
| Reference importer to mirror | `Scope12BulkImportService` + `Scope12BulkImportController` |
| Deprecated-source write guard | `MeasurementData::booted()` (added Aug 2026) |

---

## 5. Design

### 5.1 Separate service — not an extension

Build `Scope3BulkImportService` as its own class. `Scope12BulkImportService` juggles six
condition columns with vehicle special-cases; Scope 3 needs two. Merging would mean
scope-conditional branching through a currently clean class.

Shared shape (header mapping, per-row transaction, error collection) is copied, not
inherited — the two differ enough that a shared base class would leak.

### 5.2 Columns

```
location_name · fiscal_year · entry_date · category · activity_type · quantity · unit · notes
```

Against Scope 1 & 2: `fuel_category` and `vehicle_type` dropped (unused in Scope 3);
`sub_type` renamed `activity_type` (matches the `fuel_type` factor column it maps to).

`category` accepts **both** the slug (`business-travel`) and the GHG number (`Cat 6`, `6`) —
users think in GHG Protocol numbering, the DB keys on slug.

### 5.3 Workbook sheets

| # | Sheet | Purpose |
|---|---|---|
| 1 | Instructions | How to fill; where UAE clients find each number |
| 2 | **Data Entry** | The only imported sheet |
| 3 | Reference | All **66** valid `category → activity_type → unit` rows |
| 4 | Your Locations | Exact location names for this company |
| 5 | Calc: Commuting | *Optional.* One row per employee → auto-totals to a Data Entry row |
| 6 | Calc: Flights | *Optional.* One row per trip → auto-totals passenger.km |

Sheets 5–6 are the answer to "we have 200 employees and their flights". Users keep their
detail **in the file** via formulas; the app imports the aggregate. No schema change, and
the working isn't thrown away year over year.

The importer reads sheet 2 only — `extractDataSheet()` already skips known helper sheets
by name; the two new names must be added to its skip list.

### 5.4 Validation rules

| Rule | Behaviour on failure |
|---|---|
| Location must match a company location exactly | Row error, names listed |
| `fiscal_year` 2000–2100 and writable (`canWriteForReportingYear`) | Row error |
| `category` resolves to an active Scope 3 quick-input source | Row error, valid list shown |
| `(activity_type, unit)` resolves to exactly one factor | Row error, Reference sheet pointed to |
| `quantity` numeric ≥ 0 | Row error |
| **Per-row `canAddScope3Record()`** | Row error naming the cap and the plan |
| Whole file | One transaction; partial success reported per row |

The plan check must run **per row**, not once per file — a 180-row upload can legitimately
exhaust the cap partway through, and the user needs to know which rows landed.

---

## 6. Units are strict

Free-typed units will fail. The Reference sheet is the contract:

```
AED · km · passenger.km · tonne.km · tonnes · kWh · litres · cubic metres · m2 · FTE working hour
```

`passenger.km`, `tonne.km` and `FTE working hour` are the ones users get wrong. Data
validation dropdowns on the Data Entry sheet, not free text.

---

## 7. Phases

| Phase | Scope | Files |
|---|---|---|
| **0** | Raise the cap (both places) | `PlanEntitlementDefaults.php`; migration for `subscription_plans.limits` |
| **1** | `Scope3BulkImportService` — headers, category/activity/unit resolution, row import | `app/Services/Scope3BulkImportService.php` |
| **2** | Excel template export (6 sheets) | `app/Exports/Scope3BulkTemplateExport.php` + sheet classes |
| **3** | Controller, routes, Input Data UI (gated on `canBulkImport`) | `Scope3BulkImportController`, `routes/web.php`, quick-input views |
| **4** | Per-row cap enforcement + error surfacing | Phase 1 service + import result view |
| **5** | Help guide page + `TESTING_CHECKLIST.md` entries | `resources/views/quick-input/scope3-help-guide.blade.php` |

Phase 0 ships independently and is worth verifying on live before Phase 1 starts —
if the cap doesn't move, nothing downstream is usable.

---

## 8. Risks

| Risk | Mitigation |
|---|---|
| Cap changed in PHP only, not DB | §3.3 — both, and verify on live before Phase 1 |
| Users free-type units | Dropdowns + Reference sheet + explicit row errors |
| Category naming confusion (slug vs "Cat 6") | Accept both |
| Import writes to a deprecated source | `MeasurementData::booted()` guard already logs this |
| Silent partial import | Per-row errors with line numbers; counts reported |

---

## 9. Checklist

- [x] **Phase 0** — cap raised in `PlanEntitlementDefaults.php`
- [x] **Phase 0** — cap raised in `subscription_plans.limits` via migration
- [ ] **Phase 0** — verified on live: paid plan reports cap 12 *(needs `php artisan migrate`)*
- [x] **Phase 1** — `Scope3BulkImportService` with header mapping + validation
- [x] **Phase 2** — 6-sheet template (Instructions / Data Entry / Reference / Locations / 2 calculators)
- [x] **Phase 2** — `extractDataSheet()` skips the calculator sheets
- [x] **Phase 3** — controller + routes + UI, gated on `canBulkImport`
- [x] **Phase 4** — per-row `canAddScope3Record()` with clear messages *(built in Phase 1 — the cap leaks within a single upload otherwise)*
- [x] **Phase 5** — help guide page
- [x] **Phase 5** — `TESTING_CHECKLIST.md` updated

**Remaining:** run the two pending migrations on live, then work the checklist.
Nothing in this build has been executed — there is no PHP runtime in the dev
environment, so the first template download and upload are the real tests.

### Post-build review — Aug 2026

Two fixes applied after a full-system review:

- [x] **Data Entry no longer ships pre-filled.** `Scope3BulkTemplateExport` put
  `sampleRows()` directly into the **Data Entry** sheet — the one sheet the importer
  reads. A user who downloaded the template and uploaded it back re-imported the five
  examples, and because they reference `Dubai Head Office`, every row failed with
  "Location not found" for most companies. Now Data Entry ships empty and the samples
  sit on a separate **Examples** sheet, matching `Scope12BulkTemplateExport`
  (`extractDataSheet()` already ignores `examples`).
- [x] **Empty-workbook upload gives an honest error.** `extractDataSheet()` fell back to
  `reset($sheets)` when no candidate sheet had rows, handing the Instructions sheet to
  the header mapper and reporting "Unrecognised header row". It now returns `[]`, so
  `importRows()` reports "The uploaded file contains no data rows."

**Still unverified (needs a PHP runtime):** first real template download, a round-trip
upload, and confirmation that the paid cap reads 12 after migration.

---

## 10. Appendix — the 66 valid combinations

Source: `emission_factors` joined to `emission_sources_master`, live snapshot.
Verified **zero ambiguous** `(source, activity_type, unit)` triples.

| Cat | Category (slug) | Activity types → unit |
|---|---|---|
| 1 | Purchased Goods & Services (`purchased-goods`) | Spend - General / Food & Catering / IT & Electronics → **AED**; Material - Food / Glass / Metals / Paper / Plastics → **tonnes**; Water supply / Water treatment → **cubic metres** |
| 2 | Capital Goods (`capital-goods`) | Spend - Capital → **AED**; Material - Metals → **tonnes** |
| 3 | Fuel & Energy Related (`fuel-energy-related`) | Electricity T&D losses / Electricity WTT → **kWh**; Diesel WTT / Petrol WTT → **litres**; Natural gas WTT → **cubic metres** |
| 4 | Upstream Transport (`upstream-transport`) | Van / HGV / HGV Rigid / HGV Articulated → **tonne.km** |
| 5 | Waste in Operations (`waste-operations`) | Mixed - Landfill / Mixed - Combustion / Organic - Landfill / Organic - Composting / Construction - Landfill → **tonnes** |
| 6 | Business Travel (`business-travel`) | Flight - Domestic / Short-haul Economy / Short-haul Business / Long-haul Economy / Premium Economy / Business / First / Rail - National / Rail - International / Light rail / Tram → **passenger.km**; Average car / Taxi → **km** |
| 7 | Employee Commuting (`employee-commuting`) | Local bus / Coach / Rail - National / Light rail / Tram → **passenger.km**; Average car / Motorbike → **km**; Homeworking → **FTE working hour** |
| 8 | Upstream Leased Assets (`upstream-leased`) | Electricity → **kWh**; Floor area → **m2** |
| 9 | Downstream Transport (`downstream-transport`) | Van / HGV / HGV Rigid / HGV Articulated → **tonne.km** |
| 10 | Processing of Sold Products (`processing-sold`) | Energy → **kWh**; Spend → **AED** |
| 11 | Use of Sold Products (`use-sold`) | Electricity → **kWh** |
| 12 | End-of-Life Treatment (`end-of-life`) | Mixed - Landfill / Mixed - Combustion / Organic - Composting → **tonnes** |
| 13 | Downstream Leased Assets (`downstream-leased`) | Electricity → **kWh**; Floor area → **m2** |
| 14 | Franchises (`franchises`) | Electricity → **kWh**; Revenue → **AED** |
| 15 | Investments (`investments`) | Listed equity / Business loans / Project finance / Real estate → **AED** |
