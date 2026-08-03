@extends('admin.layouts.app')

@section('title', 'Price book | MENetZero')
@section('page-title', 'Commercial price book')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif

    <p class="text-sm text-gray-600 mb-6 max-w-3xl">
        Admin-only list prices (excl. 5% VAT). Used by package / entity request quote suggestions.
        Not shown on public Explore Free. Preferential ≥10 onboarded stays sales-only.
    </p>

    <form action="{{ route('admin.price-book.update') }}" method="POST" class="space-y-8">
        @csrf
        @method('PUT')

        @php
            $titles = [
                'company_package' => 'Company packages (xlsx §5)',
                'consultant_rate' => 'Consultant Plan rates (§6.2)',
                'extra' => 'Extras (usually custom)',
            ];
        @endphp

        @foreach($titles as $category => $title)
            @php $rows = $entries[$category] ?? collect(); @endphp
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-900">{{ $title }}</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs text-gray-500">
                            <tr>
                                <th class="px-4 py-2">Code</th>
                                <th class="px-4 py-2">Label</th>
                                <th class="px-4 py-2">AED / yr</th>
                                <th class="px-4 py-2">Custom</th>
                                <th class="px-4 py-2">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($rows as $i => $entry)
                                <tr>
                                    <td class="px-4 py-2 text-xs text-gray-400 whitespace-nowrap">
                                        {{ $entry->code }}
                                        <input type="hidden" name="entries[{{ $entry->id }}][id]" value="{{ $entry->id }}">
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="text" name="entries[{{ $entry->id }}][label]" value="{{ old('entries.'.$entry->id.'.label', $entry->label) }}" class="border border-gray-300 rounded text-xs px-2 py-1 w-full min-w-[10rem]" required>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" step="0.01" name="entries[{{ $entry->id }}][amount_aed]" value="{{ old('entries.'.$entry->id.'.amount_aed', $entry->is_custom ? '' : $entry->amount_aed) }}" class="border border-gray-300 rounded text-xs px-2 py-1 w-28" placeholder="—">
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <input type="checkbox" name="entries[{{ $entry->id }}][is_custom]" value="1" class="rounded" @checked(old('entries.'.$entry->id.'.is_custom', $entry->is_custom))>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="text" name="entries[{{ $entry->id }}][notes]" value="{{ old('entries.'.$entry->id.'.notes', $entry->notes) }}" class="border border-gray-300 rounded text-xs px-2 py-1 w-full min-w-[14rem]">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">No rows — run migrations.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800">
            Save price book
        </button>
    </form>
@endsection
