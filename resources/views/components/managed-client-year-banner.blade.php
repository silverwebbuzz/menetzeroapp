@props(['fiscalYear' => null, 'exportCode' => null])

@php
    $consultantBannerMessage = $gate->managedPreviewBannerMessage($fiscalYear);
    $canExport = $exportCode
        ? $gate->canDisclosureExportType($exportCode, $fiscalYear)
        : ($fiscalYear ? $gate->canDisclosureExport($fiscalYear) : $gate->canDisclosureExport());
@endphp

@if($consultantBannerMessage)
    <x-preview-only-banner :message="$consultantBannerMessage" :show-upgrade="false" />
@elseif(!$canExport)
    <x-preview-only-banner
        :message="$gate->disclosureExportMessage($fiscalYear)"
        upgrade-label="Upgrade to Growth" />
@endif
