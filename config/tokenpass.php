<?php

return [
    // Enter your client id and client secret from Tokenpass here
    'client_id'     => env('TOKENPASS_CLIENT_ID'),
    'client_secret' => 'KTC7eWX1CUGd16Z2Zy8HrVaymDD7IDLZIcRyWE8l',
    // this is the URL that Tokenpass uses to redirect the user back to your application
    'redirect'      => 'http://localhost:8000/account/authorize/callback',
    // this is the Tokenpass URL
    'base_url'      => rtrim(env('TOKENPASS_PROVIDER_HOST', 'https://tokenpass.tokenly.com'), '/'),
];
