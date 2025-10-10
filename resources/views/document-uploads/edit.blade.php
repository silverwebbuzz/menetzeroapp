@extends('layouts.app')

@section('page-title', 'Edit Document Data')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg border">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-xl font-semibold text-gray-900">Edit Extracted Data</h1>
            <p class="mt-1 text-sm text-gray-600">Review and modify the automatically extracted data</p>
        </div>
        
        <form method="POST" action="{{ route('document-uploads.update', $document) }}" class="p-6 space-y-6">
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
            
            <!-- Location Selection -->
            <div>
                <label for="location_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Location (Optional)
                </label>
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
            
            <!-- Extracted Data Fields -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Extracted Data</h3>
                <p class="text-sm text-gray-600 mb-6">
                    Review and correct the extracted data from your {{ ucfirst($document->source_type) }} bill. 
                    Match the values with your actual bill to ensure accurate carbon calculations.
                </p>
                
                @if($document->processed_data)
                    <!-- DEWA Bill Structure -->
                    @if($document->source_type === 'electricity')
                        <!-- Bill Information Section -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <h4 class="text-sm font-semibold text-blue-900 mb-3">📋 Bill Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Bill Number</label>
                                    <input type="text" name="extracted_data[bill_number]" value="{{ old('extracted_data.bill_number', $document->processed_data['bill_number'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="e.g., 10003068141">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Issue Date</label>
                                    <input type="date" name="extracted_data[issue_date]" value="{{ old('extracted_data.issue_date', $document->processed_data['issue_date'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                                    <input type="date" name="extracted_data[due_date]" value="{{ old('extracted_data.due_date', $document->processed_data['due_date'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Number</label>
                                    <input type="text" name="extracted_data[account_number]" value="{{ old('extracted_data.account_number', $document->processed_data['account_number'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="e.g., 2031203304">
                                </div>
                            </div>
                        </div>

                        <!-- Electricity and Water Consumption (Main DEWA Services) -->
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                            <h4 class="text-sm font-semibold text-green-900 mb-3">⚡ Electricity and Water Consumption</h4>
                            <p class="text-xs text-green-700 mb-4">Charges for your usage, with different rates or slabs depending on consumption levels</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Electricity Consumption (kWh) *</label>
                                    <input type="number" step="0.01" name="extracted_data[electricity_consumption_kwh]" value="{{ old('extracted_data.electricity_consumption_kwh', $document->processed_data['electricity_consumption_kwh'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="e.g., 1234.5" required>
                                    <p class="text-xs text-gray-500 mt-1">Required for carbon emissions calculation</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Electricity Charges (AED)</label>
                                    <input type="number" step="0.01" name="extracted_data[electricity_charges_aed]" value="{{ old('extracted_data.electricity_charges_aed', $document->processed_data['electricity_charges_aed'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="e.g., 85.60">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Water Consumption (Cubic Meters)</label>
                                    <input type="number" step="0.01" name="extracted_data[water_consumption_cubic_meters]" value="{{ old('extracted_data.water_consumption_cubic_meters', $document->processed_data['water_consumption_cubic_meters'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="e.g., 45.2">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Water Charges (AED)</label>
                                    <input type="number" step="0.01" name="extracted_data[water_charges_aed]" value="{{ old('extracted_data.water_charges_aed', $document->processed_data['water_charges_aed'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="e.g., 33.66">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Other DEWA Services (AED)</label>
                                    <input type="number" step="0.01" name="extracted_data[dewa_other_services_aed]" value="{{ old('extracted_data.dewa_other_services_aed', $document->processed_data['dewa_other_services_aed'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="e.g., 100.00">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">DEWA Total (AED)</label>
                                    <input type="number" step="0.01" name="extracted_data[dewa_total_aed]" value="{{ old('extracted_data.dewa_total_aed', $document->processed_data['dewa_total_aed'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="e.g., 219.26">
                                </div>
                            </div>
                        </div>

                        <!-- Municipal Fee Section -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <h4 class="text-sm font-semibold text-blue-900 mb-3">🏛️ Municipal Fee</h4>
                            <p class="text-xs text-blue-700 mb-4">A fee equal to 5% of your annual rent, divided by 12 months</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Municipal Fee (AED)</label>
                                    <input type="number" step="0.01" name="extracted_data[municipal_fee_aed]" value="{{ old('extracted_data.municipal_fee_aed', $document->processed_data['municipal_fee_aed'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="e.g., 150.00">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Municipal Fee Rate</label>
                                    <input type="text" name="extracted_data[municipal_fee_percentage]" value="{{ old('extracted_data.municipal_fee_percentage', $document->processed_data['municipal_fee_percentage'] ?? '5%') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="5%" readonly>
                                    <p class="text-xs text-gray-500 mt-1">Fixed rate by government</p>
                                </div>
                            </div>
                        </div>

                        <!-- Chiller/Cold Water Charge Section -->
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6">
                            <h4 class="text-sm font-semibold text-purple-900 mb-3">❄️ Chiller/Cold Water Charge</h4>
                            <p class="text-xs text-purple-700 mb-4">For buildings with central air conditioning, varies based on apartment size</p>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Chiller Charge (AED)</label>
                                    <input type="number" step="0.01" name="extracted_data[chiller_charge_aed]" value="{{ old('extracted_data.chiller_charge_aed', $document->processed_data['chiller_charge_aed'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="e.g., 200.00">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cold Water Charge (AED)</label>
                                    <input type="number" step="0.01" name="extracted_data[cold_water_charge_aed]" value="{{ old('extracted_data.cold_water_charge_aed', $document->processed_data['cold_water_charge_aed'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="e.g., 50.00">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Apartment Size (Sq Ft)</label>
                                    <input type="number" step="0.01" name="extracted_data[apartment_size_sqft]" value="{{ old('extracted_data.apartment_size_sqft', $document->processed_data['apartment_size_sqft'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="e.g., 1200">
                                </div>
                            </div>
                        </div>

                        <!-- Housing Fee Section -->
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6">
                            <h4 class="text-sm font-semibold text-orange-900 mb-3">🏠 Housing Fee</h4>
                            <p class="text-xs text-orange-700 mb-4">A fee paid by all residents, whether they own or rent. Owner is technically responsible but often included in tenant's bill</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Housing Fee (AED)</label>
                                    <input type="number" step="0.01" name="extracted_data[housing_fee_aed]" value="{{ old('extracted_data.housing_fee_aed', $document->processed_data['housing_fee_aed'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="e.g., 0.00">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Responsible Party</label>
                                    <select name="extracted_data[housing_fee_responsible]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select responsible party</option>
                                        <option value="Owner" {{ old('extracted_data.housing_fee_responsible', $document->processed_data['housing_fee_responsible'] ?? '') == 'Owner' ? 'selected' : '' }}>Owner</option>
                                        <option value="Tenant" {{ old('extracted_data.housing_fee_responsible', $document->processed_data['housing_fee_responsible'] ?? '') == 'Tenant' ? 'selected' : '' }}>Tenant</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Moving-in Charges Section -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                            <h4 class="text-sm font-semibold text-yellow-900 mb-3">🚚 Moving-in Charges</h4>
                            <p class="text-xs text-yellow-700 mb-4">When setting up a new connection, initial moving-in fees will be included on your first bill</p>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Moving-in Charges (AED)</label>
                                    <input type="number" step="0.01" name="extracted_data[moving_in_charges_aed]" value="{{ old('extracted_data.moving_in_charges_aed', $document->processed_data['moving_in_charges_aed'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="e.g., 0.00">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Connection Fee (AED)</label>
                                    <input type="number" step="0.01" name="extracted_data[connection_fee_aed]" value="{{ old('extracted_data.connection_fee_aed', $document->processed_data['connection_fee_aed'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="e.g., 0.00">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Deposit Amount (AED)</label>
                                    <input type="number" step="0.01" name="extracted_data[deposit_amount_aed]" value="{{ old('extracted_data.deposit_amount_aed', $document->processed_data['deposit_amount_aed'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="e.g., 0.00">
                                </div>
                            </div>
                        </div>

                        <!-- Dubai Municipality Section -->
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6">
                            <h4 class="text-sm font-semibold text-orange-900 mb-3">🏛️ Dubai Municipality Services</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Housing (AED)</label>
                                    <input type="number" step="0.01" name="extracted_data[municipality_housing]" value="{{ old('extracted_data.municipality_housing', $document->processed_data['municipality_housing'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="e.g., 0.00">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Sewerage (AED)</label>
                                    <input type="number" step="0.01" name="extracted_data[municipality_sewerage]" value="{{ old('extracted_data.municipality_sewerage', $document->processed_data['municipality_sewerage'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="e.g., 6.60">
                                </div>
                            </div>
                        </div>

                        <!-- Energy Consumption Summary (for Carbon Calculation) -->
                        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 mb-6">
                            <h4 class="text-sm font-semibold text-emerald-900 mb-3">🌱 Energy Consumption Summary (for Carbon Calculation)</h4>
                            <p class="text-xs text-emerald-700 mb-4">Total consumption values used for carbon emissions calculation</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Total Electricity (kWh) *</label>
                                    <input type="number" step="0.01" name="extracted_data[total_electricity_kwh]" value="{{ old('extracted_data.total_electricity_kwh', $document->processed_data['total_electricity_kwh'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="e.g., 1234.5" required>
                                    <p class="text-xs text-gray-500 mt-1">Primary for Scope 2 emissions calculation</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Total Water (Cubic Meters)</label>
                                    <input type="number" step="0.01" name="extracted_data[total_water_cubic_meters]" value="{{ old('extracted_data.total_water_cubic_meters', $document->processed_data['total_water_cubic_meters'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="e.g., 45.2">
                                    <p class="text-xs text-gray-500 mt-1">For water-related emissions calculation</p>
                                </div>
                            </div>
                        </div>

                        <!-- Financial Summary Section -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                            <h4 class="text-sm font-semibold text-gray-900 mb-3">💰 Financial Summary</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Total Due (AED)</label>
                                    <input type="number" step="0.01" name="extracted_data[total_due]" value="{{ old('extracted_data.total_due', $document->processed_data['total_due'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="e.g., 245.86">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">VAT Amount (AED)</label>
                                    <input type="number" step="0.01" name="extracted_data[vat_amount]" value="{{ old('extracted_data.vat_amount', $document->processed_data['vat_amount'] ?? '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="e.g., 5.68">
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Generic fields for other document types -->
                        @foreach($document->processed_data as $key => $value)
                            <div class="flex items-center space-x-4 mb-4">
                                <div class="flex-1">
                                    <label for="extracted_data_{{ $key }}" class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ ucfirst(str_replace('_', ' ', $key)) }}
                                    </label>
                                    <input type="text" 
                                           name="extracted_data[{{ $key }}]" 
                                           id="extracted_data_{{ $key }}"
                                           value="{{ old('extracted_data.' . $key, $value) }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div class="flex-shrink-0">
                                    <button type="button" 
                                            onclick="document.getElementById('extracted_data_{{ $key }}').value = ''"
                                            class="text-sm text-red-600 hover:text-red-900">
                                        Clear
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    @endif
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No data extracted</h3>
                            <p class="mt-1 text-sm text-gray-500">This document hasn't been processed yet or failed to extract data.</p>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Add Custom Fields -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Add Custom Fields</h3>
                <div id="custom-fields">
                    <!-- Custom fields will be added here dynamically -->
                </div>
                <button type="button" 
                        onclick="addCustomField()" 
                        class="mt-2 inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add Field
                </button>
            </div>
            
            <!-- Validation Messages -->
            @if($document->ocr_confidence && $document->ocr_confidence < 70)
                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">Low Confidence Warning</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>The OCR confidence for this document is {{ $document->ocr_confidence }}%. Please review the extracted data carefully and make corrections as needed.</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            
            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ route('document-uploads.show', $document) }}" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let customFieldCount = 0;

function addCustomField() {
    const container = document.getElementById('custom-fields');
    const fieldId = 'custom_field_' + customFieldCount;
    
    const fieldHtml = `
        <div class="flex items-center space-x-4 mb-4" id="field_${fieldId}">
            <div class="flex-1">
                <input type="text" 
                       name="custom_fields[${fieldId}][key]" 
                       placeholder="Field name (e.g., amount, quantity)"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex-1">
                <input type="text" 
                       name="custom_fields[${fieldId}][value]" 
                       placeholder="Field value"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex-shrink-0">
                <button type="button" 
                        onclick="removeCustomField('field_${fieldId}')"
                        class="text-red-600 hover:text-red-900">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', fieldHtml);
    customFieldCount++;
}

function removeCustomField(fieldId) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.remove();
    }
}

// Auto-focus first empty field
document.addEventListener('DOMContentLoaded', function() {
    const firstInput = document.querySelector('input[type="text"]');
    if (firstInput) {
        firstInput.focus();
    }
});
</script>
@endsection
