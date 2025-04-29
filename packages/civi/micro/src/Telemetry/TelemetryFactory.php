<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry;

use Civi\Micro\Telemetry\Helper\PrometeusFileStorage;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Prometheus\CollectorRegistry;
use Psr\Log\LoggerInterface;

/**
 * Factory class for creating telemetry-related components such as loggers and metrics collectors.
 *
 * This class centralizes the creation of common telemetry services used across the application,
 * based on the configuration provided via {@see TelemetryConfig}.
 *
 * @api
 */
class TelemetryFactory
{
    /**
     * Initializes the factory with the given telemetry configuration.
     * @api
     *
     * @param TelemetryConfig $config Configuration object containing paths, app name, and other telemetry settings.
     */
    public function __construct(private readonly TelemetryConfig $config)
    {
    }

    /**
     * Creates and returns a logger instance configured for the application.
     *
     * The logger uses a {@see StreamHandler} to write log entries to the file defined in {@see TelemetryConfig::$logFile},
     * at the minimum level specified in {@see TelemetryConfig::$logLevel}.
     *
     * @return LoggerInterface Configured Monolog logger ready for use.
     */
    public function logger(): LoggerInterface
    {
        $logger = new Logger($this->config->appName);
        $logger->pushHandler(new StreamHandler($this->config->logFile, $this->config->logLevel));
        return $logger;
    }

    /**
     * Creates and returns a metrics collector registry configured to use file-based storage.
     *
     * The returned {@see CollectorRegistry} instance uses a {@see PrometeusFileStorage}
     * that writes metric samples to the file defined in {@see TelemetryConfig::$metricsFile}.
     *
     * @return CollectorRegistry Metrics collector registry using Prometheus file storage.
     */
    public function metrics(): CollectorRegistry
    {
        $storage = new PrometeusFileStorage($this->config->metricsFile);
        return new CollectorRegistry($storage);
    }
}
