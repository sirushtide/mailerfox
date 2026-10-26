<?php

return [
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],
    'update_server' => [
        'base_url' => 'https://api.getmailpurse.com',
        'product_id' => 2,
        'product_secret' => 'fv870t4vmO9HY7bQzclolcsQ3zul1CkC',
        'product_name' => 'Mailpurse',
        'license' => [
            'activate' => 'https://api.getmailpurse.com/wp-json/v1/license/activate',
            'check' => 'https://api.getmailpurse.com/wp-json/v1/license/check',
            'verify' => 'https://api.getmailpurse.com/wp-json/v1/license/verify',
            'deactivate' => 'https://api.getmailpurse.com/wp-json/v1/license/deactivate',
        ],
        'addons' => [
            'cold-email-outreach' => [
                'product_id'     => 3,
                'product_secret' => 'p85Zy89nyCYrPLDEoJ3ouvbV1hYRl5L0',
                'product_name'   => 'cold-email-outreach',
            ],
            'super-scrape' => [
                'product_id'     => 4,
                'product_secret' => 'X26Tm92SiJ4rzwIkjPmp4cmf8VUIgCDv',
                'product_name'   => 'super-scrape',
            ],
            'unibox' => [
                'product_id'     => 5,
                'product_secret' => 'YVRKN5ckO3IJ3NLhrOTprgpdAlfUvaQ3',
                'product_name'   => 'unibox',
            ],
            'workspace' => [
                'product_id'     => 6,
                'product_secret' => 'Xb5GgK0woOcjOmOQiEYqW4EECDURUMrP',
                'product_name'   => 'Workspace',
            ],
            'sms' => [
                'product_id'     => 7,
                'product_secret' => '',
                'product_name'   => 'sms',
            ],
            'whatsapp' => [
                'product_id'     => 8,
                'product_secret' => '',
                'product_name'   => 'whatsapp',
            ],
            'ai-assistant' => [
                'product_id'     => 9,
                'product_secret' => '',
                'product_name'   => 'ai-assistant',
            ],
        ],
    ],
    'twilio' => [
        'webhook_url' => env('TWILIO_WEBHOOK_URL'),
    ],
    'whatsapp' => [
        'app_secret'   => env('WHATSAPP_APP_SECRET'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
    ],
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'clock_skew_check' => [
        'url' => env('CLOCK_SKEW_CHECK_URL', 'https://www.google.com'),
        'threshold_seconds' => (int) env('CLOCK_SKEW_THRESHOLD_SECONDS', 120),
        'timeout_seconds' => (int) env('CLOCK_SKEW_TIMEOUT_SECONDS', 5),
        'cache_seconds' => (int) env('CLOCK_SKEW_CACHE_SECONDS', 60),
        'auto_pause_campaigns' => (bool) env('CLOCK_SKEW_AUTO_PAUSE_CAMPAIGNS', true),
        'auto_pause_seconds' => (int) env('CLOCK_SKEW_AUTO_PAUSE_SECONDS', 300),
    ],
    'sendgrid' => [
        'api_key' => env('SENDGRID_API_KEY'),
        // ECDSA public key for signed Event Webhooks. Obtain it from the SendGrid
        // dashboard (Settings > Mail Settings > Event Webhook > "signed" webhook)
        // or leave empty to auto-fetch it via the API key (cached).
        'webhook_public_key' => env('SENDGRID_WEBHOOK_PUBLIC_KEY'),
    ],
    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],
    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],
    'ipinfo' => [
        'token' => env('IPINFO_TOKEN'),
    ],
    'unlayer' => [
        'api_key' => env('UNLAYER_API_KEY'),
        'project_id' => env('UNLAYER_PROJECT_ID'),
    ],
];
