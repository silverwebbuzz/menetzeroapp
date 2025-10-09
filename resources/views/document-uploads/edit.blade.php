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
                <div class="space-y-4">
                    @if($document->processed_data)
                        @foreach($document->processed_data as $key => $value)
                            <div class="flex items-center space-x-4">
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
