<?php

namespace Tests\Integration;

use App\Services\Messaging\SqsPublisher;
use Aws\Sqs\SqsClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SqsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private SqsPublisher $publisher;
    private SqsClient $sqsClient;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configure for LocalStack
        config([
            'sqs.aws' => [
                'region' => 'us-east-1',
                'version' => 'latest',
                'credentials' => [
                    'key' => 'test',
                    'secret' => 'test',
                ],
                'endpoint' => 'http://localstack:4566',
            ],
            'sqs.leads_queue_url' => 'http://localstack:4566/000000000000/leads-queue',
            'sqs.log_queue_url' => 'http://localstack:4566/000000000000/log-events-queue',
        ]);
        
        $this->sqsClient = app(SqsClient::class);
        $this->publisher = app(SqsPublisher::class);
    }

    public function test_can_publish_to_sqs_with_localstack(): void
    {
        // Skip if LocalStack is not available
        if (!$this->isLocalStackAvailable()) {
            $this->markTestSkipped('LocalStack is not available');
        }

        $messageData = [
            'event_type' => 'LeadCreated',
            'tenant_id' => '1',
            'correlation_id' => 'test-correlation-id',
            'lead_data' => [
                'email' => 'test@example.com',
                'first_name' => 'John',
            ],
        ];

        $result = $this->publisher->publishLeadCreated($messageData);

        $this->assertTrue($result);
    }

    public function test_can_publish_log_event_to_sqs(): void
    {
        // Skip if LocalStack is not available
        if (!$this->isLocalStackAvailable()) {
            $this->markTestSkipped('LocalStack is not available');
        }

        $logData = [
            'level' => 'info',
            'message' => 'Test log message',
            'correlation_id' => 'test-correlation-id',
            'tenant_id' => '1',
            'timestamp' => now()->toISOString(),
        ];

        $result = $this->publisher->publishLogEvent($logData);

        $this->assertTrue($result);
    }

    private function isLocalStackAvailable(): bool
    {
        try {
            $this->sqsClient->listQueues();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
