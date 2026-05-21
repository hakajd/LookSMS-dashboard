<?php

$app_specific = [
    'application_title' => 'LookSMS',
    'application_description' => 'Send SMS from your own phone number',
    'application_version' => '1.0.0',
    'app_version_code' => 1,
    'company_name' => 'LookSMS',
    'company_url' => 'https://app.looksms.com',
    'application_url' => 'https://app.looksms.com/download/looksms.apk',
    'unsubscribe_url' => '%server%/unsubscribe.php',
    'logo_src' => 'logo.png',
    'favicon_src' => 'favicon.ico',
    'get_credits_url' => 'https://app.looksms.com/#pricing',
    'skin' => 'blue',
    'default_language' => 'English',
    'default_use_progressive_queue' => 1,
    'default_credits' => 200,
    'default_devices_limit' => 2,
    'default_contacts_limit' => 200,
    'smtp_ssl_verification' => 1,
    'use_credits_for_received_messages_enabled' => 1
];

$lang = array_merge($lang, $app_specific);