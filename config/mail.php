<?php

return [
    'default' => env('MAIL_MAILER', 'smtp'),

    'mailers' => [
        'smtp' => [
            'transport'    => 'smtp',
            'host'         => env('MAIL_HOST', 'smtp.gmail.com'),
            'port'         => env('MAIL_PORT', 587),
            'encryption'   => env('MAIL_ENCRYPTION', 'tls'),
            'username'     => env('MAIL_USERNAME'),
            'password'     => env('MAIL_PASSWORD'),
            'timeout'      => null,
            'local_domain' => env(
                'MAIL_EHLO_DOMAIN',
                parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)
            ),
        ],

        'log' => ['transport' => 'log'],

        'failover' => [
            'transport'   => 'failover',
            'mailers'     => ['smtp', 'log'],
            'retry_after' => 60,
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'ems.barabd@gmail.com'),
        'name'    => env('MAIL_FROM_NAME', 'Education Management System'),
    ],
];
