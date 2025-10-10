<?php

namespace App\Http\Controllers;

use App\Models\DocumentUpload;
use App\Models\DocumentProcessingLog;
use App\Models\Company;
use App\Models\Location;
use App\Services\OCRService;
use App\Services\DocumentProcessorService;
use App\Services\EmissionIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DocumentUploadController extends Controller
{
    protected $ocrService;
    protected $documentProcessor;
    protected $integrationService;

    public function __construct(
        OCRService $ocrService,
        DocumentProcessorService $documentProcessor,
        EmissionIntegrationService $integrationService
    ) {
        $this->ocrService = $ocrService;
        $this->documentProcessor = $documentProcessor;
        $this->integrationService = $integrationService;
    }

    /**
     * Display a listing of documents
     */
    public function index(Request $request)
    {
        $query = DocumentUpload::with(['company', 'location', 'approvedBy'])
            ->where('company_id', Auth::user()->company_id);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('source_type')) {
            $query->where('source_type', $request->source_type);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('file_name', 'like', '%' . $request->search . '%')
                  ->orWhere('original_name', 'like', '%' . $request->search . '%');
            });
        }

        $documents = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('document-uploads.index', compact('documents'));
    }

    /**
     * Show the form for creating a new document
     */
    public function create()
    {
        $locations = Location::where('company_id', Auth::user()->company_id)
            ->where('is_active', true)
            ->get();

        return view('document-uploads.create', compact('locations'));
    }

    /**
     * Store a newly uploaded document
     */
    public function store(Request $request)
    {
        // Debug: Log request data
        \Log::info('Document upload request', [
            'has_file' => $request->hasFile('file'),
            'file_name' => $request->file('file') ? $request->file('file')->getClientOriginalName() : 'No file',
            'all_input' => $request->all()
        ]);

        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
            'source_type' => 'required|in:electricity,fuel,waste,water,transport,other',
            'document_category' => 'required|in:bill,receipt,invoice,statement,contract,other',
            'location_id' => 'nullable|exists:locations,id',
        ]);

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('documents/' . Auth::user()->company_id, $fileName, 'private');

            $document = DocumentUpload::create([
                'company_id' => Auth::user()->company_id,
                'location_id' => $request->location_id,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'original_name' => $originalName,
                'source_type' => $request->source_type,
                'document_category' => $request->document_category,
                'status' => 'pending',
            ]);

            // Log the upload
            DocumentProcessingLog::log(
                $document->id,
                'info',
                'Document uploaded successfully',
                ['original_name' => $originalName, 'file_size' => $file->getSize()],
                'upload'
            );

            // Queue OCR processing
            $this->documentProcessor->processDocument($document);

            return redirect()->route('document-uploads.show', $document)
                ->with('success', 'Document uploaded successfully. OCR processing has started.');

        } catch (\Exception $e) {
            DocumentProcessingLog::log(
                $document->id ?? null,
                'error',
                'Failed to upload document: ' . $e->getMessage(),
                ['error' => $e->getTraceAsString()],
                'upload'
            );

            return back()->withErrors(['file' => 'Failed to upload document. Please try again.']);
        }
    }

    /**
     * Display the specified document
     */
    public function show(DocumentUpload $document)
    {
        // Check if user has access to this document
        if ($document->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to document.');
        }

        $document->load(['company', 'location', 'approvedBy', 'processingLogs' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        return view('document-uploads.show', compact('document'));
    }

    /**
     * Show the form for editing extracted data
     */
    public function edit(DocumentUpload $document)
    {
        // Check if user has access to this document
        if ($document->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to document.');
        }

        if (!$document->canBeEdited()) {
            return redirect()->route('document-uploads.show', $document)
                ->with('error', 'This document cannot be edited in its current status.');
        }

        $locations = Location::where('company_id', Auth::user()->company_id)
            ->where('is_active', true)
            ->get();

        return view('document-uploads.edit', compact('document', 'locations'));
    }

    /**
     * Update the document's extracted data
     */
    public function update(Request $request, DocumentUpload $document)
    {
        // Check if user has access to this document
        if ($document->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to document.');
        }

        if (!$document->canBeEdited()) {
            return redirect()->route('document-uploads.show', $document)
                ->with('error', 'This document cannot be edited in its current status.');
        }

        $request->validate([
            'extracted_data' => 'required|array',
            'location_id' => 'nullable|exists:locations,id',
        ]);

        try {
            $document->update([
                'processed_data' => $request->extracted_data,
                'location_id' => $request->location_id,
                'status' => 'reviewed',
            ]);

            // Log the update
            DocumentProcessingLog::log(
                $document->id,
                'info',
                'Document data updated by user',
                ['updated_fields' => array_keys($request->extracted_data)],
                'review'
            );

            return redirect()->route('document-uploads.show', $document)
                ->with('success', 'Document data updated successfully.');

        } catch (\Exception $e) {
            DocumentProcessingLog::log(
                $document->id,
                'error',
                'Failed to update document: ' . $e->getMessage(),
                ['error' => $e->getTraceAsString()],
                'review'
            );

            return back()->withErrors(['error' => 'Failed to update document. Please try again.']);
        }
    }

    /**
     * Approve the document and integrate with emission sources
     */
    public function approve(Request $request, DocumentUpload $document)
    {
        // Check if user has access to this document
        if ($document->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to document.');
        }

        if (!$document->canBeApproved()) {
            return redirect()->route('document-uploads.show', $document)
                ->with('error', 'This document cannot be approved in its current status.');
        }

        $request->validate([
            'approved_data' => 'required|array',
        ]);

        try {
            $document->update([
                'approved_data' => $request->approved_data,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'status' => 'approved',
            ]);

            // Log the approval
            DocumentProcessingLog::log(
                $document->id,
                'info',
                'Document approved by user',
                ['approved_by' => Auth::id(), 'approved_data' => $request->approved_data],
                'approval'
            );

            // Integrate with emission sources
            $measurement = $this->integrationService->integrateDocument($document);

            return redirect()->route('document-uploads.show', $document)
                ->with('success', 'Document approved and integrated successfully. Measurement ID: ' . $measurement->id);

        } catch (\Exception $e) {
            DocumentProcessingLog::log(
                $document->id,
                'error',
                'Failed to approve document: ' . $e->getMessage(),
                ['error' => $e->getTraceAsString()],
                'approval'
            );

            return back()->withErrors(['error' => 'Failed to approve document. Please try again.']);
        }
    }

    /**
     * Reject the document
     */
    public function reject(Request $request, DocumentUpload $document)
    {
        // Check if user has access to this document
        if ($document->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to document.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        try {
            $document->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
            ]);

            // Log the rejection
            DocumentProcessingLog::log(
                $document->id,
                'info',
                'Document rejected by user',
                ['rejection_reason' => $request->rejection_reason],
                'rejection'
            );

            return redirect()->route('document-uploads.index')
                ->with('success', 'Document rejected successfully.');

        } catch (\Exception $e) {
            DocumentProcessingLog::log(
                $document->id,
                'error',
                'Failed to reject document: ' . $e->getMessage(),
                ['error' => $e->getTraceAsString()],
                'rejection'
            );

            return back()->withErrors(['error' => 'Failed to reject document. Please try again.']);
        }
    }

    /**
     * Delete the document
     */
    public function destroy(DocumentUpload $document)
    {
        // Check if user has access to this document
        if ($document->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to document.');
        }

        try {
            // Delete the file from storage
            if (Storage::disk('private')->exists($document->file_path)) {
                Storage::disk('private')->delete($document->file_path);
            }

            // Delete the document record
            $document->delete();

            return redirect()->route('document-uploads.index')
                ->with('success', 'Document deleted successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete document. Please try again.']);
        }
    }

    /**
     * Retry OCR processing for failed documents
     */
    public function retryOcr(DocumentUpload $document)
    {
        // Check if user has access to this document
        if ($document->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to document.');
        }

        if ($document->status !== 'failed') {
            return redirect()->route('document-uploads.show', $document)
                ->with('error', 'Only failed documents can be retried.');
        }

        try {
            $document->update([
                'status' => 'pending',
                'ocr_error_message' => null,
            ]);

            // Queue OCR processing again
            $this->documentProcessor->processDocument($document);

            return redirect()->route('document-uploads.show', $document)
                ->with('success', 'OCR processing has been restarted.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to restart OCR processing. Please try again.']);
        }
    }
}
