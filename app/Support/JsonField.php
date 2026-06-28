<?php

namespace App\Support;

class JsonField
{
    /**
     * Normalize a JSON DB column that may already be cast to an array.
     */
    public static function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
