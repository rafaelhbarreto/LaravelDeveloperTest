<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Congress API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the US Congress API integration.
    | API Documentation: https://www.congress.gov/developers/docs/api
    |
    */

    'api' => [
        /*
         * Base URL for the Congress API
         */
        'base_url' => env('CONGRESS_API_BASE_URL', 'https://api.congress.gov/v3'),

        /*
         * API Key for authentication
         * Get your API key at: https://api.congress.gov/sign-up/
         */
        'api_key' => env('CONGRESS_API_KEY'),

        /*
         * Default chunk size for fetching records per request
         * API supports up to 250 records per page
         */
        'chunk_size' => (int) env('CONGRESS_FETCH_CHUNK_SIZE', 250),

        /*
         * Request timeout in seconds
         */
        'timeout' => (int) env('CONGRESS_API_TIMEOUT', 30),
    ],

    'queues' => [
        /*
         * Queue name for fetching data from the API
         */
        'fetch' => env('CONGRESS_FETCH_QUEUE', 'congress-fetch'),

        /*
         * Queue name for storing data in the database
         */
        'store' => env('CONGRESS_STORE_QUEUE', 'congress-store'),
    ],
];
