<?php

return [
    'secret' => env('PAYMONGO_SECRET_KEY'),
    'public' => env('PAYMONGO_PUBLIC_KEY'),
    'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),
    'endpoint' => env('PAYMONGO_ENDPOINT', 'https://api.paymongo.com/v1'),
    'test_wallet_account_number' => env('PAYMONGO_TEST_WALLET_ACCOUNT_NUMBER'),
    'test_wallet_account_name' => env('PAYMONGO_TEST_WALLET_ACCOUNT_NAME'),
];
