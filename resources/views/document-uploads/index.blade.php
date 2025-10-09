@extends('layouts.app')

@section('page-title', 'AI Smart Uploads')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">AI Smart Uploads</h1>
            <p class="mt-1 text-sm text-gray-600">Upload bills and documents for automatic data extraction</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('document-uploads.create') }}" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Upload Document
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg shadow-sm border">
        <form method="GET" class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Search documents..." 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-2">
                <select name="status" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="extracted" {{ request('status') === 'extracted' ? 'selected' : '' }}>Extracted</option>
                    <option value="reviewed" {{ request('status') === 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="integrated" {{ request('status') === 'integrated' ? 'selected' : '' }}>Integrated</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
                <select name="source_type" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Types</option>
                    <option value="electricity" {{ request('source_type') === 'electricity' ? 'selected' : '' }}>Electricity</option>
                    <option value="fuel" {{ request('source_type') === 'fuel' ? 'selected' : '' }}>Fuel</option>
                    <option value="waste" {{ request('source_type') === 'waste' ? 'selected' : '' }}>Waste</option>
                    <option value="water" {{ request('source_type') === 'water' ? 'selected' : '' }}>Water</option>
                    <option value="transport" {{ request('source_type') === 'transport' ? 'selected' : '' }}>Transport</option>
                    <option value="other" {{ request('source_type') === 'other' ? 'selected' : '' }}>Other</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Documents List -->
    <div class="bg-white shadow-sm rounded-lg border">
        @if($documents->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Confidence</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($documents as $document)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="text-2xl mr-3">{{ $document->file_icon }}</div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $document->original_name }}</div>
                                            <div class="text-sm text-gray-500">{{ $document->file_size_human }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ ucfirst($document->source_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
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
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($document->ocr_confidence)
                                        <div class="flex items-center">
                                            <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $document->ocr_confidence }}%"></div>
                                            </div>
                                            <span class="text-xs">{{ $document->ocr_confidence }}%</span>
                                        </div>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $document->created_at->format('M j, Y g:i A') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('document-uploads.show', $document) }}" 
                                           class="text-blue-600 hover:text-blue-900">View</a>
                                        
                                        @if($document->canBeEdited())
                                            <a href="{{ route('document-uploads.edit', $document) }}" 
                                               class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                        @endif
                                        
                                        @if($document->status === 'failed')
                                            <form method="POST" action="{{ route('document-uploads.retry-ocr', $document) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-yellow-600 hover:text-yellow-900">Retry</button>
                                            </form>
                                        @endif
                                        
                                        @if($document->status !== 'integrated')
                                            <form method="POST" action="{{ route('document-uploads.destroy', $document) }}" class="inline" 
                                                  onsubmit="return confirm('Are you sure you want to delete this document?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="px-6 py-3 border-t border-gray-200">
                {{ $documents->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No documents found</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by uploading your first document.</p>
                <div class="mt-6">
                    <a href="{{ route('document-uploads.create') }}" class="btn btn-primary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Upload Document
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
