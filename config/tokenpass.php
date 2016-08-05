<?php

return [
    // Enter your client id and client secret from Tokenpass here
    'client_id'     => env('TOKENPASS_CLIENT_ID'),
    'client_secret' => env('TOKENPASS_CLIENT_SECRET'),
    // this is the URL that Tokenpass uses to redirect the user back to your application
    'redirect'      => env('SITE_HOST', 'https://bitsplit.tokenly.com').'/account/authorize/callback',
    // this is the Tokenpass URL
    'base_url'      => rtrim(env('TOKENPASS_PROVIDER_HOST', 'https://tokenpass.tokenly.com'), '/'),
];
