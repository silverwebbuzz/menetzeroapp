<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>UAE ESG Report — {{ $report['company']->name }}</title>
    <style>
        @page { margin: 28mm 18mm 22mm 18mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; line-height: 1.45; }
        .brand-bar { height: 4px; background: #0d9488; margin-bottom: 14px; }
        h1 { font-size: 20px; color: #115e59; margin: 0 0 4px 0; }
        h2 { font-size: 13px; color: #115e59; border-bottom: 1px solid #ccfbf1; padding-bottom: 4px; margin: 20px 0 8px 0; }
        h3 { font-size: 11px; color: #134e4a; margin: 12px 0 6px 0; }
        .field-label { font-size: 9px; text-transform: uppercase; color: #9ca3af; font-weight: bold; margin-top: 6px; }
        .field-value { white-space: pre-wrap; margin-bottom: 4px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.data th, table.data td { border: 1px solid #e5e7eb; padding: 5px 7px; font-size: 9px; vertical-align: top; }
        table.data th { background: #f0fdfa; text-align: left; }
        .muted { color: #6b7280; font-size: 9px; }
        .page-break { page-break-before: always; }
        .cover { text-align: center; padding-top: 40mm; }
        .disclaimer { font-size: 8px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px; margin-top: 20px; }
        @if(!empty($enterpriseCover))
        .cover-enterprise { text-align: center; padding: 30mm 15mm 20mm; min-height: 240mm; position: relative; }
        .cover-enterprise .accent-block { height: 8mm; width: 100%; margin-bottom: 18mm; }
        .cover-enterprise .logo-wrap { margin-bottom: 14mm; }
        .cover-enterprise .logo-wrap img { max-height: 72px; max-width: 220px; }
        .cover-enterprise .report-title { font-size: 28px; font-weight: bold; margin: 0 0 8px; line-height: 1.2; }
        .cover-enterprise .company-name { font-size: 18px; color: #1f2937; margin: 0 0 6px; }
        .cover-enterprise .meta { font-size: 11px; color: #6b7280; margin: 4px 0; }
        .cover-enterprise .tagline { font-size: 10px; color: #4b5563; margin: 14mm auto 0; max-width: 85%; line-height: 1.5; }
        .cover-enterprise .footer-line { position: absolute; bottom: 18mm; left: 0; right: 0; font-size: 8px; color: #9ca3af; }
        @endif
    </style>
</head>
<body>
    {{-- Cover --}}
    @if(!empty($enterpriseCover))
        <div class="cover-enterprise">
            <div class="accent-block" style="background: {{ $enterpriseCover['accent_color'] }};"></div>
            <div class="logo-wrap">
                @if(!empty($enterpriseCover['logo']))
                    <img src="{{ $enterpriseCover['logo'] }}" alt="">
                @endif
            </div>
            <div class="report-title" style="color: {{ $enterpriseCover['accent_dark'] }};">{{ $enterpriseCover['title'] }}</div>
            <p class="company-name">{{ $enterpriseCover['company_name'] }}</p>
            <p class="meta">Fiscal Year {{ $enterpriseCover['fiscal_year'] }} · {{ $enterpriseCover['generated_at'] }}</p>
            @if(!empty($enterpriseCover['frameworks']))
                <p class="meta">{{ $enterpriseCover['frameworks'] }}</p>
            @endif
            @if(!empty($enterpriseCover['tagline']))
                <p class="tagline">{{ $enterpriseCover['tagline'] }}</p>
            @endif
            @if(!empty($enterpriseCover['approval']))
                <p class="meta" style="margin-top:10mm;">{{ $enterpriseCover['approval'] }}</p>
            @endif
            @if(!empty($enterpriseCover['confidentiality']))
                <p class="footer-line">{{ $enterpriseCover['confidentiality'] }}</p>
            @endif
        </div>
    @else
    <div class="cover">
        <div class="brand-bar" style="width:60%;margin:0 auto 20px;"></div>
        @if(!empty($companyLogo))
            <img src="{{ $companyLogo }}" alt="" style="max-height:48px;margin-bottom:16px;">
        @endif
        <h1 style="font-size:24px;">UAE ESG Report</h1>
        <p style="font-size:14px;color:#115e59;margin:8px 0;">{{ $report['company']->name }}</p>
        <p class="muted">Fiscal Year {{ $report['fiscal_year'] }} · {{ $report['generated_at'] }}</p>
        @php $about = $report['narrative']['about_report']['content'] ?? []; @endphp
        @if(!empty($about['frameworks_used']))
            <p class="muted" style="margin-top:12px;">{{ $about['frameworks_used'] }}</p>
        @elseif(!empty($report['frameworks_disclosed']))
            <p class="muted" style="margin-top:12px;">{{ implode(' · ', $report['frameworks_disclosed']) }}</p>
        @endif
    </div>
    @endif

    <div class="page-break"></div>
    <div class="brand-bar"></div>

    {{-- Narrative chapters --}}
    @foreach($report['narrative'] as $key => $section)
        @php
            $content = $section['content'] ?? [];
            $fields = $report['section_config'][$key]['fields'] ?? [];
            $hasContent = !empty(array_filter($content));
        @endphp
        @if($hasContent)
            <h2>{{ $section['title'] }}</h2>
            @foreach($fields as $fieldKey => $field)
                @if(isset($content[$fieldKey]) && $content[$fieldKey] !== '')
                    <div class="field-label">{{ $field['label'] }}</div>
                    <div class="field-value">{{ $content[$fieldKey] }}</div>
                @endif
            @endforeach
        @endif
    @endforeach

    @php $settings = $report['reporting_settings']; @endphp
    @if($settings)
        <h2>Reporting Methodology</h2>
        <table class="data">
            @if($settings->organisational_boundary)<tr><td>Organisational boundary</td><td>{{ \App\Models\CompanyReportingSetting::BOUNDARIES[$settings->organisational_boundary] ?? $settings->organisational_boundary }}</td></tr>@endif
            @if($settings->gwp_version)<tr><td>GWP version</td><td>{{ $settings->gwp_version }}</td></tr>@endif
            @if($settings->base_year)<tr><td>Base year</td><td>{{ $settings->base_year }}</td></tr>@endif
        </table>
    @endif

    <div class="page-break"></div>
    <h2>GHG Inventory</h2>
    @if($report['ghg']['has_data'])
        <table class="data">
            <tr><th>Scope</th><th>Emissions (tCO₂e)</th></tr>
            <tr><td>Scope 1</td><td>{{ number_format($report['ghg']['scope_tonnes']['Scope 1'], 4) }}</td></tr>
            <tr><td>Scope 2 — location-based</td><td>{{ number_format($report['ghg']['scope2_location_tonnes'], 4) }}</td></tr>
            <tr><td>Scope 2 — market-based</td><td>{{ number_format($report['ghg']['scope2_market_tonnes'], 4) }}</td></tr>
            <tr><td>Scope 3</td><td>{{ number_format($report['ghg']['scope_tonnes']['Scope 3'], 2) }}</td></tr>
            <tr><td><strong>Total</strong></td><td><strong>{{ number_format($report['ghg']['total_tonnes'], 2) }}</strong></td></tr>
        </table>
        @if($report['ghg']['scope_3_categories']->isNotEmpty())
            <h3>Scope 3 by category</h3>
            <table class="data">
                <tr><th>Category</th><th>tCO₂e</th></tr>
                @foreach($report['ghg']['scope_3_categories'] as $cat)
                    <tr><td>{{ $cat['category'] }}</td><td>{{ number_format($cat['tonnes'], 2) }}</td></tr>
                @endforeach
            </table>
        @endif
        @if(!empty($report['ghg_methodology']))
            <p class="muted" style="margin-top:8px;">{{ $report['ghg_methodology'] }}</p>
        @endif
    @else
        <p class="muted">No GHG data entered for this fiscal year.</p>
    @endif

    <h2>Environmental Performance (GRI)</h2>
    @php $g305 = $report['gri']['gri_305']; @endphp
    @if($g305['has_data'])
        <table class="data">
            <tr><td>GRI 305-1 Scope 1</td><td>{{ number_format($g305['scope1_tonnes'], 4) }} tCO₂e</td></tr>
            <tr><td>GRI 305-2 Scope 2 (location)</td><td>{{ number_format($g305['scope2_location_tonnes'], 4) }} tCO₂e</td></tr>
            <tr><td>GRI 305-3 Scope 3</td><td>{{ number_format($g305['scope3_tonnes'], 2) }} tCO₂e</td></tr>
        </table>
    @endif
    @foreach(['energy' => 'GRI 302 Energy', 'water' => 'GRI 303 Water', 'waste' => 'GRI 306 Waste'] as $sec => $title)
        @php $c = $report['gri'][$sec] ?? []; @endphp
        @if(!empty(array_filter($c)))
            <h3>{{ $title }}</h3>
            @foreach(config('disclosure.gri.sections.'.$sec.'.fields', []) as $fk => $f)
                @if(isset($c[$fk]) && $c[$fk] !== '')
                    <div class="field-label">{{ $f['label'] }}</div>
                    <div class="field-value">{{ $c[$fk] }}</div>
                @endif
            @endforeach
        @endif
    @endforeach

    <div class="page-break"></div>
    <h2>Climate Risk &amp; IFRS S2</h2>
    @if($report['ifrs_s2']['climate_risks']->isNotEmpty())
        <h3>Climate-related risks</h3>
        <table class="data">
            <tr><th>Risk</th><th>Type</th><th>Time horizon</th></tr>
            @foreach($report['ifrs_s2']['climate_risks'] as $risk)
                <tr><td>{{ $risk->name }}</td><td>{{ $risk->risk_type }}</td><td>{{ $risk->time_horizon }}</td></tr>
            @endforeach
        </table>
    @endif
    @if($report['ifrs_s2']['reduction_targets']->isNotEmpty())
        <h3>Climate-related targets</h3>
        <table class="data">
            <tr><th>Target</th><th>Scope</th><th>Target year</th><th>Reduction %</th></tr>
            @foreach($report['ifrs_s2']['reduction_targets'] as $target)
                <tr>
                    <td>{{ $target->name }}</td>
                    <td>{{ $target->scope_coverage }}</td>
                    <td>{{ $target->target_year }}</td>
                    <td>{{ $target->reduction_percent }}%</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h2>Social &amp; Governance (GRI)</h2>
    @foreach(['social_hr' => 'Employment', 'diversity' => 'Diversity', 'health_safety' => 'Health & Safety', 'governance_metrics' => 'Governance & Ethics'] as $sec => $title)
        @php $c = $report['gri'][$sec] ?? []; @endphp
        @if(!empty(array_filter($c)))
            <h3>{{ $title }}</h3>
            @foreach(config('disclosure.gri.sections.'.$sec.'.fields', []) as $fk => $f)
                @if(isset($c[$fk]) && $c[$fk] !== '')
                    <div class="field-label">{{ $f['label'] }}</div>
                    <div class="field-value">{{ $c[$fk] }}</div>
                @endif
            @endforeach
        @endif
    @endforeach

    @if($report['ifrs_s1']['material_topics']->isNotEmpty())
        <h3>Material sustainability topics (IFRS S1 / GRI 3)</h3>
        <table class="data">
            <tr><th>Topic</th><th>GRI</th></tr>
            @foreach($report['ifrs_s1']['material_topics'] as $topic)
                <tr><td>{{ $topic['label'] }}</td><td>{{ $topic['gri'] ?? '—' }}</td></tr>
            @endforeach
        </table>
    @endif

    @if($report['esg_depth']['materiality_matrix']->isNotEmpty())
        <h3>Materiality matrix</h3>
        <table class="data">
            <tr><th>Topic</th><th>Impact</th><th>Financial</th><th>Material</th></tr>
            @foreach($report['esg_depth']['materiality_matrix'] as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td>{{ ucfirst($row['impact']) }}</td>
                    <td>{{ ucfirst($row['financial']) }}</td>
                    <td>{{ $row['is_material'] ? 'Yes' : 'No' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if($report['esg_depth']['stakeholders']->isNotEmpty())
        <h3>Stakeholder engagement (GRI 2-29)</h3>
        <table class="data">
            <tr><th>Group</th><th>Method</th><th>Frequency</th></tr>
            @foreach($report['esg_depth']['stakeholders'] as $s)
                <tr>
                    <td>{{ $s->stakeholder_group }}</td>
                    <td>{{ $s->engagement_method ?: '—' }}</td>
                    <td>{{ $s->frequency ? (\App\Models\StakeholderEngagement::FREQUENCIES[$s->frequency] ?? $s->frequency) : '—' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if($report['esg_depth']['suppliers']->isNotEmpty())
        <h3>Supply chain — key suppliers (Scope 3 Cat 1)</h3>
        <table class="data">
            <tr><th>Supplier</th><th>Spend AED</th><th>Screening</th></tr>
            @foreach($report['esg_depth']['suppliers'] as $sup)
                <tr>
                    <td>{{ $sup->supplier_name }}</td>
                    <td>{{ $sup->spend_aed !== null ? number_format($sup->spend_aed, 0) : '—' }}</td>
                    <td>{{ \App\Models\SupplyChainSupplier::SCREENING[$sup->screening_status] ?? $sup->screening_status }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @php $sc = $report['gri']['supply_chain'] ?? []; @endphp
    @if(!empty(array_filter($sc)))
        <h3>Supply chain due diligence (GRI 308 / 414)</h3>
        @foreach(config('disclosure.gri.sections.supply_chain.fields', []) as $fk => $f)
            @if(isset($sc[$fk]) && $sc[$fk] !== '')
                <div class="field-label">{{ $f['label'] }}</div>
                <div class="field-value">{{ $sc[$fk] }}</div>
            @endif
        @endforeach
    @endif

    @if($report['esg_depth']['esg_targets']->isNotEmpty())
        <h3>Non-climate ESG targets</h3>
        <table class="data">
            <tr><th>Target</th><th>Category</th><th>Target year</th><th>Target value</th></tr>
            @foreach($report['esg_depth']['esg_targets'] as $tgt)
                <tr>
                    <td>{{ $tgt->name }}</td>
                    <td>{{ \App\Models\EsgSustainabilityTarget::CATEGORIES[$tgt->target_category] ?? $tgt->target_category }}</td>
                    <td>{{ $tgt->target_year }}</td>
                    <td>{{ $tgt->target_value !== null ? number_format($tgt->target_value, 2).' '.$tgt->unit : '—' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <div class="page-break"></div>
    <h2>Sustainability Performance Scorecard</h2>
    <p class="muted">Three-year comparison ({{ implode(', ', $report['scorecard']['years']) }})</p>

    @foreach($report['scorecard']['categories'] as $category)
        <h3>{{ $category['title'] }}</h3>
        <table class="data">
            <tr>
                <th>Metric</th>
                <th>Unit</th>
                @foreach($report['scorecard']['years'] as $year)
                    <th>{{ $year }}</th>
                @endforeach
            </tr>
            @foreach($category['rows'] as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td>{{ $row['unit'] }}</td>
                    @foreach($report['scorecard']['years'] as $year)
                        @php $val = $row['values'][$year] ?? null; @endphp
                        <td>{{ $val !== null ? number_format($val, $row['decimals']) : '—' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    @endforeach

    <div class="page-break"></div>
    <h2>IFRS S2 Disclosure Index</h2>
    <table class="data">
        <tr><th>Paragraph</th><th>Topic</th><th>Status</th><th>Report location</th></tr>
        @foreach($report['ifrs_s2_index'] as $row)
            <tr>
                <td>{{ $row['paragraph'] }}</td>
                <td>{{ $row['topic'] }}</td>
                <td>{{ $row['status'] }}</td>
                <td>{{ $row['report_location'] }}</td>
            </tr>
        @endforeach
    </table>

    <h2>IFRS S1 Disclosure Index</h2>
    <table class="data">
        <tr><th>Paragraph</th><th>Topic</th><th>Status</th><th>Report location</th></tr>
        @foreach($report['ifrs_s1_index'] as $row)
            <tr>
                <td>{{ $row['paragraph'] }}</td>
                <td>{{ $row['topic'] }}</td>
                <td>{{ $row['status'] }}</td>
                <td>{{ $row['report_location'] }}</td>
            </tr>
        @endforeach
    </table>

    <h2>GRI Content Index</h2>
    <table class="data">
        <tr><th>Code</th><th>Disclosure</th><th>Status</th><th>Location</th><th>UNGC</th><th>WEF</th><th>SDG</th></tr>
        @foreach($report['gri_content_index'] as $row)
            <tr>
                <td>{{ $row['code'] }}</td>
                <td>{{ $row['title'] }}</td>
                <td>{{ $row['status'] }}</td>
                <td>{{ $row['location'] }}</td>
                <td>{{ $row['ungc'] ?? '—' }}</td>
                <td>{{ $row['wef'] ?? '—' }}</td>
                <td>{{ $row['sdg'] ?? '—' }}</td>
            </tr>
        @endforeach
    </table>

    @if(!empty($report['sasb_index']['sector']))
        <div class="page-break"></div>
        <h2>SASB Disclosure Index — {{ $report['sasb_index']['sector'] }}</h2>
        <p class="muted">{{ $report['sasb_index']['sector_label'] }} · {{ $report['sasb_index']['industry'] }}</p>
        <table class="data">
            <tr><th>Code</th><th>Metric</th><th>Unit</th><th>Value</th><th>Status</th></tr>
            @foreach($report['sasb_index']['metrics'] as $row)
                <tr>
                    <td>{{ $row['code'] }}</td>
                    <td>{{ $row['label'] }}</td>
                    <td>{{ $row['unit'] }}</td>
                    <td>{{ $row['value'] ?? '—' }}</td>
                    <td>{{ $row['status'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h2>UN Sustainable Development Goals — Mapping</h2>
    <table class="data">
        <tr><th>Topic area</th><th>SDG goals</th><th>Material for entity</th></tr>
        @foreach($report['sdg_map'] as $row)
            <tr>
                <td>{{ ucfirst(str_replace('_', ' ', $row['topic_key'])) }}</td>
                <td>{{ implode(', ', $row['sdg_goals']) }} — {{ $row['sdg_label'] }}</td>
                <td>{{ $row['material'] ? 'Yes' : 'No' }}</td>
            </tr>
        @endforeach
    </table>

    @php $assurance = $report['narrative']['about_report']['content']['assurance_status'] ?? null; @endphp
    @if($assurance && $assurance !== 'None')
        <h2>Independent Assurance</h2>
        <p class="field-value">Status: {{ $assurance }}</p>
        @if(!empty($report['narrative']['about_report']['content']['assurance_scope']))
            <div class="field-label">Assurance scope</div>
            <div class="field-value">{{ $report['narrative']['about_report']['content']['assurance_scope'] }}</div>
        @endif
        @if(!empty($report['assurance_document']['filename']))
            <div class="field-label">Verifier statement on file</div>
            <div class="field-value">{{ $report['assurance_document']['filename'] }}
                @if(!empty($report['assurance_document']['uploaded_at']))
                    (uploaded {{ \Carbon\Carbon::parse($report['assurance_document']['uploaded_at'])->format('d M Y') }})
                @endif
            </div>
            <p class="muted">Full assurance PDF is available for download from the UAE ESG Report workspace.</p>
        @endif
    @endif

    <div class="disclaimer">
        @if(!empty($enterpriseCover))
            This report is published by {{ $report['company']->name }}. Narrative content is the responsibility of the reporting entity.
            GHG figures are calculated from entered activity data. Official MOCCAE submission must be completed at mrv.ae using IEQT where applicable.
        @else
            {{ $report['disclaimer'] }}
        @endif
    </div>
</body>
</html>
