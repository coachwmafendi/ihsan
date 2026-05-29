<?php

return [

    'paths' => ['api/public/*', 'e/widget.js', 'embed.js'],

    'allowed_methods' => ['GET', 'POST'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
