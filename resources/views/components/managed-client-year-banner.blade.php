@props(['fiscalYear' => null, 'exportCode' => null])

@php
    $gate = \App\Support\PlanGate::forUser(auth('web')->user());
    $consultantBannerMessage = $gate->managedPreviewBannerMessage($fiscalYear);
    $canExport = $exportCode
        ? $gate->canDisclosureExportType($exportCode, $fiscalYear)
        : ($fiscalYear ? $gate->canDisclosureExport($fiscalYear) : $gate->canDisclosureExport());
@endphp

@if($consultantBannerMessage)
    <x-preview-only-banner :message="$consultantBannerMessage" :show-upgrade="false" />
@elseif(!$canExport)
    <x-preview-only-banner
        :message="$gate->lockedFeatureMessage($gate->disclosureExportMessage($fiscalYear), 'Report downloads')"
        :upgrade-label="$gate->upgradeButtonLabel('Upgrade to Growth')"
        :upgrade-url="$gate->upgradeRoute()" />
@endif
