<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | MeNetZero addresses
    |--------------------------------------------------------------------------
    |
    | hello@ — sales, demos, partnerships
    | help@  — support, billing help (Reply-To on automated mail)
    | noreply@ — welcome, verification, password, subscriptions, invoices, alerts
    |
    */

    'addresses' => [
        'hello' => [
            'address' => env('MAIL_HELLO_ADDRESS', 'hello@menetzero.com'),
            'name' => env('MAIL_HELLO_NAME', 'MeNetZero'),
        ],
        'help' => [
            'address' => env('MAIL_HELP_ADDRESS', 'help@menetzero.com'),
            'name' => env('MAIL_HELP_NAME', 'MeNetZero Support'),
        ],
        'noreply' => [
            'address' => env('MAIL_NOREPLY_ADDRESS', 'noreply@menetzero.com'),
            'name' => env('MAIL_NOREPLY_NAME', 'MeNetZero'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => mail_smtp_scheme(),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'smtp_hello' => [
            'transport' => 'smtp',
            'scheme' => mail_smtp_scheme(env('MAIL_HELLO_SCHEME')),
            'host' => env('MAIL_HELLO_HOST', env('MAIL_HOST', '127.0.0.1')),
            'port' => env('MAIL_HELLO_PORT', env('MAIL_PORT', 587)),
            'username' => env('MAIL_HELLO_USERNAME', env('MAIL_USERNAME')),
            'password' => env('MAIL_HELLO_PASSWORD', env('MAIL_PASSWORD')),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'smtp_help' => [
            'transport' => 'smtp',
            'scheme' => mail_smtp_scheme(env('MAIL_HELP_SCHEME')),
            'host' => env('MAIL_HELP_HOST', env('MAIL_HOST', '127.0.0.1')),
            'port' => env('MAIL_HELP_PORT', env('MAIL_PORT', 587)),
            'username' => env('MAIL_HELP_USERNAME', env('MAIL_USERNAME')),
            'password' => env('MAIL_HELP_PASSWORD', env('MAIL_PASSWORD')),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'smtp_noreply' => [
            'transport' => 'smtp',
            'scheme' => mail_smtp_scheme(env('MAIL_NOREPLY_SCHEME')),
            'host' => env('MAIL_NOREPLY_HOST', env('MAIL_HOST', '127.0.0.1')),
            'port' => env('MAIL_NOREPLY_PORT', env('MAIL_PORT', 587)),
            'username' => env('MAIL_NOREPLY_USERNAME', env('MAIL_USERNAME')),
            'password' => env('MAIL_NOREPLY_PASSWORD', env('MAIL_PASSWORD')),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', env('MAIL_NOREPLY_ADDRESS', 'noreply@menetzero.com')),
        'name' => env('MAIL_FROM_NAME', env('MAIL_NOREPLY_NAME', 'MeNetZero')),
    ],

];
