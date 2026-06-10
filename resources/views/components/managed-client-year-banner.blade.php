@props(['fiscalYear' => null, 'exportCode' => null])

@php
    $partnerMessage = $gate->managedPreviewBannerMessage($fiscalYear);
    $canExport = $exportCode
        ? $gate->canDisclosureExportType($exportCode, $fiscalYear)
        : ($fiscalYear ? $gate->canDisclosureExport($fiscalYear) : $gate->canDisclosureExport());
@endphp

@if($partnerMessage)
    <x-preview-only-banner :message="$partnerMessage" :show-upgrade="false" />
@elseif(!$canExport)
    <x-preview-only-banner
        :message="$gate->disclosureExportMessage($fiscalYear)"
        upgrade-label="Upgrade to Growth" />
@endif
