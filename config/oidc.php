<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Driver prefix
    |--------------------------------------------------------------------------
    |
    | Each connection below is registered as a Socialite driver named
    | "{prefix}{connection}". With the default prefix, a 'keycloak' connection
    | becomes Socialite::driver('oidc_keycloak').
    |
    */

    'driver_prefix' => 'oidc_',

    /*
    |--------------------------------------------------------------------------
    | Connections
    |--------------------------------------------------------------------------
    |
    | One entry per issuer. Every key the single-connection services block
    | accepts is valid here; the full list with explanations is in the README.
    | Common keys:
    |
    | base_url        The issuer URL; /.well-known/openid-configuration is
    |                 appended for discovery. https is required except for
    |                 loopback hosts.
    | scopes          Replace the default 'openid email profile'.
    | verify_jwt      Verify id_token signatures (default true).
    | jwt_public_key  PEM alternative to the discovered JWKS.
    | jwt_algorithm   Pin the accepted signing algorithm(s).
    | issuer          Override the expected `iss` claim.
    | proxy           Proxy for calls to the IdP, in Guzzle's format.
    | guzzle          Other Guzzle request options for calls to the IdP
    |                 (headers, verify, ...); the timeout and proxy keys win.
    | issuer_validator  Class implementing IssuerValidator, for issuer shapes
    |                 the default strict comparison cannot express (built-in
    |                 providers set this where their IdP needs it).
    | provider        Which Provider class drives this connection: a built-in
    |                 shorthand (entra, keycloak, auth0, okta, google) or the
    |                 name of any Provider subclass.
    |                 Built-ins derive config from friendlier keys (tenant,
    |                 server_url + realm, domain); your explicit values always
    |                 win. Subclasses can override configDefaults() and any
    |                 other method.
    |
    | Examples:
    |
    | 'keycloak' => [
    |     'provider'      => 'keycloak',
    |     'server_url'    => env('KEYCLOAK_SERVER_URL'),   // https://id.example.com
    |     'realm'         => env('KEYCLOAK_REALM'),
    |     'client_id'     => env('KEYCLOAK_CLIENT_ID'),
    |     'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
    |     'redirect'      => env('KEYCLOAK_REDIRECT_URI'),
    | ],
    |
    | 'entra' => [
    |     'provider'      => 'entra',
    |     'tenant'        => env('ENTRA_TENANT', 'common'),
    |     'client_id'     => env('ENTRA_CLIENT_ID'),
    |     'client_secret' => env('ENTRA_CLIENT_SECRET'),
    |     'redirect'      => env('ENTRA_REDIRECT_URI'),
    | ],
    |
    | 'authentik' => [
    |     'base_url'      => env('AUTHENTIK_BASE_URL'),
    |     'client_id'     => env('AUTHENTIK_CLIENT_ID'),
    |     'client_secret' => env('AUTHENTIK_CLIENT_SECRET'),
    |     'redirect'      => env('AUTHENTIK_REDIRECT_URI'),
    | ],
    |
    */

    'connections' => [

        // Nombre interno de la conexión con Ciudadanía Digital.
        // Por eso después se utiliza: oidc_ciudadania_digital.
        'ciudadania_digital' => [

            // Dirección principal del proveedor AGETIC.
            // Desde aquí se descubren las URLs de autorización, tokens y cierre de sesión.
            'base_url' => env('CIUDADANIA_DIGITAL_ISSUER'),

            // Identifica tu aplicación ante AGETIC.
            'client_id' => env('CIUDADANIA_DIGITAL_CLIENT_ID'),

            // Contraseña privada de la aplicación.
            'client_secret' => env('CIUDADANIA_DIGITAL_CLIENT_SECRET'),

            // Dirección del sistema a la que AGETIC devolverá al usuario después de autenticarse.
            'redirect' => env('CIUDADANIA_DIGITAL_REDIRECT_URI'),

            // Información del ciudadano que se solicitará.
            'scopes' => env(
                'CIUDADANIA_DIGITAL_SCOPES',
                'openid profile email'
            ),

            // Envía el Client ID y el secreto mediante autenticación HTTP Basic
            // cuando el servidor solicita los tokens a AGETIC.
            'token_auth_method' => 'client_secret_basic',

            // Impide continuar si AGETIC no devuelve el correo electrónico.
            'require_email' => true,

            // Dirección a la que regresará el usuario después de cerrar sesión en AGETIC.
            'post_logout_redirect_uri' => env('CIUDADANIA_DIGITAL_LOGOUT_REDIRECT_URI'),
        ],
    ],

];
