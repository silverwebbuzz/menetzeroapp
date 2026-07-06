@extends('layouts.app')

@section('title', 'Stakeholder Register')
@section('page-title', 'Stakeholder Engagement')

@section('content')
<div class="w-full">
    @include('layouts.partials.nav-disclosures-esg-depth', ['fiscalYear' => $fiscalYear])

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">{{ session('success') }}</div>
    @endif

    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title">Add stakeholder engagement</h3>
            <p class="card-subtitle">GRI 2-29 — {{ $fiscalYear }}</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('disclosures.stakeholders.store', ['fiscal_year' => $fiscalYear]) }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stakeholder group *</label>
                    <input type="text" name="stakeholder_group" required placeholder="e.g. Employees, Investors, Regulators" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Engagement method</label>
                    <input type="text" name="engagement_method" placeholder="e.g. Survey, workshops, meetings" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frequency</label>
                    <select name="frequency" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">—</option>
                        @foreach(\App\Models\StakeholderEngagement::FREQUENCIES as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last engaged</label>
                    <input type="date" name="last_engaged_at" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Topics discussed</label>
                    <textarea name="topics_discussed" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Outcomes / actions</label>
                    <textarea name="outcomes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="btn btn-primary">Add engagement</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Register ({{ $engagements->count() }})</h3></div>
        <div class="card-body overflow-x-auto">
            @if($engagements->isEmpty())
                <p class="text-sm text-gray-500">No stakeholder engagements recorded for {{ $fiscalYear }}.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-2">Group</th>
                            <th>Method</th>
                            <th>Frequency</th>
                            <th>Last engaged</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($engagements as $eng)
                            <tr class="border-b border-gray-50 align-top">
                                <td class="py-3 pr-4">
                                    <div class="font-medium">{{ $eng->stakeholder_group }}</div>
                                    @if($eng->topics_discussed)<div class="text-xs text-gray-500 mt-1">{{ Str::limit($eng->topics_discussed, 80) }}</div>@endif
                                </td>
                                <td class="py-3">{{ $eng->engagement_method ?: '—' }}</td>
                                <td class="py-3">{{ $eng->frequency ? (\App\Models\StakeholderEngagement::FREQUENCIES[$eng->frequency] ?? $eng->frequency) : '—' }}</td>
                                <td class="py-3">{{ $eng->last_engaged_at?->format('d M Y') ?? '—' }}</td>
                                <td class="py-3 text-right">
                                    <form method="POST" action="{{ route('disclosures.stakeholders.destroy', ['stakeholderEngagement' => $eng, 'fiscal_year' => $fiscalYear]) }}" onsubmit="return confirm('Remove this entry?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 text-xs">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
