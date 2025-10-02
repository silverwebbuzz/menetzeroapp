<div class="space-y-6">
    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-indigo-800">Supporting Evidence</h3>
                <div class="mt-2 text-sm text-indigo-700">
                    <p>Upload supporting documents to verify your emission data. Our OCR system will automatically extract relevant information.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- File Upload Area -->
    <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-gray-400 transition-colors" id="drop-zone">
        <div class="space-y-4">
            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <div>
                <label for="files" class="cursor-pointer">
                    <span class="mt-2 block text-sm font-medium text-gray-900">
                        Upload supporting documents
                    </span>
                    <span class="mt-1 block text-sm text-gray-500">
                        Drag and drop files here, or click to select files
                    </span>
                </label>
                <input type="file" 
                       id="files" 
                       name="files[]" 
                       multiple 
                       accept=".pdf,.jpg,.jpeg,.png"
                       class="hidden"
                       onchange="handleFileSelect(this)">
            </div>
            <p class="text-xs text-gray-500">
                PDF, JPG, PNG files up to 10MB each
            </p>
        </div>
    </div>

    <!-- Uploaded Files Display -->
    <div id="uploaded-files" class="space-y-4" style="display: none;">
        <h4 class="text-lg font-medium text-gray-900">Uploaded Files</h4>
        <div id="files-list" class="space-y-2"></div>
    </div>

    <!-- OCR Processing Status -->
    <div id="ocr-status" class="hidden">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="animate-spin h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-blue-800">Processing files with OCR...</p>
                    <p class="text-sm text-blue-600">Extracting data from your documents</p>
                </div>
            </div>
        </div>
    </div>

    <!-- OCR Results -->
    <div id="ocr-results" class="hidden">
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800">Data Extracted Successfully!</h3>
                    <div class="mt-2 text-sm text-green-700">
                        <p id="ocr-message">Data has been extracted from your documents and will be used to pre-fill the form.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Types Guide -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 mb-3">Recommended Documents</h4>
            <ul class="space-y-2 text-sm text-gray-600">
                <li class="flex items-center">
                    <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    Electricity bills (DEWA, ADDC, etc.)
                </li>
                <li class="flex items-center">
                    <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    Fuel receipts and invoices
                </li>
                <li class="flex items-center">
                    <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    Waste disposal receipts
                </li>
                <li class="flex items-center">
                    <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    Water bills
                </li>
                <li class="flex items-center">
                    <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    Travel expense reports
                </li>
            </ul>
        </div>

        <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 mb-3">OCR Features</h4>
            <ul class="space-y-2 text-sm text-gray-600">
                <li class="flex items-center">
                    <svg class="w-4 h-4 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    Automatic data extraction
                </li>
                <li class="flex items-center">
                    <svg class="w-4 h-4 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    Form pre-filling
                </li>
                <li class="flex items-center">
                    <svg class="w-4 h-4 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    Smart document recognition
                </li>
                <li class="flex items-center">
                    <svg class="w-4 h-4 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    Data validation
                </li>
            </ul>
        </div>
    </div>

    <!-- Information Box -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Document Upload Guidelines</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>Upload clear, high-quality images or PDFs of your supporting documents. Our OCR system will automatically extract relevant data to help pre-fill your emission calculations.</p>
                    <p class="mt-2 text-xs text-blue-600">
                        💡 <strong>Tip:</strong> Ensure documents are well-lit and text is clearly visible for best OCR results.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let uploadedFiles = [];

function handleFileSelect(input) {
    const files = Array.from(input.files);
    uploadedFiles = [...uploadedFiles, ...files];
    displayUploadedFiles();
    
    // Process files with OCR
    files.forEach(file => {
        processFileWithOCR(file);
    });
}

function displayUploadedFiles() {
    const container = document.getElementById('uploaded-files');
    const list = document.getElementById('files-list');
    
    if (uploadedFiles.length > 0) {
        container.style.display = 'block';
        list.innerHTML = '';
        
        uploadedFiles.forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'flex items-center justify-between p-3 bg-gray-50 rounded-lg';
            fileItem.innerHTML = `
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-gray-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-sm font-medium text-gray-900">${file.name}</span>
                    <span class="ml-2 text-xs text-gray-500">(${(file.size / 1024 / 1024).toFixed(2)} MB)</span>
                </div>
                <button type="button" onclick="removeFile(${index})" class="text-red-500 hover:text-red-700">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            `;
            list.appendChild(fileItem);
        });
    }
}

function removeFile(index) {
    uploadedFiles.splice(index, 1);
    displayUploadedFiles();
}

function processFileWithOCR(file) {
    const status = document.getElementById('ocr-status');
    const results = document.getElementById('ocr-results');
    const message = document.getElementById('ocr-message');
    
    // Show processing status
    status.classList.remove('hidden');
    
    // Simulate OCR processing
    setTimeout(() => {
        status.classList.add('hidden');
        
        // Show mock results
        message.textContent = `Data extracted from ${file.name}: Electricity consumption, fuel usage, and other relevant metrics have been identified.`;
        results.classList.remove('hidden');
        
        // In a real implementation, you would make an AJAX call to the OCR endpoint
        // fetch('/emission-form/ocr', {
        //     method: 'POST',
        //     body: formData
        // }).then(response => response.json())
        // .then(data => {
        //     // Handle OCR results
        // });
    }, 2000);
}

// Drag and drop functionality
const dropZone = document.getElementById('drop-zone');
const fileInput = document.getElementById('files');

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('border-emerald-400', 'bg-emerald-50');
});

dropZone.addEventListener('dragleave', (e) => {
    e.preventDefault();
    dropZone.classList.remove('border-emerald-400', 'bg-emerald-50');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('border-emerald-400', 'bg-emerald-50');
    
    const files = Array.from(e.dataTransfer.files);
    fileInput.files = e.dataTransfer.files;
    handleFileSelect(fileInput);
});

dropZone.addEventListener('click', () => {
    fileInput.click();
});
</script>
@endpush
