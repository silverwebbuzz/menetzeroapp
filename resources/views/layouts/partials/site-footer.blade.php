@php($variant = $variant ?? 'default')
<footer class="site-footer site-footer--{{ $variant }}">
    @include('layouts.partials.site-copyright', ['variant' => $variant])
</footer>
