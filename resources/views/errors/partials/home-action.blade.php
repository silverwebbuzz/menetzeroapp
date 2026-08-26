{{--
    Picks a safe destination for whichever guard is signed in.

    Uses plain Auth::guard()->check() rather than the guard directives, so the
    markup is unambiguous, and url() rather than route() for the consultant and
    admin portals because neither has a named landing route — a bad route() call
    would throw while rendering the error page itself.

    Do not write Blade directive names in this comment: Blade compiles directives
    before stripping comments, so a name here is counted by the compiler. Naming
    them here previously left an unclosed guard directive that would have made
    EVERY error page fatal (redesign.md section 31.9).
--}}
@php
    $isClient     = \Illuminate\Support\Facades\Auth::guard('web')->check();
    $isConsultant = \Illuminate\Support\Facades\Auth::guard('consultant')->check();
    $isAdmin      = \Illuminate\Support\Facades\Auth::guard('admin')->check();
@endphp

@if ($isClient)
    <a class="err-btn err-btn--primary" href="{{ route('client.dashboard') }}">Back to dashboard</a>
@elseif ($isConsultant)
    <a class="err-btn err-btn--primary" href="{{ url('/consultant') }}">Back to agency hub</a>
@elseif ($isAdmin)
    <a class="err-btn err-btn--primary" href="{{ url('/admin') }}">Back to admin</a>
@else
    <a class="err-btn err-btn--primary" href="{{ url('/') }}">Go to MENetZero</a>
@endif
