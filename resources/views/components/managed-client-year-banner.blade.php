@props(['fiscalYear' => null, 'exportCode' => null])

@php
    $gate = \App\Support\PlanGate::forUser(auth('web')->user());
    $yearBannerMessage = $gate->managedPreviewBannerMessage($fiscalYear);
    $canWriteYear = $fiscalYear ? $gate->canWriteReportingYear($fiscalYear) : true;
    $canExport = $exportCode
        ? $gate->canDisclosureExportType($exportCode, $fiscalYear)
        : ($fiscalYear ? $gate->canDisclosureExport($fiscalYear) : $gate->canDisclosureExport());
@endphp

@if($yearBannerMessage)
    <x-preview-only-banner :message="$yearBannerMessage" :show-upgrade="false" />
@elseif($fiscalYear && !$canWriteYear)
    <x-preview-only-banner
        :message="$gate->writeReportingYearMessage($fiscalYear)"
        :show-upgrade="false" />
@elseif(!$canExport)
    <x-preview-only-banner
        :message="$gate->lockedFeatureMessage($gate->disclosureExportMessage($fiscalYear), 'Report downloads')"
        :upgrade-label="$gate->upgradeButtonLabel('Upgrade to Growth')"
        :upgrade-url="$gate->upgradeRoute()" />
@endif
