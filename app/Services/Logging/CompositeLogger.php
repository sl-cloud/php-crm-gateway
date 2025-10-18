<?php

namespace App\Services\Logging;

use App\Contracts\LoggerInterface;

class CompositeLogger implements LoggerInterface
{
    private LocalLogger $localLogger;
    private RemoteLogger $remoteLogger;

    public function __construct(LocalLogger $localLogger, RemoteLogger $remoteLogger)
    {
        $this->localLogger = $localLogger;
        $this->remoteLogger = $remoteLogger;
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
        $this->localLogger->info($message, $context);
        $this->remoteLogger->info($message, $context);
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
        $this->localLogger->error($message, $context);
        $this->remoteLogger->error($message, $context);
    }

    /**
     * Log a warning message.
     *
     * @param string $message
     * @param array $context
     * @param array $context
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        $this->localLogger->warning($message, $context);
        $this->remoteLogger->warning($message, $context);
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
        $this->localLogger->debug($message, $context);
        $this->remoteLogger->debug($message, $context);
    }
}
