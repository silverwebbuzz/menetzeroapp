@php
    $portal = $portal ?? 'company';
@endphp

@if($portal === 'consultant')
    <p class="header-context truncate" aria-label="Consultant practice">
        <strong>{{ $consultant->company_name ?: $consultant->name }}</strong>
    </p>
@else
    <p class="header-context truncate {{ ($isConsultantActing ?? false) ? 'header-context--agency' : '' }}" aria-label="Current workspace">
        @if($isConsultantActing ?? false)
            @if($consultantReadOnly ?? false)
                <span class="header-context__prefix">Read-only —</span>
            @else
                <span class="header-context__prefix">Agency mode —</span>
            @endif
            <strong>{{ $activeCompany?->name }}</strong>
            @if($consultantActingEngagement ?? null)
                <span class="header-context__meta">· PRY {{ $consultantActingEngagement->primary_reporting_year }}</span>
            @endif
        @elseif($activeCompany ?? null)
            <strong>{{ $activeCompany->name }}</strong>
        @else
            {{ config('app.name', 'MenetZero') }}
        @endif
    </p>
@endif
