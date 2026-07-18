# MENetZero — Client Presentation & PDF Content Brief

> Prepared from the capabilities currently implemented in the MENetZero software as of July 2026.
>
> This document is source material for creating a polished client-facing PDF or presentation. It is intentionally detailed so a designer or AI presentation tool can shorten, rearrange, and visualize it without inventing product claims.

---

## 1. Brand and presentation direction

**Brand name:** MENetZero  
**Brand meaning:** Middle East Net Zero  
**Website:** [app.menetzero.com](https://app.menetzero.com)  
**Primary audience:** UAE and Middle East companies, sustainability teams, finance teams, operations teams, carbon consultants, and ESG advisory practices.

### Suggested presentation title

**MENetZero**  
**From Emissions Data to Decision-Ready Climate & ESG Reporting**

### Suggested subtitle

UAE-focused carbon accounting, GHG inventory management, ESG disclosures, and consultant-enabled reporting in one secure platform.

### Short positioning statement

MENetZero helps organisations measure Scope 1, Scope 2, and Scope 3 greenhouse-gas emissions, manage supporting activity data, identify carbon hotspots, and prepare structured GHG, MOCCAE, IFRS, GRI, SASB, and UAE ESG reporting outputs.

### One-sentence elevator pitch

MENetZero turns operational data such as electricity, fuel, fleet, refrigerants, travel, waste, and supply-chain activity into traceable emissions calculations and client-ready climate and ESG reports.

### Visual identity

- Primary teal: `#0EA5A3`
- Logo teal: `#009F8E`
- Supporting emerald: `#10B981`
- Dark text: charcoal / near-black
- Backgrounds: white, very light grey, pale teal, and pale emerald
- Visual mood: credible, modern, data-led, calm, clean, UAE-focused
- Logo asset in the project: `public/images/menetzero.svg`

---

## 2. Executive overview

Carbon and ESG reporting often requires teams to collect information from utility bills, fuel records, fleet logs, travel data, waste records, HR data, and supplier information. These records may be spread across departments, spreadsheets, emails, and multiple business locations.

MENetZero brings this work into one structured platform. It provides:

- guided data capture for emissions-producing activities;
- automatic CO₂e calculation using configured emission factors and unit conversions;
- location, reporting-year, source, and scope-level organisation;
- evidence links, notes, dates, and supporting-document uploads;
- dashboards for totals, trends, scope breakdowns, and emission hotspots;
- GHG inventory and UAE-oriented compliance exports;
- disclosure workspaces for IFRS S1, IFRS S2, GRI, SASB, and integrated UAE ESG reporting;
- team permissions, multi-location access, and consultant-managed client workspaces.

The result is a more consistent and reviewable reporting process—from raw activity data to management insight and formal reporting outputs.

---

## 3. The client problem MENetZero solves

### Before MENetZero

- Emissions data is scattered across bills, spreadsheets, folders, and departments.
- Teams are unsure which emission factor or unit conversion to use.
- Scope 1, 2, and 3 boundaries can be difficult to structure consistently.
- Multiple sites and reporting periods create version-control problems.
- Supporting evidence is separated from the reported number.
- Sustainability narratives and quantitative ESG data are managed in different documents.
- Consultants must repeatedly create and maintain separate client workbooks.
- Preparing GHG, UAE, IFRS, and GRI outputs involves duplicating data.

### With MENetZero

- One controlled workspace for organisational emissions and ESG information.
- Guided forms for each emission source and reporting period.
- Automatic factor selection and CO₂e calculation.
- Clear Scope 1, Scope 2, and Scope 3 categorisation.
- Location-level and company-level visibility.
- Evidence and activity records retained alongside calculations.
- Carbon data reused across dashboards, GHG inventories, and disclosure reports.
- Consultants can manage multiple client workspaces through one agency portal.

---

## 4. Who MENetZero is for

### Companies and SMEs

For organisations that want to measure and report their own emissions through a guided, self-service platform.

### Sustainability and ESG teams

For teams responsible for carbon inventories, climate disclosures, ESG performance, targets, and stakeholder reporting.

### Finance and operations teams

For contributors who hold utility, fuel, procurement, travel, fleet, and operational records but need a structured process for supplying data.

### Sustainability consultants and agencies

For advisers managing several SME or mid-market clients who need separate client workspaces, consistent calculation workflows, draft working papers, and report exports.

### Leadership and decision-makers

For management teams that need a concise view of emissions performance, major sources, reporting completeness, and progress against targets.

---

## 5. How MENetZero works

### Step 1 — Set up the organisation

- Create a company account using email or Google sign-in.
- Add company details and upload a company logo.
- Create one or more branches, offices, facilities, or operating locations.
- Define the emission boundary for each location.
- Select the reporting year and configure reporting-methodology settings.

### Step 2 — Capture activity data

- Choose the relevant scope and emission source.
- Enter activity data through guided Quick Input forms.
- Add the activity or bill date, comments, evidence links, and supporting files.
- Use bulk CSV/Excel import where enabled for larger data sets.
- Review, edit, export, or delete individual entries.

### Step 3 — Calculate emissions

- MENetZero selects the applicable configured emission factor using source, fuel, vehicle, unit, location, region, and other relevant conditions.
- Units are converted where necessary.
- CO₂, CH₄, and N₂O can be converted into carbon-dioxide equivalent using configured Global Warming Potential values.
- Results are stored in kilograms of CO₂e and presented to users in tonnes of CO₂e.

### Step 4 — Review performance

- View total emissions and scope-level results.
- Compare Scope 1, Scope 2, and Scope 3.
- Review trends and high-emitting sources.
- Drill into the activity register and calculation details.
- Track disclosure completeness and ESG indicators.

### Step 5 — Prepare outputs

- Generate a GHG inventory.
- Prepare a MOCCAE-focused Scope 1 and 2 view.
- Export data to PDF, Excel, or UAE IEQT-compatible formats where the selected plan permits.
- Build IFRS S1, IFRS S2, GRI, SASB, and UAE ESG outputs.
- Share draft working papers with management, auditors, or sustainability advisers for review.

---

## 6. Current platform capabilities

### A. Carbon accounting across Scope 1, 2, and 3

MENetZero supports structured emissions tracking by GHG Protocol scope.

#### Scope 1 — Direct emissions

Examples supported by the platform include:

- stationary fuel combustion;
- natural gas and other fuels;
- company-owned vehicles and fleet activity;
- refrigerant leakage and fugitive emissions;
- process-related emissions where configured.

#### Scope 2 — Purchased energy

Examples supported by the platform include:

- purchased electricity;
- district cooling;
- purchased heat and steam;
- location-based reporting;
- optional market-based reporting inputs, including supplier-specific factors and renewable-energy percentages.

#### Scope 3 — Value-chain emissions

The reporting engine provides a category structure covering the 15 GHG Protocol Scope 3 categories, including:

1. Purchased goods and services
2. Capital goods
3. Fuel- and energy-related activities
4. Upstream transportation and distribution
5. Waste generated in operations
6. Business travel
7. Employee commuting and home working
8. Upstream leased assets
9. Downstream transportation and distribution
10. Processing of sold products
11. Use of sold products
12. End-of-life treatment of sold products
13. Downstream leased assets
14. Franchises
15. Investments

Scope 3 availability and record limits vary by subscription plan.

### B. Guided Quick Input

Quick Input provides source-specific forms instead of requiring users to understand calculation formulas.

Current workflow capabilities include:

- source-specific dynamic fields;
- year and location selection;
- contextual units and dropdown options;
- vehicle fields that adapt to fuel-use or distance-based data;
- live validation and calculation previews;
- entry date, comments, and evidence links;
- multiple supporting-document uploads;
- individual entry viewing, editing, and deletion;
- filterable entry registers;
- data export;
- a built-in Scope 1 and 2 help guide.

### C. Automated emissions engine

The emissions engine is designed to:

- select an emission factor using configured selection rules;
- match conditions such as fuel type, vehicle class, unit, region, and emission source;
- apply configured unit conversions;
- calculate CO₂e from activity data;
- support component gases such as CO₂, CH₄, and N₂O;
- apply configured AR4, AR5, or AR6 Global Warming Potential values;
- preserve the selected factor and calculation details for traceability;
- distinguish activity-based, spend-based, and mixed Scope 3 data quality in reports.

The platform’s factor library and calculation configuration are centrally managed by authorised administrators.

### D. Multi-location management

- Create and manage multiple branches, offices, facilities, or sites.
- Designate a head-office location.
- activate or deactivate locations;
- configure emission boundaries by location;
- track activity and emissions by reporting year and location;
- produce location-specific reports.

Location and user limits depend on the selected plan.

### E. Dashboard and management insight

The platform provides management-oriented views that can include:

- total organisational emissions in tCO₂e;
- Scope 1, Scope 2, and Scope 3 cards;
- scope contribution breakdown;
- monthly emissions trend;
- source-level contribution and carbon hotspots;
- reporting-year context;
- disclosure and compliance-readiness views;
- executive progress indicators on higher plans.

### F. Evidence and audit-ready working records

For each activity, users can retain:

- activity or bill date;
- quantity and unit;
- source and scope;
- emission factor reference;
- calculated CO₂e and component-gas results where available;
- comments and notes;
- evidence URLs;
- supporting files such as PDF, JPG, JPEG, PNG, or WebP;
- an activity register for review.

These features improve traceability, but MENetZero outputs remain draft working papers until reviewed or independently assured where assurance is required.

### G. Bulk data operations

Where enabled by plan, users can:

- download structured import templates;
- import Scope 1 and Scope 2 records through CSV or Excel;
- validate imported records;
- export activity and result data for offline analysis;
- download report results in Excel format;
- use ESG scorecard CSV and HRIS import templates on applicable plans.

### H. GHG inventory and UAE-focused reporting

MENetZero’s GHG reporting workspace includes:

- organisation and reporting-period information;
- Scope 1, Scope 2, and Scope 3 totals;
- results in tCO₂e;
- source-level results breakdown;
- percentage contribution by scope and source;
- activity register;
- location-based and market-based Scope 2 results;
- biogenic emissions disclosure where recorded;
- Scope 3 category coverage and exclusion reasoning;
- methodology statement;
- company logo in report outputs;
- export-readiness checks and warnings.

Available outputs include:

- general GHG inventory PDF;
- MOCCAE-focused Scope 1 and 2 PDF;
- Excel results export;
- UAE IEQT export for mrv.ae submission preparation.

MENetZero supports the preparation workflow; it does not represent itself as a government authority or guarantee acceptance by a regulator.

### I. IFRS S1 and IFRS S2 disclosure workspaces

#### IFRS S2 — Climate-related disclosures

- governance;
- strategy;
- climate risks;
- climate opportunities;
- metrics and targets;
- GHG metrics;
- disclosure completeness tracking;
- in-app report preview and PDF export on eligible plans.

#### IFRS S1 — Sustainability-related disclosures

- governance and sustainability narratives;
- material topics;
- broader sustainability risks;
- metrics and reporting sections;
- optional inclusion of the IFRS S2 climate appendix;
- report preview and PDF export on eligible plans.

### J. GRI reporting

- GRI sustainability disclosure sections;
- material-topic management;
- environmental, social, and governance metrics;
- GRI 305 emissions mapping from the GHG inventory;
- GRI report preview and PDF export;
- GRI content-index CSV exports;
- extended and enterprise content indexes where enabled.

### K. UAE ESG Report

MENetZero provides an integrated UAE ESG reporting workspace that brings together:

- narrative report chapters;
- GHG inventory data;
- IFRS and GRI references;
- ESG metrics;
- SDG mapping;
- report completeness;
- PDF preview and export;
- enterprise white-label cover options;
- optional assurance-document upload on applicable plans.

### L. ESG dashboard, scorecard, and deeper ESG management

Current ESG features include:

- environmental, social, and governance dashboards;
- three-year KPI scorecards;
- automatic synchronisation of selected GHG indicators;
- manual social and governance data entry;
- Excel scorecard export;
- stakeholder-engagement records;
- materiality matrix;
- supply-chain supplier records;
- climate and sustainability risks and opportunities;
- reduction targets with transition actions such as planned year, investment, and expected abatement;
- SASB sector selection and index export for configured sectors;
- HRIS CSV import on eligible enterprise plans.

Note: reduction targets and transition actions are implemented as planning records. A full scenario-simulation engine is not currently part of the product.

### M. Team, role, and access management

- Invite team members by email.
- Create company-specific roles.
- assign granular module and action permissions;
- control access to locations, emissions, reports, and administrative areas;
- resend or cancel pending invitations;
- update a member’s role;
- support owners, administrators, and staff contributors;
- switch between accessible company workspaces where a user belongs to more than one organisation.

### N. Consultant and agency portal

MENetZero includes a separate workflow for sustainability consultants and agencies.

Capabilities include:

- consultant registration and profile management;
- document submission and admin verification;
- optional public consultant-directory listing;
- profile fields for specialties, emirates, languages, and experience;
- privacy-first lead generation without publishing direct contact details;
- introduction-request inbox;
- one login for multiple managed client organisations;
- separate client workspaces;
- client-workspace switcher;
- read-only or managed workspace access;
- managed-client onboarding;
- agency team and role management;
- client-slot and annual contract management;
- 5, 10, 25, and 50-client agency packs;
- extra client slots and reporting-year unlocks.

MENetZero prepares the data and reporting layer. The consultant remains responsible for professional review, advice, and sign-off.

### O. Plans and commercial flexibility

The company portal currently presents four levels:

- **Free** — introductory Scope 1 and 2 entry and in-app disclosure previews;
- **Starter** — GHG, MOCCAE, IEQT, Excel, and bulk-data capabilities;
- **Growth** — integrated UAE ESG Report, ESG Scorecard, IFRS, GRI, and SASB outputs;
- **Enterprise** — expanded KPI coverage, advanced imports, assurance-document support, white-label outputs, and broader Scope 3 access.

Feature entitlements, limits, and pricing should be confirmed on the live pricing page before a proposal is issued.

---

## 7. Key client benefits

### Simplifies data collection

Guided forms turn utility bills, fuel use, travel, fleet activity, and other operational records into structured emissions entries.

### Reduces spreadsheet dependency

Locations, reporting years, activity records, calculations, evidence, and exports are maintained in one platform.

### Improves consistency

Configured factor-selection rules and unit conversions reduce manual calculation variability.

### Makes results easier to understand

Dashboards show emissions by scope, source, month, location, and reporting period.

### Creates a clearer audit trail

Activity values, factors, calculation outputs, dates, notes, evidence links, and attachments remain connected.

### Reuses data across reporting frameworks

GHG inventory results can feed ESG dashboards and disclosure outputs rather than being re-entered repeatedly.

### Supports UAE reporting workflows

MOCCAE-focused inventory formats and IEQT export preparation are built into the reporting module.

### Scales from one company to a client portfolio

The company portal supports internal teams, while the consultant portal supports managed client workspaces and agency operations.

---

## 8. Suggested 14-page client presentation

### Page 1 — Cover

**MENetZero**  
**From Emissions Data to Decision-Ready Climate & ESG Reporting**

Subheading:  
UAE-focused carbon accounting, compliance preparation, and ESG disclosure management.

Visual: UAE city skyline blended with a clean carbon-data dashboard and a subtle leaf/network motif.

### Page 2 — The reporting challenge

Headline: **Carbon reporting should not depend on disconnected spreadsheets**

Show five pain points:

- fragmented operational data;
- complex emission factors and units;
- difficult multi-location consolidation;
- evidence separated from calculations;
- repeated work across GHG and ESG reports.

### Page 3 — What is MENetZero?

Headline: **One platform from activity data to disclosure-ready output**

Use a five-stage visual:

**Collect → Calculate → Review → Report → Improve**

### Page 4 — Complete emissions coverage

Show three strong columns:

- Scope 1 — fuels, fleet, refrigerants, processes
- Scope 2 — electricity, cooling, heat, steam
- Scope 3 — travel, commuting, waste, purchased goods, logistics, supply chain, and other GHG Protocol categories

### Page 5 — Guided data capture

Headline: **Simple forms for complex carbon data**

Feature highlights:

- source-specific Quick Input;
- location and year selection;
- automatic units and factor matching;
- evidence links and document uploads;
- bulk CSV/Excel import;
- edit, review, and export.

Recommended visual: a real MENetZero Quick Input screenshot inside a laptop/browser frame.

### Page 6 — Calculation and traceability

Headline: **Automated calculations with a reviewable data trail**

Flow:

**Activity Data × Emission Factor × Unit Conversion / GWP = CO₂e**

Supporting text:

- conditional factor selection;
- CO₂, CH₄, and N₂O support;
- configured AR4/AR5/AR6 GWP values;
- calculation references retained;
- results shown in tCO₂e.

### Page 7 — Dashboard and carbon hotspots

Headline: **See where emissions come from**

Show:

- total emissions;
- Scope 1/2/3 split;
- monthly trend;
- largest emission sources;
- location comparison;
- executive reporting context.

Recommended visual: a real dashboard screenshot, cropped to KPI cards and charts.

### Page 8 — GHG inventory and UAE workflow

Headline: **Move from calculated data to structured GHG reporting**

Show deliverables:

- GHG inventory PDF;
- MOCCAE-focused Scope 1 and 2 PDF;
- UAE IEQT export preparation;
- Excel results;
- activity register;
- methodology and reporting-period details.

Include this note in small text:

> MENetZero outputs are draft working papers for review and do not replace regulatory, audit, or assurance approval.

### Page 9 — ESG and disclosure suite

Headline: **One data foundation, multiple reporting outputs**

Use a framework wheel or connected tiles:

- IFRS S1
- IFRS S2
- GRI
- SASB
- UAE ESG Report
- ESG Scorecard

### Page 10 — ESG management beyond carbon

Headline: **Connect emissions performance with wider ESG priorities**

Show:

- three-year KPI scorecard;
- stakeholder engagement;
- materiality matrix;
- supply-chain records;
- climate and sustainability risks;
- opportunities and targets;
- SDG mapping.

### Page 11 — Built for collaboration

Headline: **The right access for every contributor**

Show:

- company owner;
- sustainability manager;
- finance/operations contributor;
- executive reviewer;
- external consultant.

Features:

- email invitations;
- custom roles and granular permissions;
- multi-location collaboration;
- multiple company workspace access;
- evidence-backed review.

### Page 12 — Consultant & agency portal

Headline: **One consultant login, multiple client workspaces**

Show:

- portfolio dashboard;
- managed client onboarding;
- workspace switching;
- team access;
- report preparation;
- consultant directory and lead inbox;
- agency packs for 5, 10, 25, or 50 client slots.

Recommended visual: actual consultant dashboard or client-workspace switcher screenshot.

### Page 13 — Why MENetZero

Use six benefit tiles:

1. UAE and MENA focus
2. Guided carbon accounting
3. Traceable evidence and calculations
4. Multi-framework ESG reporting
5. Company and consultant workflows
6. Flexible plans from Free to Enterprise

### Page 14 — Meet the team and next step

Headline: **Built by a multidisciplinary founding team**

Add team details from Section 10 below.

Call to action:

**Let us help you build a clearer, more consistent carbon and ESG reporting process.**

Website: [app.menetzero.com](https://app.menetzero.com)

---

## 9. Image and screenshot plan

Use genuine product screenshots wherever possible. This makes the presentation credible and demonstrates that the features are implemented.

### Recommended screenshots from MENetZero

1. Company sign-in or company home page
2. Executive dashboard with Scope 1/2/3 cards
3. Quick Input — electricity or vehicle form
4. Quick Input entries register
5. GHG Inventory report preview
6. MOCCAE-focused report option
7. Disclosure hub with IFRS S1, IFRS S2, GRI, and UAE ESG cards
8. ESG Dashboard or ESG Scorecard
9. Materiality matrix or ESG Depth page
10. Consultant dashboard
11. Consultant client-workspace switcher
12. Team & Access page

### Screenshot treatment

- Hide or blur personal names, email addresses, client names, file names, and commercially sensitive values.
- Use a fictional company such as **Green Horizon Trading LLC**.
- Keep the browser frame minimal.
- Crop screenshots to the important interface area.
- Add a soft shadow and 12–16 px rounded corners.
- Do not alter calculated values in a way that makes the screen inconsistent.
- Use one large screenshot per page rather than several unreadable small screenshots.

### AI image prompts for supporting visuals

Use AI-generated visuals only as supporting illustrations, not as fake product screenshots.

#### Cover image prompt

> Premium corporate sustainability presentation cover, modern Dubai and Abu Dhabi business skyline at sunrise, subtle transparent carbon-data charts and connected nodes, clean teal and emerald colour palette, white negative space for title, realistic professional photography, no text, no logos, 16:9.

#### Data-to-report workflow prompt

> Elegant isometric business infographic showing utility bill, fuel pump, company vehicle, office building and airplane data flowing into a secure cloud platform, then transforming into carbon dashboard charts and a professional ESG report, teal emerald navy palette, white background, premium SaaS presentation style, no text, 16:9.

#### Scope 1/2/3 visual prompt

> Clean corporate infographic illustration with three connected sustainability zones: direct company operations and fleet, purchased electricity and cooling, and value-chain suppliers travel waste logistics, Middle East business setting, teal green blue palette, sophisticated minimal style, no text, 16:9.

#### UAE compliance visual prompt

> Professional UAE sustainability reporting scene, ESG analyst reviewing carbon inventory on laptop, subtle UAE skyline and abstract document/report elements, teal and emerald accents, credible corporate atmosphere, natural light, no government logos, no text, 16:9.

#### Consultant portal visual prompt

> Sustainability consultant in a modern Middle East office managing multiple client carbon dashboards across a large screen, diverse professional team, clean premium SaaS aesthetic, teal and emerald accents, realistic photography, no visible brand names, no text, 16:9.

#### ESG strategy visual prompt

> Boardroom sustainability strategy workshop with executives reviewing materiality matrix, emissions trend and ESG scorecard on a large digital display, diverse Middle East business team, modern realistic corporate photography, subtle teal green palette, no text, 16:9.

---

## 10. Founding team and contact details

### Bhavik Koradiya

**Founder & Chief Executive Officer**

- `bhavik@menetzero.com`
- `menetzero@gmail.com`

Suggested profile line:

> Leads MENetZero’s business vision, partnerships, customer strategy, and mission to make credible carbon and ESG reporting more accessible to organisations across the Middle East.

### Vishnu Prajapati

**Founder & Chief Technology Officer**

- `vishnu@menetzero.com`
- `silverwebbuzz@gmail.com`

Suggested profile line:

> Leads the technology platform, product architecture, emissions-data workflows, reporting automation, security, and technical delivery.

### Ojas Bohra

**Co-Founder & Strategic Advisor**

- `ojas.bohra@menetzero.com`
- `ojas.bohra3553@gmail.com`

Suggested profile line:

> Supports corporate strategy, market positioning, partnerships, and long-term growth planning.

### Surabhi Choraria

**Co-Founder & Growth Advisor**

- `surbhi@menetzero.com`
- `surabhijain2801@gmail.com`

Suggested profile line:

> Supports go-to-market planning, brand growth, customer engagement, and market development.

> Note for the presentation designer: obtain approved headshots and confirm the profile descriptions with each founder before publishing. The names, titles, and email addresses above were provided by the MENetZero team.

---

## 11. Suggested client-facing copy

### Short company description — approximately 50 words

MENetZero is a UAE-focused carbon and ESG reporting platform that helps organisations collect activity data, calculate Scope 1, 2, and 3 emissions, identify carbon hotspots, and prepare structured GHG, MOCCAE, IFRS, GRI, SASB, and UAE ESG reporting outputs from one connected workspace.

### Medium company description — approximately 100 words

MENetZero helps UAE and Middle East organisations move from fragmented operational records to a structured, traceable carbon and ESG reporting process. Teams can capture activity data for fuel, electricity, fleet, refrigerants, travel, waste, logistics, and other sources; calculate CO₂e using configured factors and conversions; retain evidence; and analyse results by scope, source, location, and reporting year. The same data foundation supports GHG inventories, MOCCAE-focused outputs, UAE IEQT preparation, IFRS S1/S2, GRI, SASB, ESG scorecards, and an integrated UAE ESG Report. A dedicated consultant portal also supports advisers managing multiple client workspaces.

### Client value proposition

**MENetZero helps you spend less time managing carbon spreadsheets and more time understanding performance, improving data quality, and preparing decision-ready reports.**

### Consultant value proposition

**Manage multiple client carbon and ESG workspaces through one consistent workflow—while retaining the professional review and sign-off that clients value.**

### Suggested closing statement

**Measure with confidence. Report with clarity. Plan for a lower-carbon future.**

---

## 12. Important claim and compliance guardrails

The final presentation should remain commercially confident without overstating the product.

### Safe claims

- “UAE-focused”
- “MOCCAE-ready” or “MOCCAE-focused reporting format”
- “supports IEQT export preparation”
- “GHG Protocol-aligned methodology”
- “supports IFRS S1/S2 and GRI disclosure preparation”
- “draft working papers”
- “configured emission-factor library”
- “consultant review available”

### Claims to avoid unless independently verified

- “MOCCAE certified”
- “government approved”
- “guaranteed regulatory acceptance”
- “UAE mandatory reporting format” without legal citation
- “fully audit-proof”
- “assurance certified”
- “100% accurate”
- “eliminates the need for professional review”
- “real-time government filing”
- “AI-powered” unless a specific production AI feature is demonstrated
- “scenario / reduction simulation engine” — targets and transition actions exist; a full what-if simulation engine does not
- customer-facing “public API” — not implemented as an external API surface
- unverified traction metrics such as seeded consultant counts
- “most popular plan” without usage evidence

### Prefer these safer phrases

- “MOCCAE-focused Scope 1 & 2 export”
- “supports IEQT export preparation for mrv.ae”
- “GHG Protocol-aligned methodology”
- “disclosure preparation for IFRS S1 / S2 and GRI”
- “draft working papers for review”
- “configured emission-factor library”
- “consultant review available through the directory”

### Product nuances for the designer

- Paid checkout and consultant review-pack purchase may still show as “coming soon” depending on live payment-gateway configuration.
- Dashboard “Net Zero progress” is a management visualisation, not a company-specific pathway model.
- SASB currently covers selected configured sectors, not every SASB industry.
- Brand spelling in materials should be standardised as **MENetZero**.

### Required small-print statement

> MENetZero supports carbon accounting and sustainability-report preparation. Outputs are draft working papers and should be reviewed for organisational context, reporting-boundary decisions, regulatory requirements, and assurance needs. MENetZero is not a government authority, auditor, or assurance provider.

---

## 13. Instructions for the PDF/presentation creator

Create a premium, modern, 14-page client presentation using the slide structure in this document.

Requirements:

1. Use the MENetZero logo and teal/emerald visual identity.
2. Use concise client-friendly language; avoid software-development terminology.
3. Use genuine anonymised product screenshots for portal features.
4. Use AI-generated sustainability images only as background or supporting visuals.
5. Keep each page focused on one message.
6. Use icons, diagrams, KPI cards, and short feature lists instead of long paragraphs.
7. Keep all standards and compliance claims within the guardrails above.
8. Present Scope 3 and export availability as plan-dependent.
9. Include the complete founding team and contact details.
10. End with a clear call to action and `app.menetzero.com`.

Recommended output:

- A4 landscape or 16:9 presentation layout
- PDF suitable for email and client meetings
- high-resolution but compressed to a practical email attachment size
- editable source version retained for future updates

