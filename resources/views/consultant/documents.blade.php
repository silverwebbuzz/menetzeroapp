@extends('consultant.layouts.app')

@section('title', 'Documents')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-2">Verification documents</h1>
<p class="text-sm text-gray-600 mb-6">Upload PDF or image files (max 10 MB). Trade license and CV are required before submission.</p>

<div class="grid lg:grid-cols-2 gap-6">
    <form method="POST" action="{{ route('consultant.documents.store') }}" enctype="multipart/form-data" class="bg-white border border-gray-200 rounded-xl p-6 space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Document type</label>
            <select name="document_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                @foreach($documentTypes as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">File</label>
            <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required class="w-full text-sm">
            @error('document')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg">Upload</button>
    </form>

    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Uploaded files</h2>
        @forelse($consultant->documents as $doc)
            <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                <div>
                    <div class="text-sm font-medium text-gray-900">{{ $doc->typeLabel() }}</div>
                    <div class="text-xs text-gray-500">{{ $doc->original_filename }}</div>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $doc->status === 'verified' ? 'bg-green-100 text-green-800' : ($doc->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-600') }}">
                        {{ ucfirst($doc->status) }}
                    </span>
                </div>
                @if(!in_array($consultant->status, ['approved', 'pending_review']))
                    <form action="{{ route('consultant.documents.destroy', $doc) }}" method="POST" onsubmit="return confirm('Remove this document?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-600 hover:underline">Remove</button>
                    </form>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-500">No documents uploaded yet.</p>
        @endforelse
    </div>
</div>
@endsection
