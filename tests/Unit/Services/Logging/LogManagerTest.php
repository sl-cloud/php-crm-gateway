<?php

namespace Tests\Unit\Services\Logging;

use App\Contracts\LoggerInterface;
use App\Services\Logging\LogManager;
use App\Services\Logging\LocalLogger;
use App\Services\Logging\RemoteLogger;
use App\Services\Logging\CompositeLogger;
use App\Contracts\MessagePublisherInterface;
use Tests\TestCase;
use Mockery;

class LogManagerTest extends TestCase
{
    private LogManager $logManager;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_returns_local_logger_when_mode_is_local(): void
    {
        config(['logging.mode' => 'local']);
        
        $logManager = new LogManager();
        $logger = $logManager->getLogger();
        
        $this->assertInstanceOf(LocalLogger::class, $logger);
    }

    public function test_returns_remote_logger_when_mode_is_remote(): void
    {
        config(['logging.mode' => 'remote']);
        
        $mockPublisher = Mockery::mock(MessagePublisherInterface::class);
        $this->app->instance(MessagePublisherInterface::class, $mockPublisher);
        
        $logManager = new LogManager();
        $logger = $logManager->getLogger();
        
        $this->assertInstanceOf(RemoteLogger::class, $logger);
    }

    public function test_returns_composite_logger_when_mode_is_both(): void
    {
        config(['logging.mode' => 'both']);
        
        $mockPublisher = Mockery::mock(MessagePublisherInterface::class);
        $this->app->instance(MessagePublisherInterface::class, $mockPublisher);
        
        $logManager = new LogManager();
        $logger = $logManager->getLogger();
        
        $this->assertInstanceOf(CompositeLogger::class, $logger);
    }

    public function test_defaults_to_local_logger_when_mode_is_invalid(): void
    {
        config(['logging.mode' => 'invalid']);
        
        $logManager = new LogManager();
        $logger = $logManager->getLogger();
        
        $this->assertInstanceOf(LocalLogger::class, $logger);
    }

    public function test_log_methods_delegate_to_underlying_logger(): void
    {
        config(['logging.mode' => 'local']);
        
        $mockLogger = Mockery::mock(LoggerInterface::class);
        $mockLogger->shouldReceive('info')->once()->with('test message', []);
        $mockLogger->shouldReceive('error')->once()->with('error message', []);
        $mockLogger->shouldReceive('warning')->once()->with('warning message', []);
        $mockLogger->shouldReceive('debug')->once()->with('debug message', []);
        
        // We need to mock the getLogger method to return our mock
        $logManager = Mockery::mock(LogManager::class)->makePartial();
        $logManager->shouldReceive('getLogger')->andReturn($mockLogger);
        
        $logManager->info('test message');
        $logManager->error('error message');
        $logManager->warning('warning message');
        $logManager->debug('debug message');
    }

    public function test_log_methods_with_context(): void
    {
        config(['logging.mode' => 'local']);
        
        $context = ['key' => 'value'];
        
        $mockLogger = Mockery::mock(LoggerInterface::class);
        $mockLogger->shouldReceive('info')->once()->with('test message', $context);
        
        $logManager = Mockery::mock(LogManager::class)->makePartial();
        $logManager->shouldReceive('getLogger')->andReturn($mockLogger);
        
        $logManager->info('test message', $context);
    }
}
