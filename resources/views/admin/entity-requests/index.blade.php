@extends('admin.layouts.app')

@section('title', 'Client requests | MENetZero')
@section('page-title', 'Consultant client requests')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
    @endif

    <p class="text-sm text-gray-600 mb-4">
        Workflow: Review → Suggest/edit band quote (price book) → Mark paid → Activate (grants capacity for the requested managed clients).
        Preferential ≥10 onboarded is sales-only (not auto-applied).
    </p>

    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        <a href="{{ route('admin.entity-requests.index') }}" class="px-3 py-1 rounded {{ !$status ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700' }}">All</a>
        @foreach(['new','contacted','quoted','activated','closed'] as $s)
            <a href="{{ route('admin.entity-requests.index', ['status' => $s]) }}" class="px-3 py-1 rounded {{ $status === $s ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700' }}">{{ ucfirst($s) }}</a>
        @endforeach
    </div>

    <div class="space-y-4">
        @forelse($requests as $req)
            @php $sug = $suggestions[$req->id] ?? null; @endphp
            <div class="bg-white shadow rounded-lg p-4 text-sm">
                <div class="flex flex-wrap justify-between gap-3 mb-3">
                    <div>
                        <div class="font-semibold text-gray-900">{{ $req->consultantCompany?->name ?? '—' }} <span class="text-xs text-gray-400">#{{ $req->consultant_company_id }}</span></div>
                        <div class="text-xs text-gray-500">{{ $req->user?->name }} · {{ $req->user?->email }} · {{ $req->created_at->format('d M Y H:i') }}</div>
                    </div>
                    <div class="text-right">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100">{{ ucfirst($req->status) }}</span>
                        @if($req->paid_at)
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Paid {{ $req->paid_at->format('d M') }}</span>
                        @endif
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <div class="text-xs text-gray-500">Requested</div>
                        <div class="font-medium">{{ $req->entity_count }} managed clients · {{ $req->wants_enterprise ? 'Enterprise' : 'Standard' }}</div>
                        <div class="text-xs text-gray-500 mt-1">
                            @if($req->needs_sites_over_5)&gt;5 sites flagged · @endif
                            {{ $req->message ? \Illuminate\Support\Str::limit($req->message, 140) : 'No message' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Suggested (§6.2)</div>
                        @if($sug)
                            <div class="font-medium">
                                @if($sug['custom'])
                                    Custom
                                @else
                                    AED {{ number_format($sug['amount_aed'], 0) }} / yr
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 mt-1">{{ $sug['breakdown'] }}</div>
                            <div class="text-xs text-gray-400 mt-1">Activate pack → {{ $sug['suggested_pack_code'] ?? '—' }} (+extra clients if needed)</div>
                        @endif
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Saved quote</div>
                        <div class="font-medium">
                            @if($req->quote_amount_aed !== null)
                                AED {{ number_format($req->quote_amount_aed, 0) }}
                            @else
                                —
                            @endif
                        </div>
                        <div class="text-xs text-gray-500 mt-1">{{ $req->quote_breakdown ?: '—' }}</div>
                    </div>
                </div>

                @if($req->status !== 'activated' && $req->status !== 'closed')
                    <div class="border-t border-gray-100 pt-3 grid lg:grid-cols-2 gap-4">
                        <form action="{{ route('admin.entity-requests.quote', $req) }}" method="POST" class="space-y-2">
                            @csrf
                            <div class="font-medium text-gray-800">Quote</div>
                            <div class="flex flex-wrap gap-2 items-end">
                                <div>
                                    <label class="text-xs text-gray-500">AED / yr excl. VAT</label>
                                    <input type="number" step="0.01" name="quote_amount_aed" value="{{ old('quote_amount_aed', $req->quote_amount_aed ?? $sug['amount_aed'] ?? '') }}" class="border border-gray-300 rounded text-xs px-2 py-1 w-32">
                                </div>
                                <label class="text-xs flex items-center gap-1">
                                    <input type="checkbox" name="use_suggestion" value="1" class="rounded"> Apply band suggestion
                                </label>
                            </div>
                            <textarea name="quote_breakdown" rows="2" class="border border-gray-300 rounded text-xs px-2 py-1 w-full" placeholder="Breakdown notes">{{ old('quote_breakdown', $req->quote_breakdown ?? ($sug['breakdown'] ?? '')) }}</textarea>
                            <button type="submit" class="text-xs px-3 py-1.5 bg-gray-900 text-white rounded">Save quote</button>
                        </form>

                        <div class="space-y-2">
                            <div class="font-medium text-gray-800">Offline payment &amp; activate</div>
                            <div class="flex flex-wrap gap-2">
                                <form action="{{ route('admin.entity-requests.mark-paid', $req) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-xs px-3 py-1.5 border border-green-600 text-green-700 rounded hover:bg-green-50">Mark paid</button>
                                </form>
                                <form action="{{ route('admin.entity-requests.activate', $req) }}" method="POST" onsubmit="return confirm('Grant capacity for {{ $req->entity_count }} managed clients?')">
                                    @csrf
                                    <button type="submit" class="text-xs px-3 py-1.5 bg-orange-600 text-white rounded hover:bg-orange-700">Activate</button>
                                </form>
                            </div>
                            <p class="text-xs text-gray-500">Uses nearest capacity pack (+ extras) until Phase 8. Standard entitlements apply to paid managed clients.</p>
                        </div>
                    </div>
                @endif

                <form action="{{ route('admin.entity-requests.update', $req) }}" method="POST" class="mt-3 flex flex-wrap gap-2 items-end border-t border-gray-100 pt-3">
                    @csrf @method('PUT')
                    <div>
                        <label class="text-xs text-gray-500">Status</label>
                        <select name="status" class="border border-gray-300 rounded text-xs px-2 py-1">
                            @foreach(['new','contacted','quoted','closed'] as $s)
                                <option value="{{ $s }}" @selected($req->status === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                            @if($req->status === 'activated')
                                <option value="activated" selected>Activated</option>
                            @endif
                        </select>
                    </div>
                    <div class="flex-1 min-w-[12rem]">
                        <label class="text-xs text-gray-500">Admin notes</label>
                        <input type="text" name="admin_notes" value="{{ $req->admin_notes }}" class="border border-gray-300 rounded text-xs px-2 py-1 w-full">
                    </div>
                    <button type="submit" class="text-xs px-2 py-1 bg-gray-200 rounded">Save notes</button>
                </form>
            </div>
        @empty
            <div class="bg-white shadow rounded-lg px-4 py-8 text-center text-gray-500">No client requests yet.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>
@endsection
