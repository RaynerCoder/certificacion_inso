<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    --------------------------------------------------------------------------
    OpenBCB
    --------------------------------------------------------------------------
    Datos utilizados para conectar el Sistema Certificador INSO con el ambiente de pruebas de OpenBCB.
    */
    'openbcb' => [
        'base_url' => env('OPENBCB_BASE_URL'),
        'entity_id' => env('OPENBCB_ENTITY_ID'),
        'key_id' => env('OPENBCB_KEY_ID'),
        'shared_secret' => env('OPENBCB_SHARED_SECRET'),
        'access_token' => env('OPENBCB_ACCESS_TOKEN'),

        'endpoints' => [
            'entidad' => env('OPENBCB_ENTIDAD_ENDPOINT'),
            'qr_crear' => env('OPENBCB_QR_CREAR_ENDPOINT'),
            'qr_consultar' => env('OPENBCB_QR_CONSULTAR_ENDPOINT'),

            'cuenta_actualizar' => env('OPENBCB_CUENTA_ACTUALIZAR_ENDPOINT'),
        ],

        'qr' => [
            'titular_destinatario' => env('OPENBCB_QR_TITULAR_DESTINATARIO'),
            'ci_nit_destinatario' => env('OPENBCB_QR_CI_NIT_DESTINATARIO'),
            'eif' => env('OPENBCB_QR_EIF'),
            'cuenta_destino' => env('OPENBCB_QR_CUENTA_DESTINO'),
            'cod_moneda' => env('OPENBCB_QR_COD_MONEDA', 'BOB'),
            'codigo_servicio' => env('OPENBCB_QR_CODIGO_SERVICIO', '0'),
        ],

        'notification' => [
            'token' => env('OPENBCB_NOTIFICACION_TOKEN'),
        ],
        
    ],

];
