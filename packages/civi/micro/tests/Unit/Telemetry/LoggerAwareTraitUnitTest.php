<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry\Tests;

use Civi\Micro\Telemetry\LoggerAwareTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Civi\Micro\Telemetry\LoggerAwareTrait
 */
class LoggerAwareTraitUnitTest extends TestCase
{
    private $loggerAware;
    private $mockLogger;

    protected function setUp(): void
    {
        $this->loggerAware = new TestLoggerAware();
        $this->mockLogger = $this->createMock(LoggerInterface::class);
    }

    public function testSetLogger(): void
    {
        $this->loggerAware->setLogger($this->mockLogger);
        $this->assertSame($this->mockLogger, $this->loggerAware->getLogger());
    }

    public static function logLevelProvider(): array
    {
        return [
            ['logEmergency', 'emergency'],
            ['logAlert', 'alert'],
            ['logCritical', 'critical'],
            ['logError', 'error'],
            ['logWarning', 'warning'],
            ['logNotice', 'notice'],
            ['logInfo', 'info'],
            ['logDebug', 'debug'],
        ];
    }

    #[DataProvider('logLevelProvider')]
    public function testLogMethodsWithLogger(string $method, string $logLevel): void
    {
        $this->loggerAware->setLogger($this->mockLogger);
        $message = 'Test message';
        $context = ['key' => 'value'];

        $this->mockLogger
            ->expects($this->once())
            ->method($logLevel)
            ->with($message, $context);

        $this->loggerAware->{$method}($message, $context);
    }

    #[DataProvider('logLevelProvider')]
    public function testLogMethodsFallbackWithoutLogger(string $method, string $logLevel): void
    {
        $message = 'Fallback test';
        $context = ['info' => 'test'];

        // Capturamos el output de error_log
        $this->expectOutputRegex('/' . strtoupper($logLevel) . ': ' . preg_quote($message, '/') . '.*"info":"test"/');

        // error_log escribe en STDERR por defecto, redirigimos para capturarlo
        ob_start();
        $this->loggerAware->{$method}($message, $context);
        ob_end_flush();
    }
}

/**
 * Dummy class to test LoggerAwareTrait
 */
class TestLoggerAware
{
    use LoggerAwareTrait;

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }
}
