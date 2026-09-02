@extends('admin.layouts.app')

@section('title', $consultant->company_name . ' | Consultants')
@section('page-title', 'Review consultant')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
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

            <div class="bg-white shadow rounded-lg p-5 border-l-4 border-indigo-500">
                <h3 class="font-semibold text-gray-900 mb-1 text-sm">Assign agency pack</h3>
                <p class="text-xs text-gray-500 mb-3">Admin-approved grant, no payment. Links the consultant to an agency org if needed.</p>

                @if($activeSubscription)
                    <div class="mb-3 text-xs text-gray-600 bg-gray-50 rounded p-2">
                        Current: <span class="font-medium">{{ optional($activeSubscription->plan)->plan_name ?? 'Unknown' }}</span>
                        · {{ $activeSubscription->slot_limit }} slots
                        · expires {{ optional($activeSubscription->expires_at)->format('Y-m-d') }}
                    </div>
                @endif

                @if($consultantPacks->isEmpty())
                    <p class="text-sm text-gray-500">No active consultant packs found.</p>
                @else
                <form action="{{ route('admin.consultants.assign-package', $consultant) }}" method="POST" class="space-y-2 text-sm" onsubmit="return confirm('Assign this consultant pack at no charge?')">
                    @csrf
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Pack</label>
                        <select name="plan_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            @foreach($consultantPacks as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->plan_name }} (AED {{ number_format($plan->price_annual, 0) }}/yr)</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Contract year</label>
                        <input type="number" name="contract_year" value="{{ now()->year }}" min="2024" max="2100" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Reason / approval note</label>
                        <input type="text" name="note" required placeholder="e.g. Launch partner" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium">Assign pack</button>
                </form>
                @endif

                @if($packageAssignments->isNotEmpty())
                    <div class="mt-4 border-t border-gray-100 pt-3">
                        <div class="text-xs font-medium text-gray-500 mb-1">Assignment history</div>
                        @foreach($packageAssignments as $assignment)
                            <div class="text-xs text-gray-600 py-1">
                                {{ optional($assignment->plan)->plan_name ?? 'Plan' }} · Year {{ $assignment->contract_year }} ·
                                {{ $assignment->created_at->format('Y-m-d') }}
                                @if($assignment->admin) · {{ $assignment->admin->name }}@endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

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

    {{-- DANGER ZONE. Deletes the consultant AND the agency company behind it:
         two rows describing one organisation. Typed-name confirmation, as on
         the company page -- a stray Enter key must not erase an org. --}}
    @php
        $agencyCompany = $consultant->agency_company_id
            ? \App\Models\Company::find($consultant->agency_company_id)
            : null;
        $deletionBlocker = $agencyCompany
            ? app(\App\Services\OrganisationDeletionService::class)->blockerFor($agencyCompany)
            : null;
    @endphp

    <div class="bg-white shadow rounded-lg border border-red-200 mt-8">
        <div class="px-5 py-4 border-b border-red-100">
            <h2 class="text-lg font-medium text-red-700">Danger zone</h2>
        </div>
        <div class="p-5">
            @if($deletionBlocker)
                <p class="text-sm text-gray-700">{{ $deletionBlocker }}</p>
            @else
                <p class="text-sm text-gray-700 mb-1">
                    Permanently delete <strong>{{ $consultant->name }}</strong>, its agency workspace
                    and everything belonging to it — documents, orders, subscriptions, users and invoices.
                </p>
                <p class="text-xs text-red-600 mb-4">This cannot be undone. There is no recovery short of a database backup.</p>

                <form method="POST" action="{{ route('admin.consultants.destroy', $consultant) }}"
                      class="flex flex-wrap items-end gap-3">
                    @csrf
                    @method('DELETE')
                    <div>
                        <label for="confirm_name" class="block text-xs text-gray-600 mb-1">
                            Type <span class="font-mono font-semibold">{{ $consultant->name }}</span> to confirm
                        </label>
                        <input type="text" name="confirm_name" id="confirm_name" autocomplete="off"
                               data-expected="{{ $consultant->name }}"
                               class="w-80 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <button type="submit" id="delete-consultant-btn" disabled
                            class="px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg disabled:opacity-40 disabled:cursor-not-allowed">
                        Delete permanently
                    </button>
                </form>

                <script>
                    (function () {
                        var input = document.getElementById('confirm_name');
                        var btn = document.getElementById('delete-consultant-btn');
                        if (!input || !btn) { return; }
                        input.addEventListener('input', function () {
                            btn.disabled = input.value.trim() !== input.dataset.expected.trim();
                        });
                    })();
                </script>
            @endif
        </div>
    </div>
@endsection
