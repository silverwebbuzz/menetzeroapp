@extends('admin.layouts.app')

@section('title', 'Email Templates | MeNetZero')
@section('page-title', 'Email Templates')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif

    <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-900 px-4 py-3 rounded text-sm flex flex-wrap items-center justify-between gap-2">
        <span>
            <strong>Addresses:</strong>
            hello — {{ config('mail.addresses.hello.address') }} (sales)
            · help — {{ config('mail.addresses.help.address') }} (support)
            · noreply — {{ config('mail.addresses.noreply.address') }} (automated).
            SMTP credentials are set in <code class="text-xs bg-white px-1 rounded">.env</code>.
        </span>
        <a href="{{ route('admin.email-test.index') }}" class="text-indigo-700 font-medium hover:underline whitespace-nowrap">Open email tester →</a>
    </div>

    @foreach(['noreply' => 'Automated (noreply@)', 'help' => 'Support (help@)', 'hello' => 'Sales (hello@)'] as $mailer => $label)
        @php $group = $templates->get($mailer, collect()); @endphp
        @if($group->isNotEmpty())
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">{{ $label }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Template</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Subject</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Active</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Updated</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($group as $template)
                                <tr>
                                    <td class="px-4 py-2">
                                        <div class="font-medium text-gray-900">{{ $template->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $template->slug }}</div>
                                    </td>
                                    <td class="px-4 py-2 max-w-xs truncate">{{ $template->subject }}</td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-0.5 rounded-full text-xs {{ $template->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $template->is_active ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-xs text-gray-500">{{ $template->updated_at?->diffForHumans() }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <a href="{{ route('admin.email-templates.edit', $template) }}" class="text-purple-700 hover:underline">Edit</a>
                                        ·
                                        <a href="{{ route('admin.email-templates.preview', $template) }}" class="text-purple-700 hover:underline" target="_blank">Preview</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endforeach
@endsection
