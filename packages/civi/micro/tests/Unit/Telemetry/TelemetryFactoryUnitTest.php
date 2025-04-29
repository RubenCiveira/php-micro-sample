<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry\Tests;

use Civi\Micro\Telemetry\TelemetryConfig;
use Civi\Micro\Telemetry\TelemetryFactory;
use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Psr\Log\LoggerInterface;

/**
 * @covers \Civi\Micro\Telemetry\TelemetryFactory
 */
class TelemetryFactoryUnitTest extends TestCase
{
    private TelemetryConfig $config;
    private TelemetryFactory $factory;

    protected function setUp(): void
    {
        $logPath = sys_get_temp_dir() . '/test_app.log';
        $metricsPath = sys_get_temp_dir() . '/test_metrics.json';

        $this->config = new TelemetryConfig($logPath, $metricsPath);
        $this->factory = new TelemetryFactory($this->config);

        // Ensure files don't exist before test
        if (file_exists($logPath)) {
            unlink($logPath);
        }
        if (file_exists($metricsPath)) {
            unlink($metricsPath);
        }
    }

    public function testLoggerReturnsLoggerInstance(): void
    {
        $logger = $this->factory->logger();

        $this->assertInstanceOf(LoggerInterface::class, $logger);

        // Assert that $logger is also a Monolog\Logger to satisfy the IDE and allow getName()
        $this->assertInstanceOf(\Monolog\Logger::class, $logger);
        if( is_a($logger, \Monolog\Logger::class) ) {
            $this->assertSame($this->config->appName, $logger->getName());
        }

        // Optional: trigger a real log entry to ensure the StreamHandler works
        $logger->info('Test log entry');
        $this->assertFileExists($this->config->logFile);
        $this->assertStringContainsString('Test log entry', file_get_contents($this->config->logFile));
    }

    public function testMetricsReturnsCollectorRegistryInstance(): void
    {
        $registry = $this->factory->metrics();

        $this->assertInstanceOf(CollectorRegistry::class, $registry);

        // Optional: Check that storage inside the registry is PrometeusFileStorage (not directly exposed, internal)
        $reflection = new \ReflectionClass($registry);
        $property = $reflection->getProperty('storageAdapter');
        $property->setAccessible(true);
        $storage = $property->getValue($registry);

        $this->assertInstanceOf(\Civi\Micro\Telemetry\Helper\PrometeusFileStorage::class, $storage);
    }
}
