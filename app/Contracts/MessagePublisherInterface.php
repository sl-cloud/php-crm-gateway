<?php

namespace App\Contracts;

interface MessagePublisherInterface
{
    /**
     * Publish a lead created event to SQS.
     *
     * @param array $messageData The message data to publish
     * @param array $messageAttributes Additional message attributes
     * @return bool True if successful, false otherwise
     */
    public function publishLeadCreated(array $messageData, array $messageAttributes = []): bool;

    /**
     * Publish a generic message to SQS.
     *
     * @param string $queueUrl The SQS queue URL
     * @param array $messageData The message data to publish
     * @param array $messageAttributes Additional message attributes
     * @return bool True if successful, false otherwise
     */
    public function publishMessage(string $queueUrl, array $messageData, array $messageAttributes = []): bool;
}
