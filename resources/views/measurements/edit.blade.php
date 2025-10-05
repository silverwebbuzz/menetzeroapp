@extends('layouts.app')

@section('title', 'Edit Measurement - MenetZero')
@section('page-title', 'Edit Measurement')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Edit Measurement</h1>
        <p class="mt-2 text-gray-600">{{ $measurement->location->name }} - {{ $measurement->period_start->format('M d, Y') }} to {{ $measurement->period_end->format('M d, Y') }}</p>
    </div>

    <form method="POST" action="{{ route('measurements.update', $measurement) }}" class="space-y-6">
        @csrf
        @method('PUT')
        
        <!-- Notes Section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Measurement Notes</h3>
            
            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                    Notes
                </label>
                <textarea name="notes" id="notes" rows="4"
                          placeholder="Add any additional notes about this measurement..."
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('notes') border-red-500 @enderror">{{ old('notes', $measurement->notes) }}</textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Emission Data Section -->
        @if($measurement->measurementData->count() > 0)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Emission Data</h3>
                    <p class="text-sm text-gray-600">{{ $measurement->measurementData->count() }} data points • Total: {{ number_format($measurement->total_co2e, 2) }} kg CO2e</p>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-orange-600">{{ number_format($measurement->getCo2eByScope('Scope 1'), 2) }}</div>
                            <div class="text-sm text-gray-600">Scope 1 (kg CO2e)</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600">{{ number_format($measurement->getCo2eByScope('Scope 2'), 2) }}</div>
                            <div class="text-sm text-gray-600">Scope 2 (kg CO2e)</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">{{ number_format($measurement->getCo2eByScope('Scope 3'), 2) }}</div>
                            <div class="text-sm text-gray-600">Scope 3 (kg CO2e)</div>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <a href="{{ route('measurements.show', $measurement) }}" 
                           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            View All Data
                        </a>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="text-center">
                    <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No emission data added yet</h3>
                    <p class="text-gray-600 mb-4">Add emission data to start calculating your carbon footprint.</p>
                    <a href="{{ route('measurements.show', $measurement) }}" 
                       class="px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                        Add Emission Data
                    </a>
                </div>
            </div>
        @endif

        <!-- Status Information -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Measurement Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <div class="flex items-center">
                        <span class="px-3 py-1 text-sm font-medium rounded-full 
                            @if($measurement->status === 'draft') bg-gray-100 text-gray-800
                            @elseif($measurement->status === 'submitted') bg-blue-100 text-blue-800
                            @elseif($measurement->status === 'under_review') bg-yellow-100 text-yellow-800
                            @elseif($measurement->status === 'not_verified') bg-red-100 text-red-800
                            @elseif($measurement->status === 'verified') bg-green-100 text-green-800
                            @endif">
                            {{ $measurement->status_display }}
                        </span>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Created</label>
                    <div class="text-sm text-gray-900">{{ $measurement->created_at->format('M d, Y H:i') }}</div>
                    <div class="text-sm text-gray-600">by {{ $measurement->creator->name }}</div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-between items-center pt-6">
            <div class="flex space-x-3">
                <a href="{{ route('measurements.show', $measurement) }}" 
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </a>
                <a href="{{ route('measurements.show', $measurement) }}" 
                   class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    View Details
                </a>
            </div>
            
            <button type="submit" 
                    class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                Update Measurement
            </button>
        </div>
    </form>
</div>
@endsection
