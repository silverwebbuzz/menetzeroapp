@extends('layouts.app')

@section('title', 'Measurement Details - MenetZero')
@section('page-title', 'Measurement Details')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $measurement->location->name }}</h1>
            <p class="mt-2 text-gray-600">{{ $measurement->period_start->format('M d, Y') }} - {{ $measurement->period_end->format('M d, Y') }}</p>
        </div>
        <div class="flex space-x-3">
            <span class="px-4 py-2 text-sm font-medium rounded-full 
                @if($measurement->status === 'draft') bg-gray-100 text-gray-800
                @elseif($measurement->status === 'submitted') bg-blue-100 text-blue-800
                @elseif($measurement->status === 'under_review') bg-yellow-100 text-yellow-800
                @elseif($measurement->status === 'not_verified') bg-red-100 text-red-800
                @elseif($measurement->status === 'verified') bg-green-100 text-green-800
                @endif">
                {{ $measurement->status_display }}
            </span>
            
            @if($measurement->canBeEdited())
                <a href="{{ route('measurements.edit', $measurement) }}" 
                   class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                    Edit
                </a>
            @endif
            
            @if($measurement->canBeSubmitted())
                <form method="POST" action="{{ route('measurements.submit', $measurement) }}" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                        Submit for Review
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Measurement Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Basic Info -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Measurement Details</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Frequency:</span>
                    <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $measurement->frequency)) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Fiscal Year:</span>
                    <span class="font-medium">{{ $measurement->fiscal_year }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Created:</span>
                    <span class="font-medium">{{ $measurement->created_at->format('M d, Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Created By:</span>
                    <span class="font-medium">{{ $measurement->creator->name }}</span>
                </div>
            </div>
        </div>

        <!-- CO2e Summary -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">CO2e Summary</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Total CO2e:</span>
                    <span class="font-medium text-orange-600">{{ number_format($measurement->total_co2e, 2) }} kg</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Scope 1:</span>
                    <span class="font-medium">{{ number_format($measurement->getCo2eByScope('Scope 1'), 2) }} kg</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Scope 2:</span>
                    <span class="font-medium">{{ number_format($measurement->getCo2eByScope('Scope 2'), 2) }} kg</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Scope 3:</span>
                    <span class="font-medium">{{ number_format($measurement->getCo2eByScope('Scope 3'), 2) }} kg</span>
                </div>
            </div>
        </div>

        <!-- Data Points -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Data Points</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Entries:</span>
                    <span class="font-medium">{{ $measurement->measurementData->count() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">With Documents:</span>
                    <span class="font-medium">{{ $measurement->measurementData->where('supporting_docs', '!=', null)->count() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Offset Items:</span>
                    <span class="font-medium">{{ $measurement->measurementData->where('is_offset', true)->count() }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Emission Data by Scope -->
    @if($measurement->measurementData->count() > 0)
        <div class="space-y-8">
            @foreach(['Scope 1', 'Scope 2', 'Scope 3'] as $scope)
                @php
                    $scopeData = $measurement->measurementData->where('scope', $scope);
                @endphp
                
                @if($scopeData->count() > 0)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $scope }} Emissions</h3>
                            <p class="text-sm text-gray-600">Total: {{ number_format($scopeData->sum('calculated_co2e'), 2) }} kg CO2e</p>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Emission Source</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CO2e</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($scopeData as $data)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $data->emissionSource->name }}</div>
                                                @if($data->notes)
                                                    <div class="text-sm text-gray-500">{{ Str::limit($data->notes, 50) }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ number_format($data->quantity, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $data->unit }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-orange-600">
                                                {{ number_format($data->calculated_co2e, 2) }} kg
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $data->calculation_method ?? 'Standard' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center space-x-2">
                                                    @if($data->is_offset)
                                                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                                            Offset
                                                        </span>
                                                    @endif
                                                    @if($data->hasSupportingDocs())
                                                        <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                                            {{ $data->supporting_docs_count }} docs
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <div class="text-center py-12">
            <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No emission data added yet</h3>
            <p class="text-gray-600 mb-6">Start adding emission data for this measurement period.</p>
            @if($measurement->canBeEdited())
                <a href="{{ route('measurements.edit', $measurement) }}" class="px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                    Add Emission Data
                </a>
            @endif
        </div>
    @endif

    <!-- Notes Section -->
    @if($measurement->notes)
        <div class="mt-8 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Notes</h3>
            <p class="text-gray-700">{{ $measurement->notes }}</p>
        </div>
    @endif

    <!-- Audit Trail -->
    @if($measurement->auditTrail->count() > 0)
        <div class="mt-8 bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Activity History</h3>
            </div>
            <div class="divide-y divide-gray-200">
                @foreach($measurement->auditTrail->sortByDesc('changed_at') as $audit)
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $audit->change_description }}</p>
                                    <p class="text-sm text-gray-500">by {{ $audit->changedBy->name }} on {{ $audit->changed_at->format('M d, Y H:i') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
