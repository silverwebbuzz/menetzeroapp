@php
    $fy = $fiscalYear ?? request('fiscal_year', session('disclosure_fiscal_year', now()->year));
    $q = ['fiscal_year' => $fy];
    $fw = $framework ?? 'ifrs_s2';
@endphp

@if($fw === 'ifrs_s1')
    @include('layouts.partials.nav-disclosures-s1', ['fiscalYear' => $fy])
@elseif($fw === 'gri')
    @include('layouts.partials.nav-disclosures-gri', ['fiscalYear' => $fy])
@elseif($fw === 'esg_report')
    @include('layouts.partials.nav-disclosures-uae-esg', ['fiscalYear' => $fy, 'sectionConfig' => config('esg_report.sections', [])])
@else
    @include('layouts.partials.nav-disclosures-s2', ['fiscalYear' => $fy])
@endif
