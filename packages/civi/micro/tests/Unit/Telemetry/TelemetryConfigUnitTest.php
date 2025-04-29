<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry\Tests;

use Civi\Micro\Telemetry\TelemetryConfig;
use Civi\Micro\ProjectLocator;
use Monolog\Level;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\Micro\Telemetry\TelemetryConfig
 */
class TelemetryConfigUnitTest extends TestCase
{
    public function testConstructorWithNullParameters(): void
    {
        $config = new TelemetryConfig(null, null);

        $this->assertStringEndsWith('/var/log/app.log', $config->logFile);
        $this->assertEquals(Level::Debug, $config->logLevel);
        $this->assertStringEndsWith('/var/metrics.json', $config->metricsFile);
        $this->assertSame('app.name', $config->appName);
        $this->assertSame('civi', $config->appNamespace);
        $this->assertSame('1.0.0', $config->appVersion);
        $this->assertSame('dev', $config->enviroment);
    }

    public function testConstructorWithCustomParameters(): void
    {
        $customLogPath = '/custom/path/to/logfile.log';
        $customMetricsPath = '/custom/path/to/metrics.json';

        $config = new TelemetryConfig($customLogPath, $customMetricsPath);

        $this->assertSame($customLogPath, $config->logFile);
        $this->assertEquals(Level::Debug, $config->logLevel);
        $this->assertSame($customMetricsPath, $config->metricsFile);
        $this->assertSame('app.name', $config->appName);
        $this->assertSame('civi', $config->appNamespace);
        $this->assertSame('1.0.0', $config->appVersion);
        $this->assertSame('dev', $config->enviroment);
    }
}
