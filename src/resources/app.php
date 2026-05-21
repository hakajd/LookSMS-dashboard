<?php

$app_specific = [
    'application_title'      => 'LookSMS',
    'application_description'=> 'Envoyez des SMS depuis votre propre numéro',
    'application_version'    => '1.4.2',
    'app_version_code'       => 14,
    'company_name'           => 'LookSMS',
    'company_url'            => 'https://looksms.com',
    'application_url'        => 'https://app.looksms.com/download/LookSMS-latest.apk',
    'unsubscribe_url'        => '%server%/unsubscribe.php',
    'logo_src'               => 'logo.png',
    'favicon_src'            => 'favicon.ico',
    'get_credits_url'        => 'https://looksms.com/#pricing',
    'skin'                   => 'blue',
    'default_language'       => 'English',
    'default_use_progressive_queue' => 1,
    'default_credits'        => 200,
    'default_devices_limit'  => 5,
    'default_contacts_limit' => 500,
    'smtp_ssl_verification'  => 1,
    'use_credits_for_received_messages_enabled' => 0
];

$lang = array_merge($lang, $app_specific);
