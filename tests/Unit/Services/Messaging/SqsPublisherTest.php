<?php

namespace Tests\Unit\Services\Messaging;

use App\Services\Messaging\SqsPublisher;
use Aws\Sqs\SqsClient;
use Aws\Result;
use Mockery;
use Tests\TestCase;

class SqsPublisherTest extends TestCase
{
    private SqsPublisher $publisher;
    private $mockSqsClient;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockSqsClient = Mockery::mock(SqsClient::class);
        $this->publisher = new SqsPublisher($this->mockSqsClient);
        
        // Mock config values
        config(['sqs.leads_queue_url' => 'http://test-queue-url']);
        config(['sqs.log_queue_url' => 'http://test-log-queue-url']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_publishes_lead_created_event_successfully(): void
    {
        $messageData = [
            'event_type' => 'LeadCreated',
            'tenant_id' => '1',
            'correlation_id' => 'test-correlation-id',
            'lead_data' => [
                'email' => 'test@example.com',
                'first_name' => 'John',
            ],
        ];

        $expectedResult = new Result([
            'MessageId' => 'test-message-id',
        ]);

        $this->mockSqsClient
            ->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($args) use ($messageData) {
                return $args['QueueUrl'] === 'http://test-queue-url' &&
                       $args['MessageBody'] === json_encode($messageData) &&
                       isset($args['MessageAttributes']['EventType']) &&
                       isset($args['MessageAttributes']['CorrelationId']) &&
                       isset($args['MessageAttributes']['TenantId']) &&
                       isset($args['MessageAttributes']['Timestamp']);
            })
            ->andReturn($expectedResult);

        $result = $this->publisher->publishLeadCreated($messageData);

        $this->assertTrue($result);
    }

    public function test_publishes_generic_message_successfully(): void
    {
        $queueUrl = 'http://test-queue-url';
        $messageData = ['test' => 'data'];
        $messageAttributes = [
            'TestAttribute' => [
                'StringValue' => 'test-value',
                'DataType' => 'String',
            ],
        ];

        $expectedResult = new Result([
            'MessageId' => 'test-message-id',
        ]);

        $this->mockSqsClient
            ->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($args) use ($queueUrl, $messageData, $messageAttributes) {
                return $args['QueueUrl'] === $queueUrl &&
                       $args['MessageBody'] === json_encode($messageData) &&
                       $args['MessageAttributes'] === $messageAttributes;
            })
            ->andReturn($expectedResult);

        $result = $this->publisher->publishMessage($queueUrl, $messageData, $messageAttributes);

        $this->assertTrue($result);
    }

    public function test_publishes_log_event_successfully(): void
    {
        $logData = [
            'level' => 'info',
            'message' => 'Test log message',
            'correlation_id' => 'test-correlation-id',
            'tenant_id' => '1',
            'timestamp' => '2023-01-01T00:00:00Z',
        ];

        $expectedResult = new Result([
            'MessageId' => 'test-message-id',
        ]);

        $this->mockSqsClient
            ->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($args) use ($logData) {
                return $args['QueueUrl'] === 'http://test-log-queue-url' &&
                       $args['MessageBody'] === json_encode($logData) &&
                       isset($args['MessageAttributes']['LogLevel']) &&
                       isset($args['MessageAttributes']['CorrelationId']) &&
                       isset($args['MessageAttributes']['TenantId']) &&
                       isset($args['MessageAttributes']['Timestamp']);
            })
            ->andReturn($expectedResult);

        $result = $this->publisher->publishLogEvent($logData);

        $this->assertTrue($result);
    }

    public function test_handles_sqs_exception(): void
    {
        $messageData = ['test' => 'data'];

        $this->mockSqsClient
            ->shouldReceive('sendMessage')
            ->once()
            ->andThrow(new \Exception('SQS Error'));

        $result = $this->publisher->publishLeadCreated($messageData);

        $this->assertFalse($result);
    }

    public function test_includes_default_attributes_for_lead_created(): void
    {
        $messageData = [
            'tenant_id' => '1',
            'correlation_id' => 'test-correlation-id',
        ];

        $this->mockSqsClient
            ->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($args) {
                $attributes = $args['MessageAttributes'];
                
                return isset($attributes['EventType']['StringValue']) &&
                       $attributes['EventType']['StringValue'] === 'LeadCreated' &&
                       isset($attributes['CorrelationId']['StringValue']) &&
                       $attributes['CorrelationId']['StringValue'] === 'test-correlation-id' &&
                       isset($attributes['TenantId']['StringValue']) &&
                       $attributes['TenantId']['StringValue'] === '1' &&
                       isset($attributes['Timestamp']['StringValue']);
            })
            ->andReturn(new Result(['MessageId' => 'test-message-id']));

        $this->publisher->publishLeadCreated($messageData);
    }

    public function test_merges_custom_attributes_with_defaults(): void
    {
        $messageData = [
            'tenant_id' => '1',
            'correlation_id' => 'test-correlation-id',
        ];

        $customAttributes = [
            'CustomAttribute' => [
                'StringValue' => 'custom-value',
                'DataType' => 'String',
            ],
        ];

        $this->mockSqsClient
            ->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($args) use ($customAttributes) {
                $attributes = $args['MessageAttributes'];
                
                return isset($attributes['EventType']) && // Default attribute
                       isset($attributes['CustomAttribute']) && // Custom attribute
                       $attributes['CustomAttribute'] === $customAttributes['CustomAttribute'];
            })
            ->andReturn(new Result(['MessageId' => 'test-message-id']));

        $this->publisher->publishLeadCreated($messageData, $customAttributes);
    }
}
