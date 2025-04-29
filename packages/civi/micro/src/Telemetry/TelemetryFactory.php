<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry;

use Civi\Micro\Telemetry\Helper\PrometeusFileStorage;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Prometheus\CollectorRegistry;
use Psr\Log\LoggerInterface;

class TelemetryFactory
{
    /**
     * @api
     */
    public function __construct(private readonly TelemetryConfig $config)
    {
    }

    public function logger(): LoggerInterface
    {
        $logger = new Logger($this->config->appName);
        $logger->pushHandler(new StreamHandler($this->config->logFile, $this->config->logLevel));
        return $logger;
    }

    public function metrics(): CollectorRegistry
    {
        $storage = new PrometeusFileStorage($this->config->metricsFile);
        return new CollectorRegistry($storage);
    }
}
