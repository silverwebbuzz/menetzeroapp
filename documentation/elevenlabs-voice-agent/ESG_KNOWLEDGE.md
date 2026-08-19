# ESG, GHG Protocol & Disclosure Knowledge (shared)

Standards and regulatory background for **both** portals. The company and consultant
pre-question files cover *how to use MENetZero*; this file covers *what the concepts
mean* — GHG Protocol, reporting standards, UAE regulation and ESG practice.

Served by **Zero AI** (`App\Services\ZeroAiKnowledgeBase`) in addition to each portal's
own file, and suitable for the ElevenLabs voice agent knowledge base.

**Format is load-bearing.** `## Category`, then alternating `Q:` / `A:` lines, one line
per answer. A reflowed answer is dropped by the parser.

---

### Writing rules for this file

1. **Standards content** (GHG Protocol, IFRS, GRI, SASB) is stable — state it plainly.
2. **Regulatory content** is not. Every legal answer carries an *as-at* date and points
   to the official source. State what the law says; never tell a customer what they
   personally must do.
3. **No invented numbers, thresholds, penalties or deadlines.** If it was not verified,
   the answer says it is not covered here and points to MOCCAE.
4. This is general information, **not legal or assurance advice**.

**Regulatory status verified:** August 2026. Re-check before each release — UAE climate
regulation is actively developing, and a confidently stated stale rule is worse than
no answer.

---

## GHG Protocol basics

Q: What is the GHG Protocol?
A: The most widely used global standard for measuring and reporting greenhouse gas emissions. Its Corporate Standard defines how organisations set boundaries and report Scope 1, 2 and 3. MENetZero follows it.

Q: What is Scope 1?
A: Direct emissions from sources you own or control — fuel burned in your own boilers, generators, furnaces and company vehicles, plus refrigerant leakage from your own equipment.

Q: What is Scope 2?
A: Indirect emissions from the energy you buy and consume — mainly purchased electricity, and also district cooling, steam or heat. You did not burn the fuel, but you caused the emission by using the energy.

Q: What is Scope 3?
A: All other indirect emissions across your value chain, both upstream and downstream — purchased goods and services, business travel, employee commuting, waste, transport, and use of sold products. It is usually the largest share of a company's footprint.

Q: Why is Scope 3 usually the biggest?
A: Because it covers your whole value chain rather than just your own sites. For most service and retail businesses the emissions from what they buy and sell far exceed what they burn on site.

Q: How many Scope 3 categories are there?
A: Fifteen, defined by the GHG Protocol Corporate Value Chain (Scope 3) Standard. Categories 1 to 8 are upstream and 9 to 15 are downstream. You report only the ones relevant to your business.

Q: What are the 15 Scope 3 categories?
A: 1 Purchased goods and services, 2 Capital goods, 3 Fuel and energy related activities, 4 Upstream transport, 5 Waste generated in operations, 6 Business travel, 7 Employee commuting, 8 Upstream leased assets, 9 Downstream transport, 10 Processing of sold products, 11 Use of sold products, 12 End-of-life of sold products, 13 Downstream leased assets, 14 Franchises, 15 Investments.

Q: Do I have to report all 15 Scope 3 categories?
A: No. The GHG Protocol expects you to report the categories that are relevant and material to your business, and to explain which ones you excluded and why.

Q: What is an organisational boundary?
A: The rule deciding which entities count as yours. Under the GHG Protocol you choose either an equity share approach or a control approach (financial or operational). Apply the same choice consistently across all scopes.

Q: What is the difference between operational control and financial control?
A: Operational control means you count 100% of emissions from operations where you set the operating policies. Financial control means you count operations where you hold the financial risk and reward. Pick one and apply it consistently.

Q: What is a base year?
A: The reference year your future performance is measured against. You fix a base year, then track reductions relative to it, recalculating if your structure changes significantly.

Q: When do I have to recalculate my base year?
A: When something structural makes the comparison misleading — acquisitions, disposals, outsourcing, or a methodology change. The principle is that like must be compared with like.

