@extends('admin.layouts.app')

@section('title', 'Role Templates | MENetZero')
@section('page-title', 'Role Templates')

@section('content')
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-medium text-gray-900">Role Templates</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Code</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">System</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Active</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Sort Order</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($templates as $template)
                        <tr>
                            <td class="px-4 py-2 text-gray-900">
                                {{ $template->template_code }}
                            </td>
                            <td class="px-4 py-2 text-gray-900">
                                {{ $template->template_name }}
                            </td>
                            <td class="px-4 py-2 text-gray-500">
                                {{ $template->is_system_template ? 'Yes' : 'No' }}
                            </td>
                            <td class="px-4 py-2 text-gray-500">
                                {{ $template->is_active ? 'Yes' : 'No' }}
                            </td>
                            <td class="px-4 py-2 text-gray-500">
                                {{ $template->sort_order }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-center text-gray-500">
                                No role templates defined.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection



