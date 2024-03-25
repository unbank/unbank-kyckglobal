<?php

return [
    "username" => env('KYCKGLOBAL_USERNAME'),
    "password" => env('KYCKGLOBAL_PASSWORD'),
    "url" => env('KYCKGLOBAL_URL', 'https://sandboxapi.kyckglobal.com'),
    'payer_id' => env('KYCKGLOBAL_PAYER_ID'),
    'payer_name' => env('KYCKGLOBAL_PAYER_NAME'),
    'payment_method' => env('KYCKGLOABL_PAYMENT_METHOD', 'E'),
    'pickup_ready' => env('PICKUP_READY_URL'),

    'atm_direction' => 'Tap "Mobile Cash" on the ATM screen to initiate your ATM transaction.',
    'expire_in_hours' => 24 * ( (int) env('PICKUP_EXPIRES_IN_DAYS', 90) ),

    // Transaction statues
    'statuses' => [
        "cancelled" => ['Returned', 'Rejected'],
        'proccessed' => ['Proccessing', "Submitted", "sent"],
        'accepted' => ['Pickup Ready'],
        "completed" => ["Success"],
        'cancellable' => ['Submitted', 'Returned', 'Pickup Ready']
    ],

    // Class that will store the disbursement data
    'disbursement_class' => null
]

?>
