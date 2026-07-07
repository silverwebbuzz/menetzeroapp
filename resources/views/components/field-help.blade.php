@props([
    'framework' => null,
    'section' => '',
    'field' => null,
    'key' => null,
])

@php
    if ($key !== null) {
        $text = \App\Support\FieldHelp::line($key);
    } elseif ($field !== null) {
        $text = field_help($framework, $section, $field);
    } else {
        $text = field_help_section($framework, $section);
    }
@endphp

@if($text)
    <p {{ $attributes->merge(['class' => 'form-help-text']) }}>{{ $text }}</p>
@endif
