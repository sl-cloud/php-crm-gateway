<?php

namespace App\Providers;

use App\Contracts\MessagePublisherInterface;
use App\Services\Messaging\SqsPublisher;
use Aws\Sqs\SqsClient;
use Illuminate\Support\ServiceProvider;

class AwsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(SqsClient::class, function ($app) {
            $config = config('sqs.aws');
            
            $clientConfig = [
                'region' => $config['region'],
                'version' => $config['version'],
            ];

            // Add credentials if provided
            if (!empty($config['credentials']['key']) && !empty($config['credentials']['secret'])) {
                $clientConfig['credentials'] = [
                    'key' => $config['credentials']['key'],
                    'secret' => $config['credentials']['secret'],
                ];
            }

            // Add endpoint for LocalStack
            if (!empty($config['endpoint'])) {
                $clientConfig['endpoint'] = $config['endpoint'];
            }

            return new SqsClient($clientConfig);
        });

        $this->app->bind(MessagePublisherInterface::class, SqsPublisher::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
