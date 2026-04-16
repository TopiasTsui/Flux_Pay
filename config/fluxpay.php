<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Order Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix prepended to all system-generated order numbers.
    |
    */
    'order_prefix' => env('FLUXPAY_ORDER_PREFIX', 'FP'),

    /*
    |--------------------------------------------------------------------------
    | Callback Max Retries
    |--------------------------------------------------------------------------
    |
    | Maximum number of retry attempts for merchant callback notifications.
    |
    */
    'callback_max_retries' => (int) env('FLUXPAY_CALLBACK_MAX_RETRIES', 5),

    /*
    |--------------------------------------------------------------------------
    | Callback Retry Interval
    |--------------------------------------------------------------------------
    |
    | Interval in seconds between callback retry attempts.
    |
    */
    'callback_retry_interval' => (int) env('FLUXPAY_CALLBACK_RETRY_INTERVAL', 60),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency code used when none is specified.
    |
    */
    'default_currency' => env('FLUXPAY_DEFAULT_CURRENCY', 'PHP'),

];
