<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Controls which origins may call the JSON API consumed by the plas-mobile
    | Flutter app. Native builds (Android/iOS/desktop via Dio) do not send an
    | Origin header and are not subject to CORS - this only matters for the
    | `flutter run -d chrome` web build during local development.
    |
    | The pattern below allows any localhost / 127.0.0.1 port, so it keeps
    | working when Flutter's dev server picks a different web port each run,
    | without opening the API to arbitrary external origins.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [],

    'allowed_origins_patterns' => [
        '#^http://(localhost|127\.0\.0\.1):\d+$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Bearer-token auth (mobile_api_tokens) - no cookies, so credentials
    // support is not needed.
    'supports_credentials' => false,

];
