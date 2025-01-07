<?php

return [
    'site_name' => [
        'value' => env('APP_NAME', '© Acelle Mail'),
        'type' => 'string',
    ],
    'site_keyword' => [
        'value' => 'Email Marketing, Campaigns, Lists',
        'type' => 'string',
    ],
    'site_logo_light' => [
        'value' => '',
        'type' => 'image',
    ],
    'site_logo_dark' => [
        'value' => '',
        'type' => 'image',
    ],
    'site_favicon' => [
        'value' => '',
        'type' => 'image',
    ],
    'license' => [
        'value' => '',
        'type' => 'string',
    ],
    'license_type' => [
        'value' => '',
        'type' => 'string',
    ],
    'license_status' => [
        'value' => '',
        'type' => 'string',
    ],
    'license_supported_until' => [
        'value' => '',
        'type' => 'string',
    ],
    'site_online' => [
        'value' => 'yes',
        'type' => 'boolean',
    ],
    'site_offline_message' => [
        'value' => 'Application currently offline. We will come back soon!',
        'type' => 'text',
    ],
    'site_description' => [
        'value' => 'Makes it easy for you to create, send, and optimize your email marketing campaigns.',
        'type' => 'text',
    ],
    'default_language' => [
        'value' => 'en',
        'type' => 'string',
    ],
    'frontend_scheme' => [
        'value' => 'default',
        'type' => 'enum',
        'options' => [
            'default',
            'blue',
            'green',
            'brown',
            'pink',
            'grey',
            'white',
        ],
    ],
    'backend_scheme' => [
        'value' => 'default',
        'type' => 'enum',
        'options' => [
            'default',
            'blue',
            'green',
            'brown',
            'pink',
            'grey',
            'white',
        ],
    ],
    'captcha_engine' => [
        'value' => 'recaptcha',
        'type' => 'string',
    ],
    'login_recaptcha' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
    'embedded_form_recaptcha' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
    'list_sign_up_captcha' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
    'enable_user_registration' => [
        'value' => 'yes',
        'type' => 'boolean',
    ],
    'registration_recaptcha' => [
        'value' => 'yes',
        'type' => 'boolean',
    ],
    'custom_script' => [
        'value' => '',
        'type' => 'text',
    ],
    'builder' => [
        'value' => 'both',
        'type' => 'enum',
        'options' => [
            'both',
            'pro',
            'classic',
        ],
    ],
    'import_subscribers_commitment' => [
        'value' => null,
        'type' => 'text',
    ],
    'url_unsubscribe' => [
        'value' => '',
        'type' => 'string',
    ],
    'url_open_track' => [
        'value' => '', // action('CampaignController@open', ["message_id" => trans("messages.MESSAGE_ID")]),
        'type' => 'string',
    ],
    'url_click_track' => [
        'value' => '', // action('CampaignController@click', ["message_id" => trans("messages.MESSAGE_ID"), "url" => trans("messages.URL")]),
        'type' => 'string',
    ],
    'url_delivery_handler' => [
        'value' => '', // action('DeliveryController@notify'),
        'type' => 'string',
    ],
    'url_update_profile' => [
        'value' => '',
        'type' => 'string',
    ],
    'url_web_view' => [
        'value' => '',
        'type' => 'string',
    ],
    'php_bin_path' => [
        'value' => '',
        'type' => 'string',
    ],
    'remote_job_token' => [
        'value' => '',
        'type' => 'string',
    ],
    'cronjob_last_execution' => [
        'value' => 0,
        'type' => 'string',
    ],
    'cronjob_min_interval' => [
        'value' => '15 minutes',
        'type' => 'string',
    ],
    'spf_record' => [
        'value' => null,
        'type' => 'string',
    ],
    'spf_host' => [
        'value' => null,
        'type' => 'string',
    ],
    'verification_hostname' => [
        'value' => 'emarketing',
        'type' => 'string',
    ],
    'dkim_selector' => [
        'value' => 'mailer',
        'type' => 'string',
    ],
    'allow_turning_off_dkim_signing' => [
        'value' => 'yes',
        'type' => 'boolean',
    ],
    'escape_dkim_dns_value' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
    'verify_subscriber_email' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
    'send_notification_email_for_list_subscription' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
    'aws_verification_server' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
    'geoip.engine' => [
        'value' => 'sqlite', # available values are sqlite|nekudo|mysql
        'type' => 'string',
    ],
    'geoip.enabled' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
    'geoip.last_message' => [
        'value' => null,
        'type' => 'string',
    ],
    'geoip.sqlite.dbname' => [
        'value' => 'storage/app/GeoLite2-City.mmdb',
        'type' => 'string',
    ],
    'geoip.sqlite.source_url' => [
        'value' => 'https://acellemail.s3.amazonaws.com/GeoLite2-City_20230404.mmdb',
        'type' => 'string',
    ],
    'geoip.sqlite.source_hash' => [
        'value' => '1b6368f0e80b1be2dd5be3606d25ac16',
        'type' => 'string',
    ],
    'delivery.sendmail' => [
        'value' => 'yes',
        'type' => 'boolean',
    ],
    'delivery.phpmail' => [
        'value' => 'yes',
        'type' => 'boolean',
    ],
    'subscription.expiring_period' => [
        'value' => '7',
        'type' => 'string',
    ],
    'subscription.auto_billing_period' => [
        'value' => '3',
        'type' => 'string',
    ],
    'allowed_due_subscription' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
    'theme.beta' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
    'spamassassin.command' => [
        'value' => 'spamc -R',
        'type' => 'boolean',
    ],
    'spamassassin.required' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
    'spamassassin.enabled' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
    'mta.api_endpoint' => [
        'value' => null,
        'type' => 'string',
    ],
    'mta.api_key' => [
        'value' => null,
        'type' => 'string',
    ],
    'storage.s3' => [
        'value' => null,
        'type' => 'string',
    ],
    'rss.enabled' => [
        'value' => 'yes',
        'type' => 'boolean',
    ],
    'list.clone_for_others' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
    'gateways' => [
        'value' => '["direct"]',
        'type' => 'array',
    ],
    'automation.trigger_imported_contacts' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
    'campaign.bcc' => [
        'value' => '',
        'type' => 'string',
    ],
    'campaign.cc' => [
        'value' => '',
        'type' => 'string',
    ],
    'campaign.tracking_domain' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
    'list.allow_single_optin' => [
        'value' => 'yes',
        'type' => 'boolean',
    ],
    'campaign.enforce_unsubscribe_url_check' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
    'layout.menu_bar' => [
        'value' => 'left',
        'type' => 'string',
    ],
    'invoice.current' => [
        'value' => '1',
        'type' => 'number',
    ],
    'invoice.format' => [
        'value' => '%08d',  // a number of 8 digit, for example: sprintf('%08d', 15) -> 00000015
        'type' => 'string',
    ],
    'customer_can_change_language' => [
        'value' => 'yes',
        'type' => 'boolean',
    ],
    'customer_can_change_personality' => [
        'value' => 'yes',
        'type' => 'boolean',
    ],
    'campaign.stop_on_error' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
    'not_require_card_for_trial' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
    '2fa_enable' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
    'tracking_domain.enable_https' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
    'email.enable_one_click_unsubscribe_headers' => [
        'value' => 'yes',
        'type' => 'boolean',
    ],
    'campaign.duplicate' => [
        'value' => 'yes',
        'type' => 'boolean',
    ],
    'list.default.double_optin' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
    'email.default.skip_failed_message' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
    'notification.on_user_created' => [
        'value' => '',
        'type' => 'string',
    ],
    'signature.enabled' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
    'blacklist.use_global_blacklist' => [
        'value' => 'yes',
        'type' => 'boolean',
    ],
    'automation.outgoing_webhook' => [
        'value' => 'yes',
        'type' => 'boolean',
    ],
    'user.require_mobile_phone' => [
        'value' => 'no',
        'type' => 'boolean',
    ],
];


            