<?php

namespace App\Services\Logging;

use App\Contracts\LoggerInterface;
use App\Contracts\MessagePublisherInterface;
use Illuminate\Support\Facades\App;

class LogManager
{
    private string $logMode;

    public function __construct()
    {
        $this->logMode = config('logging.mode', 'local');
    }

    /**
     * Get the appropriate logger based on LOG_MODE.
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return match ($this->logMode) {
            'remote' => new RemoteLogger(App::make(MessagePublisherInterface::class)),
            'both' => new CompositeLogger(
                new LocalLogger(),
                new RemoteLogger(App::make(MessagePublisherInterface::class))
            ),
            default => new LocalLogger(),
        };
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
        $this->getLogger()->info($message, $context);
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
        $this->getLogger()->error($message, $context);
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
        $this->getLogger()->warning($message, $context);
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
        $this->getLogger()->debug($message, $context);
    }
}
