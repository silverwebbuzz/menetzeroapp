@extends('layouts.app')

@section('page-title', 'Assign to Scope - DEWA Bill')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg border">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-xl font-semibold text-gray-900">Assign DEWA Bill Data to Scope Categories</h1>
            <p class="mt-1 text-sm text-gray-600">Review extracted data and assign each item to Scope 1, 2, or 3 for carbon calculations</p>
        </div>
        
        <form method="POST" action="{{ route('document-uploads.assign-scope', $document) }}" class="p-6 space-y-6">
            @csrf
            @method('PUT')
            
            <!-- Document Info -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="text-sm font-medium text-gray-900 mb-2">Document Information</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">File:</span>
                        <span class="font-medium">{{ $document->original_name }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Type:</span>
                        <span class="font-medium">{{ ucfirst($document->source_type) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Confidence:</span>
                        <span class="font-medium">{{ $document->ocr_confidence ?? 'N/A' }}%</span>
                    </div>
                </div>
            </div>
            
            <!-- Extracted Services -->
            @if(!empty($extractedData['extracted_services']))
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-blue-900 mb-3">⚡ Extracted Services</h3>
                    <p class="text-xs text-blue-700 mb-4">Services found in your DEWA bill</p>
                    
                    <div class="space-y-4">
                        @foreach($extractedData['extracted_services'] as $index => $service)
                            <div class="bg-white border border-blue-200 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ $service['type'] }} - {{ $service['description'] }}</h4>
                                        <p class="text-sm text-gray-600">{{ $service['value'] }} {{ $service['unit'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $service['raw_text'] }}</p>
                                    </div>
                                    <div class="w-48">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Assign to Scope</label>
                                        <select name="service_assignments[{{ $index }}][scope]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="">Select Scope</option>
                                            <option value="scope1" {{ old('service_assignments.' . $index . '.scope') == 'scope1' ? 'selected' : '' }}>Scope 1 - Direct Emissions</option>
                                            <option value="scope2" {{ old('service_assignments.' . $index . '.scope') == 'scope2' ? 'selected' : '' }}>Scope 2 - Indirect Energy</option>
                                            <option value="scope3" {{ old('service_assignments.' . $index . '.scope') == 'scope3' ? 'selected' : '' }}>Scope 3 - Other Indirect</option>
                                        </select>
                                        <input type="hidden" name="service_assignments[{{ $index }}][type]" value="{{ $service['type'] }}">
                                        <input type="hidden" name="service_assignments[{{ $index }}][value]" value="{{ $service['value'] }}">
                                        <input type="hidden" name="service_assignments[{{ $index }}][unit]" value="{{ $service['unit'] }}">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            
            <!-- Extracted Charges -->
            @if(!empty($extractedData['extracted_charges']))
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-green-900 mb-3">💰 Extracted Charges</h3>
                    <p class="text-xs text-green-700 mb-4">Charges found in your DEWA bill</p>
                    
                    <div class="space-y-4">
                        @foreach($extractedData['extracted_charges'] as $index => $charge)
                            <div class="bg-white border border-green-200 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ $charge['description'] }}</h4>
                                        <p class="text-sm text-gray-600">{{ $charge['amount'] }} {{ $charge['currency'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $charge['raw_text'] }}</p>
                                    </div>
                                    <div class="w-48">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Assign to Scope</label>
                                        <select name="charge_assignments[{{ $index }}][scope]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                            <option value="">Select Scope</option>
                                            <option value="scope1" {{ old('charge_assignments.' . $index . '.scope') == 'scope1' ? 'selected' : '' }}>Scope 1 - Direct Emissions</option>
                                            <option value="scope2" {{ old('charge_assignments.' . $index . '.scope') == 'scope2' ? 'selected' : '' }}>Scope 2 - Indirect Energy</option>
                                            <option value="scope3" {{ old('charge_assignments.' . $index . '.scope') == 'scope3' ? 'selected' : '' }}>Scope 3 - Other Indirect</option>
                                        </select>
                                        <input type="hidden" name="charge_assignments[{{ $index }}][description]" value="{{ $charge['description'] }}">
                                        <input type="hidden" name="charge_assignments[{{ $index }}][amount]" value="{{ $charge['amount'] }}">
                                        <input type="hidden" name="charge_assignments[{{ $index }}][currency]" value="{{ $charge['currency'] }}">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            
            <!-- Extracted Consumption -->
            @if(!empty($extractedData['extracted_consumption']))
                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-emerald-900 mb-3">🌱 Extracted Consumption</h3>
                    <p class="text-xs text-emerald-700 mb-4">Consumption data found in your DEWA bill</p>
                    
                    <div class="space-y-4">
                        @foreach($extractedData['extracted_consumption'] as $index => $consumption)
                            <div class="bg-white border border-emerald-200 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <h4 class="font-medium text-gray-900">Consumption Data</h4>
                                        <p class="text-sm text-gray-600">{{ $consumption['value'] }} {{ $consumption['unit'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $consumption['raw_text'] }}</p>
                                    </div>
                                    <div class="w-48">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Assign to Scope</label>
                                        <select name="consumption_assignments[{{ $index }}][scope]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                            <option value="">Select Scope</option>
                                            <option value="scope1" {{ old('consumption_assignments.' . $index . '.scope') == 'scope1' ? 'selected' : '' }}>Scope 1 - Direct Emissions</option>
                                            <option value="scope2" {{ old('consumption_assignments.' . $index . '.scope') == 'scope2' ? 'selected' : '' }}>Scope 2 - Indirect Energy</option>
                                            <option value="scope3" {{ old('consumption_assignments.' . $index . '.scope') == 'scope3' ? 'selected' : '' }}>Scope 3 - Other Indirect</option>
                                        </select>
                                        <input type="hidden" name="consumption_assignments[{{ $index }}][value]" value="{{ $consumption['value'] }}">
                                        <input type="hidden" name="consumption_assignments[{{ $index }}][unit]" value="{{ $consumption['unit'] }}">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            
            <!-- Location Assignment -->
            <div>
                <label for="location_id" class="block text-sm font-medium text-gray-700 mb-2">Location Assignment</label>
                <select name="location_id" id="location_id" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">No specific location</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" {{ (old('location_id', $document->location_id) == $location->id) ? 'selected' : '' }}>
                            {{ $location->name }} - {{ $location->address }}
                        </option>
                    @endforeach
                </select>
                @error('location_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <a href="{{ route('document-uploads.show', $document) }}" class="btn btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Document
                </a>
                
                <div class="flex space-x-3">
                    <button type="button" onclick="autoAssignScopes()" class="btn btn-outline">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Auto Assign
                    </button>
                    
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Save Assignments
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function autoAssignScopes() {
    // Auto-assign based on common patterns
    const serviceSelects = document.querySelectorAll('select[name*="service_assignments"]');
    const chargeSelects = document.querySelectorAll('select[name*="charge_assignments"]');
    const consumptionSelects = document.querySelectorAll('select[name*="consumption_assignments"]');
    
    // Auto-assign services
    serviceSelects.forEach(select => {
        const serviceType = select.closest('.bg-white').querySelector('h4').textContent;
        if (serviceType.includes('Electricity')) {
            select.value = 'scope2'; // Electricity is typically Scope 2
        } else if (serviceType.includes('Water')) {
            select.value = 'scope3'; // Water is typically Scope 3
        } else if (serviceType.includes('Fuel')) {
            select.value = 'scope1'; // Fuel is typically Scope 1
        }
    });
    
    // Auto-assign charges (most are Scope 2 for energy bills)
    chargeSelects.forEach(select => {
        select.value = 'scope2';
    });
    
    // Auto-assign consumption (electricity = scope2, water = scope3)
    consumptionSelects.forEach(select => {
        const unit = select.closest('.bg-white').querySelector('p').textContent;
        if (unit.includes('kWh')) {
            select.value = 'scope2';
        } else if (unit.includes('cubic') || unit.includes('m³')) {
            select.value = 'scope3';
        }
    });
    
    alert('Scopes have been auto-assigned based on common patterns. Please review and adjust if needed.');
}
</script>
@endsection
