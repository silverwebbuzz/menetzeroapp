@extends('admin.layouts.app')

@section('title', 'Package Assignments | MENetZero')
@section('page-title', 'Admin Package Assignments')

@section('content')
    <p class="mb-4 text-sm text-gray-500">Audit log of every package/plan an admin assigned to a client company or consultant agency org at no charge.</p>

    <div class="bg-white shadow rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Date</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Target</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Type</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Plan</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Term</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Approved by</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Status</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Note</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($assignments as $a)
                    <tr>
                        <td class="px-4 py-2 text-xs text-gray-500">{{ $a->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-2">{{ optional($a->company)->name ?? 'Company #'.$a->company_id }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $a->target_type === 'consultant' ? 'bg-indigo-100 text-indigo-700' : 'bg-purple-100 text-purple-700' }}">{{ $a->target_type }}</span>
                        </td>
                        <td class="px-4 py-2">{{ optional($a->plan)->plan_name ?? 'Plan #'.$a->subscription_plan_id }}</td>
                        <td class="px-4 py-2 text-xs text-gray-600">{{ $a->contract_year ? 'Year '.$a->contract_year : $a->duration_months.' months' }}</td>
                        <td class="px-4 py-2 text-xs text-gray-600">{{ optional($a->admin)->name ?? ($a->admin_id ? 'admin #'.$a->admin_id : '—') }}</td>
                        <td class="px-4 py-2"><span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">{{ $a->status }}</span></td>
                        <td class="px-4 py-2 text-xs text-gray-600">{{ $a->note }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-6 text-center text-gray-500">No admin package assignments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $assignments->links() }}</div>
@endsection
