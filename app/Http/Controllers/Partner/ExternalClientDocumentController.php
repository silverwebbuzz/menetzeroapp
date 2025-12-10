<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PartnerExternalClient;
use App\Models\PartnerExternalClientDocument;
use Illuminate\Support\Facades\Storage;

class ExternalClientDocumentController extends Controller
{
    /**
     * Display documents for an external client.
     */
    public function index($clientId)
    {
        $client = PartnerExternalClient::findOrFail($clientId);
        
        // Verify ownership
        $user = auth()->user();
        $partnerId = $user->getActiveCompany()->id;
        
        if ($client->partner_company_id != $partnerId) {
            abort(403, 'Unauthorized');
        }

        $documents = $client->documents()->orderBy('created_at', 'desc')->get();

        return view('partner.clients.documents.index', compact('client', 'documents'));
    }

    /**
     * Store a newly uploaded document.
     */
    public function store(Request $request, $clientId)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'source_type' => 'required|in:dewa,electricity,fuel,waste,water,transport,other',
            'document_category' => 'required|in:bill,receipt,invoice,statement,contract,other',
        ]);

        $client = PartnerExternalClient::findOrFail($clientId);
        
        // Verify ownership
        $user = auth()->user();
        $partnerId = $user->getActiveCompany()->id;
        
        if ($client->partner_company_id != $partnerId) {
            abort(403, 'Unauthorized');
        }

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('partner_external_client_documents/' . $clientId, $fileName, 'public');

        $document = PartnerExternalClientDocument::create([
            'partner_external_client_id' => $clientId,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'original_name' => $file->getClientOriginalName(),
            'source_type' => $request->source_type,
            'document_category' => $request->document_category,
            'status' => 'pending',
        ]);

        return redirect()->route('partner.clients.documents.index', $clientId)
            ->with('success', 'Document uploaded successfully');
    }

    /**
     * Display the specified document.
     */
    public function show($clientId, $documentId)
    {
        $client = PartnerExternalClient::findOrFail($clientId);
        $document = PartnerExternalClientDocument::findOrFail($documentId);
        
        // Verify ownership
        $user = auth()->user();
        $partnerId = $user->getActiveCompany()->id;
        
        if ($client->partner_company_id != $partnerId || $document->partner_external_client_id != $clientId) {
            abort(403, 'Unauthorized');
        }

        return view('partner.clients.documents.show', compact('client', 'document'));
    }

    /**
     * Remove the specified document.
     */
    public function destroy($clientId, $documentId)
    {
        $client = PartnerExternalClient::findOrFail($clientId);
        $document = PartnerExternalClientDocument::findOrFail($documentId);
        
        // Verify ownership
        $user = auth()->user();
        $partnerId = $user->getActiveCompany()->id;
        
        if ($client->partner_company_id != $partnerId || $document->partner_external_client_id != $clientId) {
            abort(403, 'Unauthorized');
        }

        // Delete file
        Storage::disk('public')->delete($document->file_path);

        $document->delete();

        return redirect()->route('partner.clients.documents.index', $clientId)
            ->with('success', 'Document deleted successfully');
    }
}

