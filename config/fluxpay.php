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

    /*
    |--------------------------------------------------------------------------
    | Stalled Order Thresholds
    |--------------------------------------------------------------------------
    |
    | Orders stuck at SENT_TO_PROVIDER are polled by StalledOrderCheckCommand.
    | "min_age": skip orders younger than this (give the callback a chance).
    | "max_age": skip orders older than this (avoid polling forever; ops decides).
    |
    */
    'stalled_min_age_seconds' => (int) env('FLUXPAY_STALLED_MIN_AGE', 120),
    'stalled_max_age_seconds' => (int) env('FLUXPAY_STALLED_MAX_AGE', 86400),

    /*
    |--------------------------------------------------------------------------
    | Merchant API Rate Limit
    |--------------------------------------------------------------------------
    |
    | Max requests per minute per merchantNo on /api/* endpoints. Requests
    | without a merchantNo are bucketed by client IP. Exceeding the limit
    | returns HTTP 429.
    |
    */
    'merchant_api_rate_limit_per_minute' => (int) env('FLUXPAY_MERCHANT_API_RATE_LIMIT', 60),

    /*
    |--------------------------------------------------------------------------
    | Operational Alerts
    |--------------------------------------------------------------------------
    |
    | Where to send alerts for stalled orders and failed merchant callbacks.
    | Recipients can be a comma-separated list of email addresses; Slack and
    | Feishu (Lark) are configured by their incoming-webhook URLs. Channels
    | left empty are silently skipped.
    |
    */
    'alert_recipients' => array_filter(array_map('trim', explode(',', (string) env('FLUXPAY_ALERT_RECIPIENTS', '')))),
    'alert_slack_webhook' => env('FLUXPAY_ALERT_SLACK_WEBHOOK'),
    'alert_feishu_webhook' => env('FLUXPAY_ALERT_FEISHU_WEBHOOK'),

    'alert_stalled_threshold_hours' => (int) env('FLUXPAY_ALERT_STALLED_HOURS', 4),
    'alert_dedupe_ttl_seconds' => (int) env('FLUXPAY_ALERT_DEDUPE_TTL', 86400),

];
