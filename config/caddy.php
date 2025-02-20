<?php

return [
    // Enable autossl for tracking domain
    'autossl' => env('CADDY_AUTOSSL', false),

    // Caddy server IP address (for pointing an A record to)
    'server_hostname' => env('CADDY_SERVER_HOSTNAME'),

    // Directory containing configuration file
    'config_dir' => '/etc/caddy/autossl/',

    // Admin email address used to obtain an SSL cert
    'admin_email_address' => env('CADDY_ADMIN_EMAIL_ADDRESS'),

    // Reverse proxy, serving https request
    'reverse_proxy' => 'localhost:8080',
];
