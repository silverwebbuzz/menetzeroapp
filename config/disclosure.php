<?php

/**
 * IFRS S2 disclosure section schemas (Phase 1).
 * Narrative sections stored in company_disclosures.content as JSON keyed by field name.
 */
return [
    'ifrs_s2' => [
        'sections' => [
            'governance' => [
                'title' => 'Climate Governance',
                'reference' => 'IFRS S2 §5–7',
                'description' => 'How governance bodies oversee climate-related risks, opportunities, and targets.',
                'fields' => [
                    'board_oversight_body' => ['label' => 'Board / committee responsible for climate oversight', 'type' => 'text', 'required' => true],
                    'board_climate_integration' => ['label' => 'How climate is integrated into strategy and decision-making', 'type' => 'textarea', 'required' => true],
                    'management_accountable_role' => ['label' => 'Management role accountable for climate', 'type' => 'text', 'required' => true],
                    'board_climate_expertise' => ['label' => 'Climate expertise on the board (Y/N and detail)', 'type' => 'textarea', 'required' => false],
                    'target_oversight' => ['label' => 'How the board oversees climate targets and progress', 'type' => 'textarea', 'required' => true],
                    'remuneration_linked' => ['label' => 'Are climate metrics linked to remuneration?', 'type' => 'textarea', 'required' => false],
                    'oversight_frequency' => ['label' => 'Frequency of board climate discussions', 'type' => 'select', 'options' => ['Quarterly', 'Bi-annually', 'Annually', 'Ad hoc'], 'required' => true],
                ],
            ],
            'strategy' => [
                'title' => 'Climate Strategy',
                'reference' => 'IFRS S2 §8–13',
                'description' => 'Effects of climate risks and opportunities on strategy, transition plan, and resilience.',
                'fields' => [
                    'risks_short_term' => ['label' => 'Climate risks — short term (0–1 years)', 'type' => 'textarea', 'required' => true],
                    'risks_medium_term' => ['label' => 'Climate risks — medium term (1–5 years)', 'type' => 'textarea', 'required' => true],
                    'risks_long_term' => ['label' => 'Climate risks — long term (5+ years)', 'type' => 'textarea', 'required' => true],
                    'opportunities_summary' => ['label' => 'Climate-related opportunities identified', 'type' => 'textarea', 'required' => false],
                    'business_model_impact' => ['label' => 'Effects on business model and value chain', 'type' => 'textarea', 'required' => true],
                    'financial_impact' => ['label' => 'Effects on financial position and cash flows', 'type' => 'textarea', 'required' => true],
                    'transition_plan_summary' => ['label' => 'Transition plan summary', 'type' => 'textarea', 'required' => true],
                    'transition_resources' => ['label' => 'Key actions and resources for transition', 'type' => 'textarea', 'required' => false],
                    'scenario_analysis_done' => ['label' => 'Scenario analysis undertaken?', 'type' => 'select', 'options' => ['Yes', 'No', 'Planned'], 'required' => true],
                    'scenarios_used' => ['label' => 'Scenarios used (e.g. 1.5°C, 2°C, 4°C)', 'type' => 'text', 'required' => false],
                    'resilience_assessment' => ['label' => 'Resilience assessment summary', 'type' => 'textarea', 'required' => false],
                ],
            ],
            'risk_management' => [
                'title' => 'Climate Risk Management',
                'reference' => 'IFRS S2 §14–17',
                'description' => 'Processes to identify, assess, and monitor climate-related risks.',
                'fields' => [
                    'identify_process' => ['label' => 'Process to identify climate risks', 'type' => 'textarea', 'required' => true],
                    'assess_process' => ['label' => 'Process to assess climate risks', 'type' => 'textarea', 'required' => true],
                    'prioritise_process' => ['label' => 'Process to prioritise climate risks', 'type' => 'textarea', 'required' => true],
                    'monitor_process' => ['label' => 'Process to monitor climate risks', 'type' => 'textarea', 'required' => true],
                    'erm_integration' => ['label' => 'Integration with enterprise risk management', 'type' => 'textarea', 'required' => true],
                ],
            ],
        ],
        'completeness_weights' => [
            'governance' => 20,
            'strategy' => 25,
            'risk_management' => 15,
            'climate_risks' => 15,
            'climate_opportunities' => 10,
            'reduction_targets' => 15,
        ],
    ],
];
