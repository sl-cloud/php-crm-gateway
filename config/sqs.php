<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SQS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for AWS SQS queues used by the application.
    |
    */

    'leads_queue_url' => env('AWS_SQS_QUEUE_URL'),
    'log_queue_url' => env('AWS_SQS_LOG_QUEUE_URL'),

    /*
    |--------------------------------------------------------------------------
    | AWS Configuration
    |--------------------------------------------------------------------------
    |
    | AWS SDK configuration for SQS client.
    |
    */

    'aws' => [
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'version' => 'latest',
        'credentials' => [
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
        ],
        'endpoint' => env('AWS_ENDPOINT'), // For LocalStack
    ],
];
