<?php

namespace App\Services\Messaging;

use App\Contracts\MessagePublisherInterface;
use Aws\Sqs\SqsClient;
use Illuminate\Support\Facades\Log;

class SqsPublisher implements MessagePublisherInterface
{
    private SqsClient $sqsClient;
    private string $leadsQueueUrl;
    private string $logQueueUrl;

    public function __construct(SqsClient $sqsClient)
    {
        $this->sqsClient = $sqsClient;
        $this->leadsQueueUrl = config('sqs.leads_queue_url');
        $this->logQueueUrl = config('sqs.log_queue_url');
    }

    /**
     * Publish a lead created event to SQS.
     *
     * @param array $messageData The message data to publish
     * @param array $messageAttributes Additional message attributes
     * @return bool True if successful, false otherwise
     */
    public function publishLeadCreated(array $messageData, array $messageAttributes = []): bool
    {
        $defaultAttributes = [
            'EventType' => [
                'StringValue' => 'LeadCreated',
                'DataType' => 'String',
            ],
            'CorrelationId' => [
                'StringValue' => $messageData['correlation_id'] ?? '',
                'DataType' => 'String',
            ],
            'TenantId' => [
                'StringValue' => $messageData['tenant_id'] ?? '',
                'DataType' => 'String',
            ],
            'Timestamp' => [
                'StringValue' => now()->toISOString(),
                'DataType' => 'String',
            ],
        ];

        $attributes = array_merge($defaultAttributes, $messageAttributes);

        return $this->publishMessage($this->leadsQueueUrl, $messageData, $attributes);
    }

    /**
     * Publish a generic message to SQS.
     *
     * @param string $queueUrl The SQS queue URL
     * @param array $messageData The message data to publish
     * @param array $messageAttributes Additional message attributes
     * @return bool True if successful, false otherwise
     */
    public function publishMessage(string $queueUrl, array $messageData, array $messageAttributes = []): bool
    {
        try {
            $result = $this->sqsClient->sendMessage([
                'QueueUrl' => $queueUrl,
                'MessageBody' => json_encode($messageData),
                'MessageAttributes' => $messageAttributes,
            ]);

            Log::info('Message published to SQS', [
                'queue_url' => $queueUrl,
                'message_id' => $result->get('MessageId'),
                'correlation_id' => $messageData['correlation_id'] ?? null,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to publish message to SQS', [
                'queue_url' => $queueUrl,
                'error' => $e->getMessage(),
                'correlation_id' => $messageData['correlation_id'] ?? null,
            ]);

            return false;
        }
    }

    /**
     * Publish a log event to SQS.
     *
     * @param array $logData The log data to publish
     * @return bool True if successful, false otherwise
     */
    public function publishLogEvent(array $logData): bool
    {
        $attributes = [
            'LogLevel' => [
                'StringValue' => $logData['level'] ?? 'info',
                'DataType' => 'String',
            ],
            'CorrelationId' => [
                'StringValue' => $logData['correlation_id'] ?? '',
                'DataType' => 'String',
            ],
            'TenantId' => [
                'StringValue' => $logData['tenant_id'] ?? '',
                'DataType' => 'String',
            ],
            'Timestamp' => [
                'StringValue' => $logData['timestamp'] ?? now()->toISOString(),
                'DataType' => 'String',
            ],
        ];

        return $this->publishMessage($this->logQueueUrl, $logData, $attributes);
    }
}
