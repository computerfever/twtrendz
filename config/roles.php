<?php

// Default roles and permissions
return [
    'organization_admin' => [
        'campaign.full_access',
        'list.full_access',
        'automation.full_access',
        'template.full_access',
        'form.full_access',
        'sending_server.full_access',
        'sending_domain.full_access',
        'account.full_access',
    ],

    'organization_readonly' => [
        'campaign.read_only',
        'list.read_only',
        'automation.read_only',
        'template.read_only',
        'form.read_only',
        'sending_server.read_only',
        'sending_domain.read_only',
        'account.read_only',
    ]
];
