@props([
    'framework' => null,
    'section' => '',
    'field' => null,
])

@php
    $text = $field !== null
        ? field_help($framework, $section, $field)
        : field_help_section($framework, $section);
@endphp

@if($text)
    <p {{ $attributes->merge(['class' => 'form-help-text']) }}>{{ $text }}</p>
@endif
