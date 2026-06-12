<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'mailer',
        'reply_to',
        'subject',
        'body_html',
        'body_text',
        'placeholders',
        'is_active',
    ];

    protected $casts = [
        'placeholders' => 'array',
        'is_active' => 'boolean',
    ];

    public function renderSubject(array $variables = []): string
    {
        return $this->replacePlaceholders($this->subject, $variables);
    }

    public function renderBodyHtml(array $variables = []): string
    {
        return $this->replacePlaceholders($this->body_html, $variables);
    }

    public function renderBodyText(array $variables = []): ?string
    {
        if (!$this->body_text) {
            return null;
        }

        return $this->replacePlaceholders($this->body_text, $variables);
    }

    protected function replacePlaceholders(string $content, array $variables): string
    {
        $merged = array_merge(EmailTemplate::globalVariables(), $variables);

        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function (array $matches) use ($merged) {
            $key = $matches[1];

            return array_key_exists($key, $merged) ? (string) $merged[$key] : $matches[0];
        }, $content) ?? $content;
    }

    public static function globalVariables(): array
    {
        return [
            'app_name' => config('app.name', 'MeNetZero'),
            'app_url' => config('app.url', url('/')),
            'year' => (string) date('Y'),
            'hello_email' => config('mail.addresses.hello.address', 'hello@menetzero.com'),
            'help_email' => config('mail.addresses.help.address', 'help@menetzero.com'),
            'noreply_email' => config('mail.addresses.noreply.address', 'noreply@menetzero.com'),
        ];
    }
}
