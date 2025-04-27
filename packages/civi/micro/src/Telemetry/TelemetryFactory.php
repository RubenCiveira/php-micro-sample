<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry;

use Civi\Micro\Telemetry\Helper\PrometeusFileStorage;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SemConv\ResourceAttributes;
use Prometheus\CollectorRegistry;
use Psr\Log\LoggerInterface;

class TelemetryFactory
{
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
