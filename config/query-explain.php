<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Query Explain Enabled
    |--------------------------------------------------------------------------
    |
    | This option determines whether the query explain functionality is enabled.
    | Set to false to disable the functionality completely.
    |
    */

    'enabled' => env('QUERY_EXPLAIN_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Query Explain Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where the query explain interface will be accessible.
    | You can change this path to anything you like.
    |
    */

    'path' => env('QUERY_EXPLAIN_PATH', 'query-explain'),

    /*
    |--------------------------------------------------------------------------
    | Query Explain Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every route in the query explain interface.
    | You can customize these middleware as needed for your application.
    |
    */

    'middleware' => [
        \Bidb97\QueryExplain\Http\Middleware\Authorize::class
    ]

];