Q: What is an emission factor?
A: A conversion value that turns activity data into emissions — for example kg CO₂e per kWh or per litre. You supply the activity data and the factor does the conversion.

Q: What is tCO₂e?
A: Tonnes of carbon dioxide equivalent. Different gases warm the planet by different amounts, so they are all converted into the equivalent tonnage of CO₂ using GWP values.

Q: What is GWP?
A: Global Warming Potential — how much a gas warms the planet compared with CO₂ over a period, normally 100 years. It is what lets methane and refrigerants be expressed as CO₂e.

Q: Which greenhouse gases are covered?
A: The seven under the Kyoto Protocol — carbon dioxide, methane, nitrous oxide, hydrofluorocarbons, perfluorocarbons, sulphur hexafluoride and nitrogen trifluoride.

Q: What is the difference between location-based and market-based Scope 2?
A: Location-based uses the average grid factor where you consume the electricity. Market-based reflects contracts you have bought, such as renewable energy certificates. The GHG Protocol asks for location-based, and market-based additionally where relevant.

Q: What is activity data?
A: The raw measure of what you did — kWh consumed, litres of fuel, kilometres travelled, tonnes of waste. Multiply it by an emission factor to get emissions.

Q: What is the difference between primary and secondary data?
A: Primary data comes from the actual source, such as a supplier's own measured figure. Secondary data is an average or estimate, such as a published industry factor. Primary data is more accurate and preferred where you can get it.

Q: What is spend-based estimation?
A: Converting money spent into estimated emissions with a factor per unit of currency. It is a reasonable starting point for Scope 3 Category 1 when you have no supplier data, and should be replaced with better data over time.

---

## Reporting standards

Q: What is IFRS S1?
A: The ISSB standard for general sustainability-related financial disclosures. It requires you to explain the sustainability risks and opportunities that could reasonably affect your prospects, across governance, strategy, risk management, and metrics and targets.

Q: What is IFRS S2?
A: The ISSB standard for climate-related disclosures. It builds on IFRS S1 for climate specifically and requires disclosure of Scope 1, 2 and 3 emissions, climate risks and opportunities, transition plans and targets.

Q: What is the difference between IFRS S1 and S2?
A: S1 is the general sustainability baseline covering any material sustainability topic. S2 is climate-specific and sits on top of it. Companies reporting on climate normally apply both together.

Q: What happened to TCFD?
A: The TCFD framework has been consolidated into the ISSB standards. Its four pillars — governance, strategy, risk management, and metrics and targets — are carried through into IFRS S2.

Q: What is GRI?
A: The Global Reporting Initiative — the most widely used standards for reporting an organisation's impacts on the economy, environment and people. It is impact-focused and aimed at a broad set of stakeholders.

Q: What is the difference between GRI and IFRS S1 and S2?
A: GRI reports your impact on the world for all stakeholders. IFRS S1 and S2 report how sustainability affects your business value, for investors. Many companies publish both because they answer different questions.

Q: What is SASB?
A: A set of industry-specific standards identifying the sustainability topics most likely to be financially material in each sector. SASB standards are now maintained under the ISSB and are referenced by IFRS S1.

Q: What is double materiality?
A: Considering both how sustainability issues affect your business financially and how your business affects people and the environment. GRI leans to impact materiality, ISSB to financial materiality, and double materiality covers both.

Q: What is a materiality assessment?
A: The process of identifying which sustainability topics matter most to your business and stakeholders, so your reporting focuses on those rather than everything.

Q: What is a material topic?
A: A sustainability issue significant enough to influence stakeholder decisions or your business performance. Material topics are what your report should concentrate on.

Q: What is limited versus reasonable assurance?
A: Limited assurance is a lower-effort check concluding nothing came to the auditor's attention suggesting the figures are wrong. Reasonable assurance is a higher-effort audit giving a positive opinion. Limited assurance is currently the more common starting point for sustainability data.

