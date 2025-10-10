@extends('layouts.app')

@section('page-title', 'Document Details')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $document->original_name }}</h1>
            <p class="mt-1 text-sm text-gray-600">
                Uploaded {{ $document->created_at->format('M j, Y g:i A') }}
                @if($document->location)
                    • {{ $document->location->name }}
                @endif
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex space-x-3">
            @if($document->canBeEdited())
                @if($document->source_type === 'electricity')
                    <a href="{{ route('document-uploads.field-mapping', $document) }}" class="btn btn-secondary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Map Fields
                    </a>
                @else
                    <a href="{{ route('document-uploads.edit', $document) }}" class="btn btn-secondary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit Data
                    </a>
                @endif
            @endif
            
            @if($document->status === 'failed')
                <form method="POST" action="{{ route('document-uploads.retry-ocr', $document) }}" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Retry OCR
                    </button>
                </form>
            @endif
            
            <a href="{{ route('document-uploads.index') }}" class="btn btn-secondary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to List
            </a>
        </div>
    </div>

    <!-- Document Info -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Document Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-white shadow-sm rounded-lg border">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Document Information</h2>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">File Name</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $document->original_name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">File Size</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $document->file_size_human }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Document Type</label>
                            <p class="mt-1 text-sm text-gray-900">{{ ucfirst($document->source_type) }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Category</label>
                            <p class="mt-1 text-sm text-gray-900">{{ ucfirst($document->document_category) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Extracted Data -->
            @if($document->extracted_data || $document->processed_data)
                <div class="bg-white shadow-sm rounded-lg border">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Extracted Data</h2>
                        @if($document->ocr_confidence)
                            <p class="mt-1 text-sm text-gray-600">Confidence: {{ $document->ocr_confidence }}%</p>
                        @endif
                    </div>
                    <div class="px-6 py-4">
                        @if($document->processed_data)
                            <!-- Debug: Show raw processed data -->
                            <div class="mb-4 p-3 bg-gray-100 rounded text-xs">
                                <strong>Debug - Processed Data:</strong><br>
                                <pre>{{ json_encode($document->processed_data, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                            
                            @if($document->source_type === 'dewa' || $document->source_type === 'electricity')
                                <!-- DEWA Bill Data Structure -->
                                <div class="space-y-6">
                                    @if(isset($document->processed_data['extracted_services']) || isset($document->processed_data['extracted_charges']) || isset($document->processed_data['extracted_consumption']))
                                        <!-- Extracted Data Summary -->
                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                            <h4 class="text-sm font-semibold text-blue-900 mb-3">🏢 DEWA Bill Data Extracted</h4>
                                            <p class="text-xs text-blue-700 mb-4">Data extracted from your DEWA bill - ready for scope assignment</p>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                                <div class="text-center">
                                                    <div class="text-2xl font-bold text-blue-900">{{ count($document->processed_data['extracted_services'] ?? []) }}</div>
                                                    <div class="text-sm text-blue-700">Services Found</div>
                                                </div>
                                                <div class="text-center">
                                                    <div class="text-2xl font-bold text-blue-900">{{ count($document->processed_data['extracted_charges'] ?? []) }}</div>
                                                    <div class="text-sm text-blue-700">Charges Found</div>
                                                </div>
                                                <div class="text-center">
                                                    <div class="text-2xl font-bold text-blue-900">{{ count($document->processed_data['extracted_consumption'] ?? []) }}</div>
                                                    <div class="text-sm text-blue-700">Consumption Data</div>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-center justify-between">
                                                <div class="text-sm text-blue-700">
                                                    Ready to assign to Scope 1, 2, or 3 categories
                                                </div>
                                                <a href="{{ route('document-uploads.assign-scope', $document) }}" class="btn btn-primary">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    Assign to Scopes
                                                </a>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- DEWA Services -->
                                    @if(isset($document->processed_data['dewa_services']))
                                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                            <h4 class="text-sm font-semibold text-green-900 mb-3">⚡ DEWA Services</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                @foreach($document->processed_data['dewa_services'] as $key => $value)
                                                    <div class="flex justify-between items-center py-1">
                                                        <span class="text-sm font-medium text-gray-600">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                                                        <span class="text-sm text-gray-900 font-medium">{{ is_numeric($value) ? number_format($value, 2) : $value }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Energy Consumption -->
                                    @if(isset($document->processed_data['energy_consumption']))
                                        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                                            <h4 class="text-sm font-semibold text-emerald-900 mb-3">🌱 Energy Consumption</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                @foreach($document->processed_data['energy_consumption'] as $key => $value)
                                                    <div class="flex justify-between items-center py-1">
                                                        <span class="text-sm font-medium text-gray-600">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                                                        <span class="text-sm text-gray-900 font-medium">{{ is_numeric($value) ? number_format($value, 2) : $value }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Carbon Footprint -->
                                    @if(isset($document->processed_data['carbon_footprint']))
                                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                                            <h4 class="text-sm font-semibold text-purple-900 mb-3">🌍 Carbon Footprint</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                @foreach($document->processed_data['carbon_footprint'] as $key => $value)
                                                    <div class="flex justify-between items-center py-1">
                                                        <span class="text-sm font-medium text-gray-600">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                                                        <span class="text-sm text-gray-900 font-medium">
                                                            @if(is_array($value))
                                                                @foreach($value as $subKey => $subValue)
                                                                    <div class="text-xs">{{ ucfirst(str_replace('_', ' ', $subKey)) }}: {{ is_numeric($subValue) ? number_format($subValue, 2) : $subValue }}</div>
                                                                @endforeach
                                                            @else
                                                                {{ is_numeric($value) ? number_format($value, 2) : $value }}
                                                            @endif
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <!-- Generic Data Structure -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    @foreach($document->processed_data as $key => $value)
                                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                            <span class="text-sm font-medium text-gray-600 capitalize">{{ str_replace('_', ' ', $key) }}</span>
                                            <span class="text-sm text-gray-900 font-medium">
                                                @if(is_array($value))
                                                    {{ json_encode($value) }}
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <p class="text-sm text-gray-500">No data extracted yet.</p>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Approved Data -->
            @if($document->approved_data)
                <div class="bg-white shadow-sm rounded-lg border">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Approved Data</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Approved by {{ $document->approvedBy->name ?? 'Unknown' }} 
                            on {{ $document->approved_at->format('M j, Y g:i A') }}
                        </p>
                    </div>
                    <div class="px-6 py-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach($document->approved_data as $key => $value)
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-sm font-medium text-gray-600 capitalize">{{ str_replace('_', ' ', $key) }}</span>
                                    <span class="text-sm text-gray-900 font-medium">{{ $value }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Processing Logs -->
            @if($document->processingLogs->count() > 0)
                <div class="bg-white shadow-sm rounded-lg border">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Processing Log</h2>
                    </div>
                    <div class="px-6 py-4">
                        <div class="space-y-3">
                            @foreach($document->processingLogs as $log)
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        @if($log->log_level === 'error')
                                            <div class="w-2 h-2 bg-red-400 rounded-full"></div>
                                        @elseif($log->log_level === 'warning')
                                            <div class="w-2 h-2 bg-yellow-400 rounded-full"></div>
                                        @else
                                            <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-900">{{ $log->message }}</p>
                                        <p class="text-xs text-gray-500">{{ $log->created_at->format('M j, Y g:i A') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status Card -->
            <div class="bg-white shadow-sm rounded-lg border">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Status</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                            @if($document->status === 'pending') bg-yellow-100 text-yellow-800
                            @elseif($document->status === 'processing') bg-blue-100 text-blue-800
                            @elseif($document->status === 'extracted') bg-green-100 text-green-800
                            @elseif($document->status === 'reviewed') bg-purple-100 text-purple-800
                            @elseif($document->status === 'approved') bg-green-100 text-green-800
                            @elseif($document->status === 'rejected') bg-red-100 text-red-800
                            @elseif($document->status === 'integrated') bg-green-100 text-green-800
                            @elseif($document->status === 'failed') bg-red-100 text-red-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ ucfirst($document->status) }}
                        </span>
                    </div>
                    
                    @if($document->ocr_error_message)
                        <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-md">
                            <p class="text-sm text-red-800">{{ $document->ocr_error_message }}</p>
                        </div>
                    @endif
                    
                    @if($document->rejection_reason)
                        <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                            <p class="text-sm text-yellow-800">{{ $document->rejection_reason }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Actions Card -->
            <div class="bg-white shadow-sm rounded-lg border">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Actions</h3>
                </div>
                <div class="px-6 py-4 space-y-3">
                    @if($document->canBeApproved())
                        <form method="POST" action="{{ route('document-uploads.approve', $document) }}" 
                              onsubmit="return confirm('Are you sure you want to approve this document? This will integrate it with your emission data.')">
                            @csrf
                            <input type="hidden" name="approved_data" value="{{ json_encode($document->processed_data) }}">
                            <button type="submit" class="w-full btn btn-primary">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Approve & Integrate
                            </button>
                        </form>
                    @endif
                    
                    @if($document->canBeEdited())
                        <a href="{{ route('document-uploads.edit', $document) }}" class="w-full btn btn-secondary">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Edit Data
                        </a>
                    @endif
                    
                    @if($document->status === 'failed')
                        <form method="POST" action="{{ route('document-uploads.retry-ocr', $document) }}">
                            @csrf
                            <button type="submit" class="w-full btn btn-warning">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Retry OCR
                            </button>
                        </form>
                    @endif
                    
                    @if($document->status !== 'integrated')
                        <form method="POST" action="{{ route('document-uploads.destroy', $document) }}" 
                              onsubmit="return confirm('Are you sure you want to delete this document? This action cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full btn btn-danger">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Delete Document
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Integration Info -->
            @if($document->measurement_id)
                <div class="bg-white shadow-sm rounded-lg border">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Integration</h3>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-sm text-gray-600">This document has been integrated with:</p>
                        <p class="mt-1 text-sm font-medium text-gray-900">Measurement #{{ $document->measurement_id }}</p>
                        <a href="{{ route('measurements.show', $document->measurement_id) }}" 
                           class="mt-2 inline-flex items-center text-sm text-blue-600 hover:text-blue-900">
                            View Measurement
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
