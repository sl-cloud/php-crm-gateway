<?php

namespace App\Services\Logging;

use App\Contracts\LoggerInterface;
use App\Contracts\MessagePublisherInterface;

class RemoteLogger implements LoggerInterface
{
    private MessagePublisherInterface $messagePublisher;

    public function __construct(MessagePublisherInterface $messagePublisher)
    {
        $this->messagePublisher = $messagePublisher;
    }

    /**
     * Log an info message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        $this->logToSqs('info', $message, $context);
    }

    /**
     * Log an error message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        $this->logToSqs('error', $message, $context);
    }

    /**
     * Log a warning message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        $this->logToSqs('warning', $message, $context);
    }

    /**
     * Log a debug message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
        $this->logToSqs('debug', $message, $context);
    }

    /**
     * Send log message to SQS.
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    private function logToSqs(string $level, string $message, array $context = []): void
    {
        $logData = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'timestamp' => now()->toISOString(),
            'correlation_id' => $context['correlation_id'] ?? null,
            'tenant_id' => $context['tenant_id'] ?? null,
        ];

        $this->messagePublisher->publishLogEvent($logData);
    }
}
