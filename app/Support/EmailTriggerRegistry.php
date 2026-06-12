<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

class EmailTriggerRegistry
{
    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public static function all(): array
    {
        return config('email-triggers', []);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function forSlug(string $slug): array
    {
        return self::all()[$slug] ?? [];
    }

    /**
     * @param  array<string, mixed>  $trigger
     */
    public static function url(array $trigger): ?string
    {
        if (!empty($trigger['route']) && Route::has($trigger['route'])) {
            try {
                return route($trigger['route']);
            } catch (\Throwable) {
                // Route may require parameters — fall back to path.
            }
        }

        if (!empty($trigger['path'])) {
            return url($trigger['path']);
        }

        return null;
    }
}