Q: What is greenwashing?
A: Making environmental claims that overstate performance or cannot be substantiated. The defence is transparent methodology, stated boundaries, disclosed exclusions and evidence for every claim.

Q: What is a Scope 3 screening?
A: A first-pass estimate across all 15 categories to find where your emissions are concentrated, so detailed data collection targets the categories that actually matter.

---

## Targets & reduction

Q: What is net zero?
A: Cutting emissions across your value chain as far as possible, then neutralising only the small residual with permanent removals. It is not the same as buying offsets against an unchanged footprint.

Q: What is the difference between net zero and carbon neutral?
A: Carbon neutral usually means balancing emissions with offsets and can be claimed without deep cuts. Net zero requires deep absolute reductions first, with only a limited residual neutralised by removals.

Q: What is a science-based target?
A: An emissions reduction target aligned with what climate science says is needed to limit warming, typically to 1.5°C. The Science Based Targets initiative provides the criteria and validates targets.

Q: What is the difference between an absolute and an intensity target?
A: An absolute target cuts total emissions by a set amount. An intensity target cuts emissions per unit — per revenue, per employee, per square metre. Intensity can improve while absolute emissions still rise if you grow.

Q: What is a carbon offset?
A: A credit representing an emission reduction or removal elsewhere, bought to compensate for your own emissions. Under most standards offsets are reported separately and do not reduce your gross Scope 1, 2 or 3 figures.

Q: Do offsets reduce my reported emissions?
A: No. Your gross inventory is reported as measured. Offsets and removals are disclosed separately so readers can see both your actual footprint and what you compensated.

Q: What is a transition plan?
A: A documented plan setting out how the business will move to a lower-carbon model — targets, actions, capital allocation and governance. IFRS S2 asks for disclosure of any such plan.

Q: What is a decarbonisation lever?
A: A specific action that cuts emissions — energy efficiency, switching to renewable electricity, electrifying a fleet, changing refrigerants, or engaging suppliers.

---

## UAE regulation

Q: Is greenhouse gas reporting mandatory in the UAE?
A: Federal Decree-Law No. 11 of 2024 on the Reduction of Climate Change Effects came into force on 30 May 2025 and, together with the MOCCAE national MRV system, establishes GHG reporting obligations based on the GHG Protocol. Status as at August 2026 — confirm current requirements with MOCCAE at moccae.gov.ae or your legal adviser.

Q: What is Federal Decree-Law No. 11 of 2024?
A: The UAE's federal climate change law, issued 28 August 2024 and in force from 30 May 2025. It establishes the national framework for reducing climate change effects, including greenhouse gas measurement and reporting. Status as at August 2026 — see moccae.gov.ae for the current text and implementing decisions.

Q: When do Scope 1 and Scope 2 obligations apply in the UAE?
A: Scope 1 and Scope 2 reporting obligations apply from 30 May 2026 under the federal climate law and the national MRV system. Status as at August 2026 — confirm timing and applicability to your organisation with MOCCAE or your legal adviser.

Q: Is Scope 3 mandatory in the UAE?
A: Not currently. Scope 3 reporting is anticipated to become mandatory from 2027, but as at August 2026 it is voluntary. Reporting it early builds the data history you would need later. Confirm the current position with MOCCAE at moccae.gov.ae.

Q: What is the UAE national MRV system?
A: The federal system for measurement, reporting and verification of greenhouse gas emissions, launched by MOCCAE in October 2025 to collect and verify emissions data under the federal climate law. Status as at August 2026 — see moccae.gov.ae.

Q: What is MOCCAE?
A: The UAE Ministry of Climate Change and Environment, the federal authority responsible for climate policy including the national MRV system and GHG reporting requirements. Official site: moccae.gov.ae

Q: Which standard does UAE reporting use?
A: The GHG Protocol. The federal framework and the national MRV system are built on it, which is also the standard MENetZero applies.

Q: Does the UAE publish its own emission factors?
A: For Scope 1 and 2 the national MRV system provides official UAE factors. As at August 2026 no national Scope 3 factor set has been published, so Scope 3 relies on recognised international sources. Confirm the current position at mrv.ae or moccae.gov.ae.

