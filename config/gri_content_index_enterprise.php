<?php

/**
 * Phase E.2a — Additional GRI content index rows (Enterprise export only).
 *
 * Keys here must NOT duplicate config('disclosure.gri.content_index').
 * Maps to existing GRI / esg_report section fields — no duplicate GHG storage.
 *
 * @see documentation/UAE_ESG_REPORT_GAP_ANALYSIS_AND_ROADMAP.md Phase E
 */
return [
    // GRI 2 — General disclosures (extended)
    'GRI 2-3' => ['title' => 'Reporting period, frequency and contact point', 'source' => 'esg_report', 'section' => 'about_report', 'field' => 'report_purpose'],
    'GRI 2-4' => ['title' => 'Restatements of information', 'section' => 'general', 'field' => 'entities_included'],
    'GRI 2-5' => ['title' => 'External assurance', 'source' => 'esg_report', 'section' => 'about_report', 'field' => 'assurance_status'],
    'GRI 2-7' => ['title' => 'Employees', 'section' => 'social_hr', 'field' => 'employees_total'],
    'GRI 2-8' => ['title' => 'Workers who are not employees', 'section' => 'social_hr', 'field' => 'benefits_summary'],
    'GRI 2-10' => ['title' => 'Nomination and selection of the highest governance body', 'section' => 'general', 'field' => 'governance_structure'],
    'GRI 2-11' => ['title' => 'Chair of the highest governance body', 'section' => 'general', 'field' => 'governance_structure'],
    'GRI 2-13' => ['title' => 'Delegation of responsibility for managing impacts', 'section' => 'general', 'field' => 'governance_sustainability_role'],
    'GRI 2-14' => ['title' => 'Role of the highest governance body in sustainability reporting', 'section' => 'general', 'field' => 'governance_sustainability_role'],
    'GRI 2-15' => ['title' => 'Conflicts of interest', 'section' => 'governance_metrics', 'field' => 'compliance_notes'],
    'GRI 2-16' => ['title' => 'Communication of critical concerns', 'section' => 'governance_metrics', 'field' => 'compliance_notes'],
    'GRI 2-17' => ['title' => 'Collective knowledge of the highest governance body', 'section' => 'general', 'field' => 'governance_sustainability_role'],
    'GRI 2-18' => ['title' => 'Evaluation of the performance of the highest governance body', 'section' => 'general', 'field' => 'governance_sustainability_role'],
    'GRI 2-19' => ['title' => 'Remuneration policies', 'section' => 'governance_metrics', 'field' => 'compliance_notes'],
    'GRI 2-20' => ['title' => 'Process to determine remuneration', 'section' => 'governance_metrics', 'field' => 'compliance_notes'],
    'GRI 2-22' => ['title' => 'Statement on sustainable development strategy', 'source' => 'esg_report', 'section' => 'leadership_message', 'field' => 'statement'],
    'GRI 2-23' => ['title' => 'Policy commitments', 'section' => 'general', 'field' => 'ethics_compliance'],
    'GRI 2-24' => ['title' => 'Embedding policy commitments', 'section' => 'general', 'field' => 'ethics_compliance'],
    'GRI 2-25' => ['title' => 'Processes to remediate negative impacts', 'section' => 'supply_chain', 'field' => 'human_rights_due_diligence'],
    'GRI 2-26' => ['title' => 'Mechanisms for seeking advice and raising concerns', 'section' => 'general', 'field' => 'ethics_compliance'],
    'GRI 2-27' => ['title' => 'Compliance with laws and regulations', 'section' => 'governance_metrics', 'field' => 'compliance_notes'],
    'GRI 2-28' => ['title' => 'Membership associations', 'section' => 'governance_metrics', 'field' => 'compliance_notes'],
    'GRI 2-30' => ['title' => 'Collective bargaining agreements', 'section' => 'governance_metrics', 'field' => 'collective_bargaining_percent'],

    // GRI 3
    'GRI 3-3' => ['title' => 'Management of material topics', 'section' => 'material_topics_process', 'field' => 'process_description'],
    'GRI 3-3b' => ['title' => 'Double materiality assessment', 'source' => 'materiality_matrix'],

    // GRI 201 — Economic performance
    'GRI 201-1' => ['title' => 'Direct economic value generated and distributed', 'source' => 'esg_report', 'section' => 'about_company', 'field' => 'company_overview'],
    'GRI 201-2' => ['title' => 'Financial implications and other risks and opportunities due to climate change', 'source' => 'esg_report', 'section' => 'esg_strategy', 'field' => 'strategy_summary'],
    'GRI 201-3' => ['title' => 'Defined benefit plan obligations and other retirement plans', 'section' => 'social_hr', 'field' => 'benefits_summary'],
    'GRI 201-4' => ['title' => 'Financial assistance received from government', 'section' => 'governance_metrics', 'field' => 'compliance_notes'],

    // GRI 203 — Indirect economic impacts
    'GRI 203-1' => ['title' => 'Infrastructure investments and services supported', 'source' => 'esg_report', 'section' => 'community_impact', 'field' => 'investment_methodology'],
    'GRI 203-2' => ['title' => 'Significant indirect economic impacts', 'source' => 'esg_report', 'section' => 'community_impact', 'field' => 'total_investment_aed'],

    // GRI 205 — Anti-corruption
    'GRI 205-1' => ['title' => 'Operations assessed for risks related to corruption', 'section' => 'governance_metrics', 'field' => 'compliance_notes'],
    'GRI 205-2' => ['title' => 'Communication and training about anti-corruption', 'section' => 'governance_metrics', 'field' => 'ethics_training_percent'],
    'GRI 205-3' => ['title' => 'Confirmed incidents of corruption and actions taken', 'section' => 'governance_metrics', 'field' => 'ethics_incidents'],

    // GRI 302 — Energy (extended)
    'GRI 302-3' => ['title' => 'Energy intensity', 'section' => 'energy', 'field' => 'energy_intensity_value'],
    'GRI 302-4' => ['title' => 'Reduction of energy consumption', 'section' => 'energy', 'field' => 'renewable_percent'],
    'GRI 302-5' => ['title' => 'Reductions in energy requirements of products and services', 'section' => 'energy', 'field' => 'methodology_notes'],

    // GRI 303 — Water (extended)
    'GRI 303-1' => ['title' => 'Interactions with water as a shared resource', 'section' => 'water', 'field' => 'water_stressed_areas_notes'],
    'GRI 303-2' => ['title' => 'Management of water discharge-related impacts', 'section' => 'water', 'field' => 'water_stressed_areas_notes'],
    'GRI 303-4' => ['title' => 'Water discharge', 'section' => 'water', 'field' => 'discharge_total_m3'],

    // GRI 305 — Emissions (extended)
    'GRI 305-4' => ['title' => 'GHG emissions intensity', 'section' => 'energy', 'field' => 'energy_intensity_value'],
    'GRI 305-5' => ['title' => 'Reduction of GHG emissions', 'source' => 'esg_report', 'section' => 'esg_strategy', 'field' => 'priority_themes'],
    'GRI 305-6' => ['title' => 'Emissions of ozone-depleting substances (ODS)', 'section' => 'energy', 'field' => 'methodology_notes'],
    'GRI 305-7' => ['title' => 'Nitrogen oxides (NOx), sulfur oxides (SOx), and other significant air emissions', 'section' => 'energy', 'field' => 'methodology_notes'],

    // GRI 306 — Waste (extended)
    'GRI 306-1' => ['title' => 'Waste generation and significant waste-related impacts', 'section' => 'waste', 'field' => 'waste_non_hazardous_tonnes'],
    'GRI 306-2' => ['title' => 'Management of significant waste-related impacts', 'section' => 'waste', 'field' => 'waste_hazardous_tonnes'],
    'GRI 306-4' => ['title' => 'Waste diverted from disposal', 'section' => 'waste', 'field' => 'waste_recycled_tonnes'],
    'GRI 306-5' => ['title' => 'Waste directed to disposal', 'section' => 'waste', 'field' => 'waste_landfill_tonnes'],

    // GRI 401 — Employment (extended)
    'GRI 401-2' => ['title' => 'Benefits provided to full-time employees', 'section' => 'social_hr', 'field' => 'benefits_summary'],
    'GRI 401-3' => ['title' => 'Parental leave', 'section' => 'social_hr', 'field' => 'parental_leave_return_rate'],

    // GRI 403 — OHS (extended)
    'GRI 403-1' => ['title' => 'Occupational health and safety management system', 'section' => 'health_safety', 'field' => 'ohs_management_system'],
    'GRI 403-2' => ['title' => 'Hazard identification, risk assessment, and incident investigation', 'section' => 'health_safety', 'field' => 'ohs_management_system'],
    'GRI 403-3' => ['title' => 'Occupational health services', 'section' => 'health_safety', 'field' => 'ohs_management_system'],
    'GRI 403-4' => ['title' => 'Worker participation in OHS', 'section' => 'health_safety', 'field' => 'ohs_management_system'],
    'GRI 403-5' => ['title' => 'Worker training on OHS', 'section' => 'health_safety', 'field' => 'ohs_management_system'],
    'GRI 403-6' => ['title' => 'Promotion of worker health', 'section' => 'health_safety', 'field' => 'ohs_management_system'],
    'GRI 403-7' => ['title' => 'Prevention of OHS impacts in business relationships', 'section' => 'health_safety', 'field' => 'ohs_management_system'],
    'GRI 403-8' => ['title' => 'Workers covered by an OHS management system', 'section' => 'health_safety', 'field' => 'hours_worked'],

    // GRI 404 — Training (extended)
    'GRI 404-2' => ['title' => 'Programs for upgrading employee skills', 'section' => 'social_hr', 'field' => 'training_hours_avg'],
    'GRI 404-3' => ['title' => 'Percentage of employees receiving regular performance reviews', 'section' => 'social_hr', 'field' => 'employees_total'],

    // GRI 405 — Diversity (extended)
    'GRI 405-2' => ['title' => 'Ratio of basic salary and remuneration of women to men', 'section' => 'diversity', 'field' => 'women_workforce_percent'],

    // GRI 406 — Non-discrimination
    'GRI 406-1' => ['title' => 'Incidents of discrimination and corrective actions', 'section' => 'governance_metrics', 'field' => 'compliance_notes'],

    // GRI 408 / 409 — Child / forced labour
    'GRI 408-1' => ['title' => 'Operations and suppliers at significant risk for child labour', 'section' => 'supply_chain', 'field' => 'human_rights_due_diligence'],
    'GRI 409-1' => ['title' => 'Operations and suppliers at significant risk for forced labour', 'section' => 'supply_chain', 'field' => 'human_rights_due_diligence'],

    // GRI 413 — Local communities
    'GRI 413-1' => ['title' => 'Operations with local community engagement', 'source' => 'esg_report', 'section' => 'community_impact', 'field' => 'beneficiaries_count'],
    'GRI 413-2' => ['title' => 'Operations with significant actual and potential negative impacts on local communities', 'source' => 'esg_report', 'section' => 'community_impact', 'field' => 'investment_methodology'],

    // GRI 308 / 414 — Supply chain (extended)
    'GRI 308-2' => ['title' => 'Negative environmental impacts in the supply chain', 'section' => 'supply_chain', 'field' => 'supplier_audit_summary'],
    'GRI 414-2' => ['title' => 'Negative social impacts in the supply chain', 'section' => 'supply_chain', 'field' => 'supplier_audit_summary'],

    // GRI 415 / 416 / 417 / 418
    'GRI 415-1' => ['title' => 'Political contributions', 'section' => 'governance_metrics', 'field' => 'ethics_incidents'],
    'GRI 416-1' => ['title' => 'Assessment of the health and safety impacts of product and service categories', 'section' => 'health_safety', 'field' => 'ohs_management_system'],
    'GRI 417-1' => ['title' => 'Requirements for product and service information and labeling', 'section' => 'governance_metrics', 'field' => 'compliance_notes'],
    'GRI 418-1' => ['title' => 'Substantiated complaints concerning breaches of customer privacy', 'section' => 'governance_metrics', 'field' => 'data_breaches'],

    // Stakeholder register (Phase C)
    'GRI 2-29a' => ['title' => 'Stakeholder engagement register', 'source' => 'stakeholder_register'],
];
