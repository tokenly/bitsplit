<?php

return [
    // Enter your client id and client secret from Tokenly Accounts here
    'client_id'     => env('TOKENPASS_CLIENT_ID'),
    'client_secret' => env('TOKENPASS_CLIENT_SECRET'),
    // this is the URL that Tokenly Accounts uses to redirect the user back to your application
    'redirect'      => env('SITE_HOST', 'https://bitsplit.tokenly.com').'/account/authorize/callback',
    // this is the Tokenly Accounts URL
    'base_url'      => rtrim(env('TOKENPASS_PROVIDER_HOST', 'https://accounts.tokenly.com'), '/'),
];
