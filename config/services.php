<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'brevo' => [
        'key' => env('BREVO_API_KEY'),
        'list_id' => (int) env('BREVO_LIST_ID', 2),
        // Liste « Consultation paroisse » : personnes ayant répondu à la consultation sur les projets d'architecture.
        'consultation_list_id' => (int) env('BREVO_CONSULTATION_LIST_ID', 6),
        // Liste « Croix du Pape » : personnes inscrites sur /croix pour recevoir le lien de vote du concours S.O.S Calvaires.
        'croix_list_id' => (int) env('BREVO_CROIX_LIST_ID', 7),
        // Liste « Huile d'olive » : contacts inscrits depuis la page /huile (QR code des étiquettes).
        'huile_list_id' => (int) env('BREVO_HUILE_LIST_ID', 8),
    ],

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

    'matomo' => [
        'url' => env('MATOMO_URL'),
        'site_id' => env('MATOMO_SITE_ID'),
        // Clé d'API Matomo (droit « vue » sur le site), pour les statistiques du tableau de bord de l'admin.
        'token' => env('MATOMO_TOKEN'),
    ],

    'contact' => [
        'email' => env('CONTACT_EMAIL', 'information.saint-joseph@saint-arnoux.fr'),
    ],

    'admin_email' => env('ADMIN_EMAIL'),

];
