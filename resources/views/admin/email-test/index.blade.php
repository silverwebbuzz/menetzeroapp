@extends('admin.layouts.app')

@section('title', 'Email Tester | MeNetZero')
@section('page-title', 'Email Tester')

@section('content')
    <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-900 px-4 py-3 rounded text-sm">
        Send a test message and inspect SMTP settings, transport errors, and delivery hints on this page.
        If mail still does not arrive after a green result, check spam, DNS (SPF/DKIM), and that the recipient mailbox exists.
    </div>

    @if(!empty($config['warnings']))
        <div class="mb-4 bg-amber-50 border border-amber-300 text-amber-950 px-4 py-3 rounded text-sm">
            <strong class="block mb-1">Configuration warnings</strong>
            <ul class="list-disc list-inside space-y-1">
                @foreach($config['warnings'] as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($result)
        <div class="mb-6 rounded-lg border {{ $result['ok'] ? 'bg-green-50 border-green-400' : 'bg-red-50 border-red-400' }} overflow-hidden">
            <div class="px-4 py-3 {{ $result['ok'] ? 'bg-green-100 border-b border-green-300' : 'bg-red-100 border-b border-red-300' }}">
                <h2 class="text-lg font-semibold {{ $result['ok'] ? 'text-green-900' : 'text-red-900' }}">
                    {{ $result['ok'] ? 'Success' : 'Failed' }}
                </h2>
                <p class="text-sm mt-1 {{ $result['ok'] ? 'text-green-800' : 'text-red-800' }}">{{ $result['summary'] }}</p>
            </div>

            <div class="px-4 py-4 space-y-4 text-sm">
                @if(!empty($result['transport_hint']))
                    <div class="rounded border border-gray-200 bg-white px-3 py-2">
                        <strong class="text-gray-700">Hint:</strong> {{ $result['transport_hint'] }}
                    </div>
                @endif

                @if(!$result['ok'])
                    <div>
                        <h3 class="font-medium text-red-900 mb-1">Error</h3>
                        @if($result['error_class'])
                            <p class="text-xs text-red-700 mb-1"><code>{{ $result['error_class'] }}</code></p>
                        @endif
                        <pre class="text-xs bg-red-950 text-red-50 p-3 rounded overflow-x-auto whitespace-pre-wrap">{{ $result['error'] }}</pre>
                    </div>

                    @if($result['error_trace'])
                        <details class="rounded border border-gray-200 bg-white">
                            <summary class="cursor-pointer px-3 py-2 font-medium text-gray-800">Stack trace</summary>
                            <pre class="text-xs p-3 overflow-x-auto whitespace-pre-wrap text-gray-700 border-t border-gray-200">{{ $result['error_trace'] }}</pre>
                        </details>
                    @endif
                @endif

                @if(!empty($result['details']))
                    <div>
                        <h3 class="font-medium text-gray-900 mb-2">Send details</h3>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-2 text-sm">
                            @foreach([
                                'mode' => 'Mode',
                                'mailbox' => 'Mailbox',
                                'slug' => 'Template slug',
                                'template_name' => 'Template',
                                'transport_mailer' => 'Laravel mailer',
                                'to' => 'To',
                                'subject' => 'Subject',
                                'duration_ms' => 'Duration (ms)',
                            ] as $key => $label)
                                @if(!empty($result['details'][$key]))
                                    <div>
                                        <dt class="text-gray-500">{{ $label }}</dt>
                                        <dd class="font-mono text-gray-900 break-all">{{ $result['details'][$key] }}</dd>
                                    </div>
                                @endif
                            @endforeach
                            @if(!empty($result['details']['from']))
                                <div>
                                    <dt class="text-gray-500">From</dt>
                                    <dd class="font-mono text-gray-900 break-all">
                                        {{ $result['details']['from']['name'] ?? '' }}
                                        &lt;{{ $result['details']['from']['address'] ?? '—' }}&gt;
                                    </dd>
                                </div>
                            @endif
                        </dl>

                        @if(!empty($result['details']['transport']))
                            @php $t = $result['details']['transport']; @endphp
                            <div class="mt-3 rounded border border-gray-200 bg-gray-50 p-3">
                                <h4 class="font-medium text-gray-800 mb-2">Transport used</h4>
                                <dl class="grid grid-cols-2 md:grid-cols-3 gap-2 text-xs font-mono">
                                    <div><dt class="text-gray-500">transport</dt><dd>{{ $t['transport'] ?? '—' }}</dd></div>
                                    <div><dt class="text-gray-500">host</dt><dd>{{ $t['host'] ?? '—' }}</dd></div>
                                    <div><dt class="text-gray-500">port</dt><dd>{{ $t['port'] ?? '—' }}</dd></div>
                                    <div><dt class="text-gray-500">scheme</dt><dd>{{ $t['scheme'] ?? '—' }}</dd></div>
                                    <div><dt class="text-gray-500">username</dt><dd>{{ $t['username'] ?? '—' }}</dd></div>
                                    <div><dt class="text-gray-500">password</dt><dd>{{ !empty($t['password_set']) ? '•••••••• (set)' : 'NOT SET' }}</dd></div>
                                    <div><dt class="text-gray-500">EHLO domain</dt><dd>{{ $t['local_domain'] ?? '—' }}</dd></div>
                                </dl>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Send test email</h2>

            <form method="POST" action="{{ route('admin.email-test.send') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Test type</label>
                    <select name="mode" id="test-mode" class="w-full rounded border-gray-300 shadow-sm text-sm" required>
                        <option value="raw" @selected(old('mode', 'raw') === 'raw')>Raw SMTP test (simple message)</option>
                        <option value="template" @selected(old('mode') === 'template')>Template test (uses DB template + sample data)</option>
                    </select>
                </div>

                <div id="mailbox-field">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Send from mailbox</label>
                    <select name="mailbox" class="w-full rounded border-gray-300 shadow-sm text-sm">
                        @foreach(['noreply' => 'noreply@ (automated)', 'help' => 'help@ (support)', 'hello' => 'hello@ (sales)'] as $key => $label)
                            <option value="{{ $key }}" @selected(old('mailbox', 'noreply') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="template-field" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Template</label>
                    <select name="template_slug" class="w-full rounded border-gray-300 shadow-sm text-sm">
                        @foreach($templates as $template)
                            <option value="{{ $template->slug }}" @selected(old('template_slug') === $template->slug)>
                                {{ $template->name }} ({{ $template->mailer }}@)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Recipient email</label>
                    <input type="email" name="to" value="{{ old('to', auth()->user()?->email) }}" required
                           class="w-full rounded border-gray-300 shadow-sm text-sm" placeholder="you@example.com">
                    @error('to')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded hover:bg-indigo-700">
                    Send test email
                </button>
            </form>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Current mail configuration</h2>
            <p class="text-sm text-gray-600 mb-3">
                Default mailer: <code class="bg-gray-100 px-1 rounded">{{ $config['default_mailer'] }}</code>
                · EHLO: <code class="bg-gray-100 px-1 rounded">{{ $config['ehlo_domain'] ?: '—' }}</code>
                · Alerts: <code class="bg-gray-100 px-1 rounded">{{ $config['alert_to'] ?: '—' }}</code>
            </p>

            @foreach($config['mailboxes'] as $key => $box)
                @php $t = $box['transport']; @endphp
                <div class="mb-4 last:mb-0 border border-gray-200 rounded p-3 text-sm">
                    <h3 class="font-medium text-gray-900 mb-1">{{ strtoupper($key) }} — {{ $box['address'] }}</h3>
                    <p class="text-xs text-gray-500 mb-2">Mailer: {{ $box['transport_mailer'] }}</p>
                    <dl class="grid grid-cols-2 gap-x-3 gap-y-1 text-xs font-mono">
                        <div><dt class="text-gray-500">host</dt><dd class="break-all">{{ $t['host'] }}</dd></div>
                        <div><dt class="text-gray-500">port</dt><dd>{{ $t['port'] }}</dd></div>
                        <div><dt class="text-gray-500">scheme</dt><dd>{{ $t['scheme'] }}</dd></div>
                        <div><dt class="text-gray-500">username</dt><dd class="break-all">{{ $t['username'] }}</dd></div>
                        <div><dt class="text-gray-500">password</dt><dd>{{ $t['password_set'] ? 'set' : 'NOT SET' }}</dd></div>
                        <div><dt class="text-gray-500">EHLO</dt><dd class="break-all">{{ $t['local_domain'] }}</dd></div>
                    </dl>
                </div>
            @endforeach

            <p class="text-xs text-gray-500 mt-4">
                After changing <code>.env</code>, run <code>php artisan config:clear</code> on the server.
            </p>
        </div>
    </div>

    <script>
        (function () {
            const mode = document.getElementById('test-mode');
            const mailboxField = document.getElementById('mailbox-field');
            const templateField = document.getElementById('template-field');

            function syncFields() {
                const isRaw = mode.value === 'raw';
                mailboxField.classList.toggle('hidden', !isRaw);
                templateField.classList.toggle('hidden', isRaw);
            }

            mode.addEventListener('change', syncFields);
            syncFields();
        })();
    </script>
@endsection
