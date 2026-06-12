<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('emails.templates', []) as $slug => $definition) {
            EmailTemplate::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $definition['name'] ?? $slug,
                    'description' => $definition['description'] ?? null,
                    'mailer' => $definition['mailer'] ?? 'noreply',
                    'reply_to' => $definition['reply_to'] ?? 'help',
                    'subject' => $definition['subject'] ?? '',
                    'body_html' => $definition['body'] ?? '',
                    'placeholders' => $definition['placeholders'] ?? [],
                    'is_active' => true,
                ]
            );
        }
    }
}
