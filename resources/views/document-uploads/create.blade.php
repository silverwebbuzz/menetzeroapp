@extends('layouts.app')

@section('page-title', 'Upload Document')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg border">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-xl font-semibold text-gray-900">Upload Document</h1>
            <p class="mt-1 text-sm text-gray-600">Upload bills, invoices, or receipts for automatic data extraction</p>
        </div>
        
        <form method="POST" action="{{ route('document-uploads.store') }}" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf
            
            <!-- File Upload -->
            <div>
                <label for="file" class="block text-sm font-medium text-gray-700 mb-2">
                    Document File <span class="text-red-500">*</span>
                </label>
                <div id="upload-area" class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 transition-colors">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                <span>Upload a file</span>
                                <input id="file" name="file" type="file" class="sr-only" accept=".pdf,.jpg,.jpeg,.png" required>
                            </label>
                            <p class="pl-1">or drag and drop</p>
                        </div>
                        <p class="text-xs text-gray-500">PDF, JPG, PNG up to 10MB</p>
                    </div>
                </div>
                @error('file')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Document Type -->
            <div>
                <label for="source_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Document Type <span class="text-red-500">*</span>
                </label>
                <select name="source_type" id="source_type" required 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select document type</option>
                    <option value="electricity" {{ old('source_type') === 'electricity' ? 'selected' : '' }}>⚡ Electricity Bill</option>
                    <option value="fuel" {{ old('source_type') === 'fuel' ? 'selected' : '' }}>⛽ Fuel Receipt</option>
                    <option value="waste" {{ old('source_type') === 'waste' ? 'selected' : '' }}>🗑️ Waste Disposal</option>
                    <option value="water" {{ old('source_type') === 'water' ? 'selected' : '' }}>💧 Water Bill</option>
                    <option value="transport" {{ old('source_type') === 'transport' ? 'selected' : '' }}>🚛 Transport/Logistics</option>
                    <option value="other" {{ old('source_type') === 'other' ? 'selected' : '' }}>📄 Other Document</option>
                </select>
                @error('source_type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Document Category -->
            <div>
                <label for="document_category" class="block text-sm font-medium text-gray-700 mb-2">
                    Document Category <span class="text-red-500">*</span>
                </label>
                <select name="document_category" id="document_category" required 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select category</option>
                    <option value="bill" {{ old('document_category') === 'bill' ? 'selected' : '' }}>📋 Bill</option>
                    <option value="receipt" {{ old('document_category') === 'receipt' ? 'selected' : '' }}>🧾 Receipt</option>
                    <option value="invoice" {{ old('document_category') === 'invoice' ? 'selected' : '' }}>📄 Invoice</option>
                    <option value="statement" {{ old('document_category') === 'statement' ? 'selected' : '' }}>📊 Statement</option>
                    <option value="contract" {{ old('document_category') === 'contract' ? 'selected' : '' }}>📝 Contract</option>
                    <option value="other" {{ old('document_category') === 'other' ? 'selected' : '' }}>📁 Other</option>
                </select>
                @error('document_category')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Location (Optional) -->
            <div>
                <label for="location_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Location (Optional)
                </label>
                <select name="location_id" id="location_id" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">No specific location</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                            {{ $location->name }} - {{ $location->address }}
                        </option>
                    @endforeach
                </select>
                @error('location_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Upload Guidelines -->
            <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Upload Guidelines</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>Supported formats: PDF, JPG, PNG</li>
                                <li>Maximum file size: 10MB</li>
                                <li>Ensure text is clearly visible and readable</li>
                                <li>For best results, use high-quality scans or photos</li>
                                <li>Make sure important data (amounts, dates, quantities) is visible</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ route('document-uploads.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    Upload Document
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// File upload preview
document.getElementById('file').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const fileName = file.name;
        const fileSize = (file.size / 1024 / 1024).toFixed(2) + ' MB';
        
        // Update the upload area to show file info
        const uploadArea = document.getElementById('upload-area');
        uploadArea.innerHTML = `
            <div class="space-y-1 text-center">
                <svg class="mx-auto h-12 w-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <div class="text-sm text-gray-900 font-medium">${fileName}</div>
                <div class="text-xs text-gray-500">${fileSize}</div>
                <button type="button" onclick="resetFileUpload()" class="text-sm text-blue-600 hover:text-blue-500">
                    Choose different file
                </button>
            </div>
        `;
    }
});

function resetFileUpload() {
    // Reset the file input
    const fileInput = document.getElementById('file');
    fileInput.value = '';
    
    // Reset the upload area
    const uploadArea = document.getElementById('upload-area');
    uploadArea.innerHTML = `
        <div class="space-y-1 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <div class="flex text-sm text-gray-600">
                <label for="file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                    <span>Upload a file</span>
                    <input id="file" name="file" type="file" class="sr-only" accept=".pdf,.jpg,.jpeg,.png" required>
                </label>
                <p class="pl-1">or drag and drop</p>
            </div>
            <p class="text-xs text-gray-500">PDF, JPG, PNG up to 10MB</p>
        </div>
    `;
    
    // Re-attach the change event listener
    document.getElementById('file').addEventListener('change', arguments.callee);
}
</script>
@endsection
