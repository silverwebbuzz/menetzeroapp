@php
    $helpText = $field->resolvedHelpText($slug ?? null, $context ?? []);
@endphp
@if($helpText)
    <p class="form-help-text">{{ $helpText }}</p>
@endif