Q: What emission factor does MENetZero use for UAE electricity?
A: A UAE grid factor of 0.424 kg CO₂e per kWh, sourced from UAE authorities. It is applied automatically when you enter kWh.

Q: Where do Scope 3 factors come from if the UAE has not published any?
A: MENetZero uses UAE-local factors where they exist, then falls back to recognised international sources — DEFRA, IPCC, and spend-based databases. This follows GHG Protocol guidance to use the most representative recognised factor available. Every factor records its source and version.

Q: Does my company have to report under UAE law?
A: Whether a specific organisation falls in scope depends on the implementing decisions and your sector and size, which this assistant does not cover. Check with MOCCAE at moccae.gov.ae or your legal adviser.

Q: What are the penalties for not reporting in the UAE?
A: Penalties are set out in the federal law and its implementing decisions and are not covered here. Consult MOCCAE at moccae.gov.ae or your legal adviser for the current position.

Q: Is this legal advice?
A: No. Zero AI provides general information about standards and publicly stated regulatory positions, with the date they were checked. For obligations specific to your organisation, consult MOCCAE or a qualified legal adviser.

Q: What is the UAE Net Zero 2050 strategy?
A: The UAE's national commitment to achieve net zero greenhouse gas emissions by 2050, announced as a strategic initiative and supported by the federal climate framework. See moccae.gov.ae for current detail.

---

## ESG practice

Q: What does ESG stand for?
A: Environmental, Social and Governance — the three areas used to assess how sustainably and responsibly an organisation operates.

Q: What is the difference between ESG and carbon accounting?
A: Carbon accounting measures greenhouse gas emissions specifically. ESG is broader, covering environmental topics plus social matters such as workforce and safety, and governance such as board oversight and ethics. Emissions are one part of the E.

Q: What goes in the Environmental pillar?
A: Emissions, energy, water, waste, biodiversity and pollution.

Q: What goes in the Social pillar?
A: Workforce topics such as employment, health and safety, training, diversity and inclusion, plus human rights, supply chain labour practices and community impact.

Q: What goes in the Governance pillar?
A: Board structure and oversight, business ethics, anti-corruption, risk management, data protection and executive accountability for sustainability.

Q: What is a stakeholder?
A: Any group affected by your organisation or able to affect it — employees, customers, investors, suppliers, regulators and local communities. Their concerns inform which topics are material.

Q: What is a sustainability KPI?
A: A measurable indicator tracked over time — emissions per revenue, renewable electricity share, water use, safety incident rate, workforce diversity. KPIs show direction of travel rather than a single-year snapshot.

Q: What is supply chain engagement?
A: Working with suppliers to collect real emissions data and encourage reductions. It is how Scope 3 Category 1 moves from spend-based estimates to primary data.

Q: What is a climate risk?
A: A climate-related factor that could harm the business, split into physical risks such as heat, flooding and water stress, and transition risks such as carbon pricing, regulation and shifting customer expectations.

Q: What is the difference between physical and transition risk?
A: Physical risk comes from the climate itself — extreme heat, storms, flooding, water scarcity. Transition risk comes from the move to a low-carbon economy — policy, carbon pricing, technology shifts and reputation.

Q: What is a climate opportunity?
A: A benefit arising from the climate transition — energy cost savings, new low-carbon products, better access to capital, or meeting customer sustainability requirements.

Q: What is scenario analysis?
A: Testing how the business would perform under different climate futures, such as a rapid transition versus a high-warming pathway, to understand resilience. IFRS S2 asks about climate resilience.

Q: Why do customers ask for our emissions data?
A: Because your emissions are part of their Scope 3. Large buyers increasingly ask suppliers for a footprint, so having an inventory is becoming a commercial requirement.

Q: What is a GHG inventory?
A: The complete record of your organisation's emissions for a reporting year, broken down by scope and source, with the methodology and boundaries stated.
