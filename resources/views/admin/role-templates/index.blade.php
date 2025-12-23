@extends('admin.layouts.app')

@section('title', 'Role Templates | MENetZero')
@section('page-title', 'Role Templates')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-medium text-gray-900">Role Templates</h2>
            <a href="{{ route('admin.role-templates.create') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                + New Template
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Code</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">System</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Active</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Sort Order</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($templates as $template)
                        <tr>
                            <td class="px-4 py-2 text-gray-900">{{ $template->template_code }}</td>
                            <td class="px-4 py-2 text-gray-900">{{ $template->template_name }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ Str::limit($template->description ?? 'N/A', 50) }}</td>
                            <td class="px-4 py-2 text-gray-500">
                                <span class="px-2 py-1 text-xs rounded-full {{ $template->is_system_template ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $template->is_system_template ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-gray-500">
                                <span class="px-2 py-1 text-xs rounded-full {{ $template->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $template->is_active ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-gray-500">{{ $template->sort_order }}</td>
                            <td class="px-4 py-2">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('admin.role-templates.edit', $template->id) }}" class="text-purple-600 hover:text-purple-900 text-sm">Edit</a>
                                    @if(!$template->is_system_template)
                                        <form action="{{ route('admin.role-templates.destroy', $template->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this role template?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 text-sm">Delete</button>
                                        </form>
                                    @else
                                        <span class="text-gray-400 text-sm">System</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-4 text-center text-gray-500">
                                No role templates defined. <a href="{{ route('admin.role-templates.create') }}" class="text-purple-600 hover:underline">Create one</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
