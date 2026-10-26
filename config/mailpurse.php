<?php

return [
    'version' => '2.0.1',
    'skip_install_wizard' => (bool) env('SKIP_INSTALL_WIZARD', false),
    'features' => [
        // Mega Menu authoring UI is deferred to a future Pro release. When
        // disabled the UI entry points are hidden, but the underlying builder,
        // data and rendering are retained so existing menus keep working.
        'mega_menu_builder' => (bool) env('MAILPURSE_MEGA_MENU_BUILDER', false),
    ],
    'tracking_domains' => [
        'cname_target' => env('TRACKING_DOMAIN_CNAME_TARGET'),
    ],
    'fonts' => [
        'supported_google_families' => [
            // Original
            'Inter', 'Roboto', 'Open Sans', 'Lato', 'Sora', 'Montserrat', 'Poppins', 'Nunito', 'Raleway',
            // Sans-serif
            'Work Sans', 'DM Sans', 'Manrope', 'Outfit', 'Plus Jakarta Sans', 'Space Grotesk',
            'Figtree', 'Hanken Grotesk', 'Onest', 'Geist', 'Geist Mono', 'Schibsted Grotesk',
            'Bricolage Grotesque', 'Mona Sans', 'Hubballi', 'Switzer', 'Cairo', 'Tajawal',
            'IBM Plex Sans', 'IBM Plex Sans Arabic', 'IBM Plex Mono', 'IBM Plex Serif',
            'Rubik', 'Karla', 'Mulish', 'Barlow', 'Barlow Condensed', 'Oxygen', 'Fira Sans',
            'Hind', 'Heebo', 'Mukta', 'Assistant', 'Jost', 'Lexend', 'Lexend Deca',
            'Spectral', 'Newsreader', 'Fraunces', 'Bodoni Moda', 'DM Serif Display', 'DM Serif Text',
            // Serif
            'Playfair Display', 'Merriweather', 'Lora', 'Crimson Pro', 'Crimson Text',
            'Source Serif 4', 'Source Sans 3', 'Source Code Pro', 'Libre Baskerville',
            'Cormorant', 'Cormorant Garamond', 'EB Garamond', 'Zilla Slab', 'Roboto Slab',
            'Bitter', 'Arvo', 'Vollkorn', 'Cardo', 'Lusitana', 'Old Standard TT',
            // Display / Decorative
            'Oswald', 'Bebas Neue', 'Anton', 'Archivo', 'Archivo Black', 'Archivo Narrow',
            'Righteous', 'Pacifico', 'Lobster', 'Caveat', 'Comfortaa', 'Quicksand',
            'Kalam', 'Satisfy', 'Patrick Hand', 'Indie Flower', 'Shadows Into Light',
            'Dancing Script', 'Great Vibes', 'Sacramento', 'Allura', 'Yellowtail',
            'Tangerine', 'Parisienne', 'Marcellus', 'Marcellus SC', 'Cinzel', 'Cinzel Decorative',
            'Playfair Display SC', 'Josefin Sans', 'Josefin Slab', 'Abril Fatface',
            'Yeseva One', 'Fredoka', 'Fredoka One', 'Baloo 2', 'Baloo Bhai 2', 'Baloo Bhaina 2',
            'Ubuntu', 'Ubuntu Mono', 'Ubuntu Condensed', 'Slabo 27px', 'Slabo 13px',
            'Petit Formal Script', 'Reem Kufi', 'Reem Kufi Fun', 'Markazi Text', 'Scheherazade New',
            'Vazirmatn', 'Estedad', 'Noto Kufi Arabic', 'Noto Naskh Arabic', 'Noto Sans Arabic',
            'Noto Serif Arabic', 'Noto Rashi Hebrew', 'Noto Sans Hebrew', 'Frank Ruhl Libre',
            'David Libre', 'Rubik Mono One', 'Spline Sans', 'Spline Sans Mono',
            'Golos Text', 'Wix Madefor Display', 'Wix Madefor Text', 'Hanken Grotesk',
            'Instrument Sans', 'Instrument Serif', 'Instrument Mono', 'Roboto Flex',
            'Roboto Mono', 'Roboto Condensed', 'Roboto Serif', 'Material Symbols Outlined',
            'Material Symbols Rounded', 'Material Symbols Sharp', 'Noto Sans',
            'Noto Serif', 'Noto Sans Display', 'Noto Serif Display', 'Noto Sans Mono',
            // Monospace
            'JetBrains Mono', 'Fira Code', 'Inconsolata', 'Space Mono', 'Victor Mono',
            'Geist Mono', 'IBM Plex Mono', 'Roboto Mono', 'Source Code Pro', 'Noto Sans Mono',
            // Handwriting / Script
            'Caveat', 'Kalam', 'Satisfy', 'Patrick Hand', 'Indie Flower', 'Shadows Into Light',
            'Dancing Script', 'Great Vibes', 'Sacramento', 'Allura', 'Yellowtail',
            'Tangerine', 'Parisienne', 'Petit Formal Script', 'Homemade Apple',
            'Gochi Hand', 'Gloria Hallelujah', 'Coming Soon', 'Architects Daughter',
            'Nanum Pen Script', 'Nanum Brush Script', 'La Belle Aurore', 'Italianno',
            'Pinyon Script', 'Mrs Saint Delafield', 'Mr Dafoe', 'Mr De Haviland',
            'Monsieur La Doulaise', 'Marck Script', 'Mansalva', 'Maven Pro', 'MedievalSharp',
            'Meddon', 'Megras', 'Meie Script', 'Mansalva', 'Marcellus', 'Marcellus SC',
        ],
    ],
    'clock_skew_check' => [
        'url' => env('CLOCK_SKEW_CHECK_URL', 'https://www.google.com'),
        'threshold_seconds' => (int) env('CLOCK_SKEW_THRESHOLD_SECONDS', 180),
        'timeout_seconds' => (int) env('CLOCK_SKEW_TIMEOUT_SECONDS', 5),
        'auto_queue_restart' => (bool) env('CLOCK_SKEW_AUTO_QUEUE_RESTART', false),
    ],
    'email_validation' => [
        'snapvalid_429_delay_seconds' => (int) env('SNAPVALID_429_DELAY_SECONDS', 10),
    ],
    'reply_tracking' => [
        'enabled' => (bool) env('REPLY_TRACKING_ENABLED', false),
        'reply_domain' => env('REPLY_TRACKING_REPLY_DOMAIN'),
        'imap' => [
            'hostname' => env('REPLY_TRACKING_IMAP_HOST'),
            'port' => (int) env('REPLY_TRACKING_IMAP_PORT', 993),
            'encryption' => env('REPLY_TRACKING_IMAP_ENCRYPTION', 'ssl'),
            'username' => env('REPLY_TRACKING_IMAP_USERNAME'),
            'password' => env('REPLY_TRACKING_IMAP_PASSWORD'),
            'mailbox' => env('REPLY_TRACKING_IMAP_MAILBOX', 'INBOX'),
            'delete_after_processing' => (bool) env('REPLY_TRACKING_IMAP_DELETE_AFTER_PROCESSING', false),
            'max_emails_per_batch' => (int) env('REPLY_TRACKING_IMAP_MAX_EMAILS_PER_BATCH', 50),
        ],
    ],
    'spam_scoring' => [
        'blocking_threshold' => (int) env('SPAM_BLOCKING_THRESHOLD', 15),
        'enabled_by_default' => (bool) env('SPAM_SCORING_ENABLED_BY_DEFAULT', false),
    ],
    'unibox' => [
        // NOTE: the fallback below is the legacy committed key kept only so existing
        // encrypted IMAP credentials still decrypt. Set UNIBOX_ENC_KEY to a fresh
        // base64:... 32-byte key and re-save accounts to migrate off it.
        'enc_key' => env('UNIBOX_ENC_KEY', 'base64:dVG+KkDlY9lu6gHreJJDrSdwL3GZY1xG1l3gyWXz394='),
    ],
];
