<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Drivers
    |--------------------------------------------------------------------------
    |
    | Each entry maps a gateway key to its driver class and connection settings.
    | The 'classname' must implement App\Contracts\Gateway\GatewayInterface.
    |
    */

    'testpay' => [
        'classname' => App\Services\Gateway\Vendors\TestpayGateway::class,
        'timeout' => 30,
        'retry' => 2,
    ],

];
