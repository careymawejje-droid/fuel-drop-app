<?php

return [
    'contact_email' => env('FUELDROP_CONTACT_EMAIL', env('MAIL_FROM_ADDRESS', 'support@fueldrop.test')),
    'delivery_fee' => 0,

    'roles' => [
        'admin' => 'Admin',
        'provider' => 'Provider',
        'driver' => 'Driver',
        'customer' => 'Customer',
    ],

    'admin' => [
        'name' => env('FUELDROP_ADMIN_NAME', env('FUELDROP_SUPER_ADMIN_NAME')),
        'email' => env('FUELDROP_ADMIN_EMAIL', env('FUELDROP_SUPER_ADMIN_EMAIL')),
        'phone' => env('FUELDROP_ADMIN_PHONE', env('FUELDROP_SUPER_ADMIN_PHONE')),
        'password' => env('FUELDROP_ADMIN_PASSWORD', env('FUELDROP_SUPER_ADMIN_PASSWORD')),
    ],
];
