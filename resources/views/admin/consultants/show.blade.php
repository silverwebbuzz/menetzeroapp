@extends('admin.layouts.app')

@section('title', $consultant->company_name . ' | Consultants')
@section('page-title', 'Review consultant')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif

    <div class="mb-4">
        <a href="{{ route('admin.consultants.index') }}" class="text-sm text-brand hover:underline">&larr; All consultants</a>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">{{ $consultant->company_name }}</h2>
                        <p class="text-sm text-gray-600">{{ $consultant->name }} · {{ $consultant->email }}</p>
                        @if($consultant->phone)<p class="text-sm text-gray-600">{{ $consultant->phone }}</p>@endif
                    </div>
                    <span class="px-2 py-1 rounded-full text-xs bg-gray-100">{{ $consultant->statusLabel() }}</span>
                </div>

                @if($consultant->bio)
                    <p class="mt-4 text-sm text-gray-700 whitespace-pre-line">{{ $consultant->bio }}</p>
                @endif

                <dl class="grid sm:grid-cols-2 gap-4 mt-4 text-sm">
                    <div><dt class="text-gray-500">Trade license</dt><dd>{{ $consultant->trade_license_number ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Experience</dt><dd>{{ $consultant->experience_years ? $consultant->experience_years . ' years' : '—' }}</dd></div>
                    <div><dt class="text-gray-500">Emirates</dt><dd>{{ implode(', ', $consultant->emirateLabels()) ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">Specialties</dt><dd>{{ implode(', ', $consultant->specialtyLabels()) ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">MOCCAE exp.</dt><dd>{{ $consultant->has_moccae_experience ? 'Yes' : 'No' }}</dd></div>
                    <div><dt class="text-gray-500">Submitted</dt><dd>{{ $consultant->submitted_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                </dl>

                @if($consultant->rejection_reason)
                    <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-800">
                        <strong>Rejection reason:</strong> {{ $consultant->rejection_reason }}
                    </div>
                @endif
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Documents</h3>
                @forelse($consultant->documents as $doc)
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                        <div>
                            <div class="font-medium text-sm">{{ $doc->typeLabel() }}</div>
                            <div class="text-xs text-gray-500">{{ $doc->original_filename }}</div>
                        </div>
                        <a href="{{ route('admin.consultants.documents.download', [$consultant, $doc->id]) }}" class="text-sm text-brand hover:underline">Download</a>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No documents uploaded.</p>
                @endforelse
            </div>

            @if($consultant->introRequests->isNotEmpty())
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Recent intro requests</h3>
                    @foreach($consultant->introRequests->take(5) as $req)
                        <div class="py-2 border-b border-gray-100 text-sm">
                            <strong>{{ $req->company?->name }}</strong> — {{ $req->packLabel() }}
                            <span class="text-gray-500">({{ $req->created_at->format('d M Y') }})</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="space-y-4">
            @if($consultant->status === 'pending_review')
                <div class="bg-white shadow rounded-lg p-5 space-y-3">
                    <form action="{{ route('admin.consultants.approve', $consultant) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium">Approve &amp; list</button>
                    </form>
                    <form action="{{ route('admin.consultants.reject', $consultant) }}" method="POST" class="space-y-2">
                        @csrf
                        <textarea name="rejection_reason" rows="3" required placeholder="Rejection reason (sent to consultant)" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                        <button type="submit" class="w-full py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium">Reject</button>
                    </form>
                </div>
            @endif

            @if($consultant->status === 'approved')
                <div class="bg-white shadow rounded-lg p-5 space-y-3">
                    <form action="{{ route('admin.consultants.featured', $consultant) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">
                            {{ $consultant->is_featured ? 'Remove featured' : 'Mark featured' }}
                        </button>
                    </form>
                    <form action="{{ route('admin.consultants.suspend', $consultant) }}" method="POST" onsubmit="return confirm('Suspend this consultant?')">
                        @csrf
                        <button type="submit" class="w-full py-2 bg-red-100 text-red-700 rounded-lg text-sm">Suspend</button>
                    </form>
                </div>
            @endif

            <div class="bg-white shadow rounded-lg p-5">
                <h3 class="font-semibold text-gray-900 mb-3 text-sm">Admin notes</h3>
                <form action="{{ route('admin.consultants.notes', $consultant) }}" method="POST">
                    @csrf @method('PUT')
                    <textarea name="admin_notes" rows="4" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ $consultant->admin_notes }}</textarea>
                    <button type="submit" class="mt-2 px-3 py-1.5 bg-brand text-white rounded text-sm">Save notes</button>
                </form>
            </div>

            <div class="bg-white shadow rounded-lg p-5 text-sm text-gray-600">
                <div>Orders: {{ $consultant->orders->count() }}</div>
                <div class="text-xs text-gray-400 mt-1">Escrow marketplace — C10</div>
            </div>
        </div>
    </div>
@endsection
