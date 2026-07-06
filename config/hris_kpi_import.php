<?php

/**
 * Phase E.2d — HRIS / payroll bulk KPI feed (Enterprise).
 *
 * CSV columns: fiscal_year, metric_key, value, unit, source_system, notes
 * Category is resolved from esg_scorecard + esg_scorecard_enterprise metric keys.
 */
return [
    'max_rows' => 500,
    'max_file_kb' => 4096,

    'template_examples' => [
        [2024, 'employees_uae', 1200, 'employees', 'Workday', 'Headcount UAE'],
        [2024, 'employees_gcc', 340, 'employees', 'Workday', 'Headcount GCC ex-UAE'],
        [2024, 'turnover_percent', 8.5, '%', 'SAP SuccessFactors', 'Annual turnover'],
        [2024, 'training_hours_avg', 32, 'hours', 'Oracle HCM', 'Avg training hours'],
        [2024, 'women_workforce_percent', 24, '%', 'Workday', 'Women in workforce'],
    ],
];
