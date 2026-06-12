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

    <div class="mb-6 bg-white border border-gray-200 rounded-lg p-4 text-sm">
        <h3 class="font-semibold text-gray-900 mb-2">Where sales &amp; support inquiries come from today</h3>
        <ul class="space-y-2 text-gray-700">
            <li>
                <strong>Sales (hello@):</strong>
                <a href="{{ route('contact') }}" target="_blank" rel="noopener" class="text-indigo-700 hover:underline">Public contact page</a>
                — users click the sales mailto link.
                Edit the displayed address in
                <a href="{{ route('admin.site-content.index') }}" class="text-indigo-700 hover:underline">Site Content</a>
                (<code class="text-xs bg-gray-100 px-1 rounded">sales_email</code>).
            </li>
            <li>
                <strong>Support (help@):</strong>
                same
                <a href="{{ route('contact') }}" target="_blank" rel="noopener" class="text-indigo-700 hover:underline">contact page</a>
                — support mailto link
                (<code class="text-xs bg-gray-100 px-1 rounded">support_email</code> in Site Content).
            </li>
            <li class="text-gray-500 text-xs">
                Auto-reply templates <em>Contact — sales acknowledgement</em> and <em>Contact — support acknowledgement</em> are ready but not yet connected to a submit form — see their “Triggered from” column below.
            </li>
        </ul>
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
                                <th class="px-4 py-2 text-left font-medium text-gray-500 min-w-[220px]">Triggered from</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Subject</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Active</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Updated</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($group as $template)
                                <tr class="align-top">
                                    <td class="px-4 py-2">
                                        <div class="font-medium text-gray-900">{{ $template->name }}</div>
                                        <div class="text-xs text-gray-500 font-mono">{{ $template->slug }}</div>
                                    </td>
                                    <td class="px-4 py-2 max-w-sm">
                                        @include('admin.email-templates.partials.triggers', [
                                            'triggers' => $triggersBySlug[$template->slug] ?? [],
                                        ])
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
