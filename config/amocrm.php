<?php



return [

    'models' => [
        'token' => Services\AmoCRM\Models\AmoCrmToken::class,
    ],
    'client_id' => env('AMO_CLIENT_ID'),
    'base_domain' => env('AMO_BASE_DOMAIN'),
    'client_secret' => env('AMO_CLIENT_SECRET'),
    'redirect_uri' => env('AMO_REDERECT_URI')

];