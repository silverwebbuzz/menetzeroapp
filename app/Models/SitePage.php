<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SitePage extends Model
{
    protected $fillable = ['slug', 'title', 'body', 'is_published', 'sort_order'];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    /**
     * Render the body with {{placeholder}} tokens replaced by site settings.
     */
    public function renderedBody(): string
    {
        $settings = SiteSetting::allSettings();

        return preg_replace_callback('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', function ($m) use ($settings) {
            return e($settings[$m[1]] ?? '');
        }, (string) $this->body);
    }
}
