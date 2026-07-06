@extends('layouts.app')

@section('title', 'SASB Index')
@section('page-title', 'SASB Disclosure Index')

@section('content')
<div class="w-full">
    @include('layouts.partials.nav-disclosures-esg-depth', ['fiscalYear' => $fiscalYear])

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">{{ session('success') }}</div>
    @endif

    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title">SASB sector</h3>
            <p class="card-subtitle">Select the SASB industry standard for {{ $fiscalYear }}. Metrics auto-link from GHG inventory and GRI disclosures where mapped.</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('disclosures.sasb.sector', ['fiscal_year' => $fiscalYear]) }}" class="flex flex-col sm:flex-row gap-4 items-end">
                @csrf
                <div class="flex-1 w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Industry sector</label>
                    <select name="sasb_sector" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">— Not applicable / not selected —</option>
                        @foreach($sectors as $code => $sector)
                            <option value="{{ $code }}" @selected($selectedSector === $code)>{{ $code }} — {{ $sector['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Save sector</button>
            </form>
        </div>
    </div>

    @if($index['sector'])
        <div class="card mb-6">
            <div class="card-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="card-title">{{ $index['sector_label'] }}</h3>
                    <p class="card-subtitle">{{ $index['sector'] }} · {{ $index['industry'] }}</p>
                </div>
                <x-plan-gated-link
                    :allowed="$gate->canDisclosureExportType('sasb_index', $fiscalYear)"
                    :href="route('disclosures.sasb.export', ['fiscal_year' => $fiscalYear])"
                    :message="$gate->disclosureExportMessage()"
                    class="btn btn-secondary"
                    locked-class="btn btn-secondary">
                    Export CSV
                </x-plan-gated-link>
            </div>
            <div class="card-body overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-2">SASB code</th>
                            <th>Metric</th>
                            <th>Unit</th>
                            <th>Value</th>
                            <th>Status</th>
                            <th>Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($index['metrics'] as $row)
                            <tr class="border-b border-gray-50">
                                <td class="py-2 font-mono text-xs">{{ $row['code'] }}</td>
                                <td>{{ $row['label'] }}</td>
                                <td>{{ $row['unit'] }}</td>
                                <td>{{ $row['value'] ?? '—' }}</td>
                                <td>{{ $row['status'] }}</td>
                                <td class="text-xs uppercase text-gray-500">{{ $row['source'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="text-xs text-gray-500 mt-4">Manual SASB metrics (e.g. air emissions) can be entered via ESG Scorecard import using metric keys defined in config/sasb.php.</p>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body text-sm text-gray-500">Select a SASB sector above to generate the disclosure index for this reporting year.</div>
        </div>
    @endif
</div>
@endsection
