<?php

namespace App\Http\Controllers\Consultant;

use App\Data\ConsultantOptions;
use App\Http\Controllers\Controller;
use App\Models\ConsultantDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index()
    {
        $consultant = Auth::guard('consultant')->user();
        $consultant->load('documents');

        return view('consultant.documents', [
            'consultant' => $consultant,
            'documentTypes' => ConsultantOptions::DOCUMENT_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $consultant = Auth::guard('consultant')->user();

        $data = $request->validate([
            'document_type' => 'required|in:' . implode(',', array_keys(ConsultantOptions::DOCUMENT_TYPES)),
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $file = $request->file('document');
        $path = $file->store('consultant-documents/' . $consultant->id, 'local');

        $consultant->documents()->create([
            'document_type' => $data['document_type'],
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'status' => 'pending',
        ]);

        return redirect()->route('consultant.documents.index')
            ->with('success', 'Document uploaded.');
    }

    public function destroy(ConsultantDocument $document)
    {
        $consultant = Auth::guard('consultant')->user();

        if ($document->consultant_id !== $consultant->id) {
            abort(403);
        }

        if (in_array($consultant->status, ['approved', 'pending_review'], true)) {
            return back()->with('error', 'Documents cannot be removed while your application is under review or approved. Contact support.');
        }

        $document->deleteFile();
        $document->delete();

        return redirect()->route('consultant.documents.index')
            ->with('success', 'Document removed.');
    }
}
