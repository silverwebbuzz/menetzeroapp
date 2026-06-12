@props(['points' => []])

@php
    $points = array_values(is_array($points) ? $points : []);
    if (count($points) === 0) {
        $points = [0, 0];
    }
    if (count($points) === 1) {
        $points[] = $points[0];
    }

    $max = max($points) ?: 1;
    $min = min($points);
    $range = max($max - $min, 0.001);
    $width = 72;
    $height = 28;
    $coords = [];
    $count = count($points);

    foreach ($points as $index => $value) {
        $x = $count > 1 ? ($index / ($count - 1)) * $width : $width / 2;
        $y = $height - (($value - $min) / $range) * ($height - 4) - 2;
        $coords[] = round($x, 1) . ',' . round($y, 1);
    }

    $polyline = implode(' ', $coords);
@endphp

<svg {{ $attributes->merge(['class' => 'ent-sparkline']) }} viewBox="0 0 72 28" aria-hidden="true">
    <polyline points="{{ $polyline }}" />
</svg>
