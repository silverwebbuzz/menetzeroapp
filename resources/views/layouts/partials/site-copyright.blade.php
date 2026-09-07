@php($variant = $variant ?? 'default')
{{-- The marketing footer already carries a Legal column, so it opts out via
     ['policyLinks' => false] rather than printing the same three links twice. --}}
@php($policyLinks = $policyLinks ?? true)
{{-- Terms, Privacy and Refunds must be reachable from inside the app, not only
     from the marketing footer: a signed-in customer about to buy is exactly who
     needs them, and payment gateways expect them linked from the paying
     surfaces. These are public routes, so they work signed in or out. --}}
<p class="site-copyright site-copyright--{{ $variant }}">
    &copy; {{ date('Y') }} MeNetZero &mdash; a brand of
    <a href="https://www.silverwebbuzz.com" target="_blank" rel="noopener noreferrer">Silver Webbuzz PVT Ltd</a>
</p>
@if($policyLinks)
<p class="site-policy-links site-policy-links--{{ $variant }}">
    <a href="{{ route('terms') }}">Terms &amp; Conditions</a>
    <span aria-hidden="true">&middot;</span>
    <a href="{{ route('privacy') }}">Privacy Policy</a>
    <span aria-hidden="true">&middot;</span>
    <a href="{{ route('refunds') }}">Refunds &amp; Cancellations</a>
</p>
@endif
