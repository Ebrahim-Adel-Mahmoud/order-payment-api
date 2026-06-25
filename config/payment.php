<?php

declare(strict_types=1);

use App\Services\Payment\Gateways\CreditCardGateway;
use App\Services\Payment\Gateways\PaypalGateway;

return [
    'gateways' => [
        'credit_card' => CreditCardGateway::class,
        'paypal' => PaypalGateway::class,
    ],

    'credit_card' => [
        'api_key' => env('CREDIT_CARD_API_KEY'),
        'secret' => env('CREDIT_CARD_SECRET'),
        'simulate_failure_suffix' => env('CREDIT_CARD_FAILURE_SUFFIX', '0000'),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'failure_threshold' => env('PAYPAL_FAILURE_THRESHOLD', 10000),
    ],
];
