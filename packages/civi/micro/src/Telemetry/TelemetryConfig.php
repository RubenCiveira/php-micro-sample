<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry;

use Civi\Micro\ProjectLocator;
use Monolog\Level;
use Monolog\Logger;

class TelemetryConfig
{
    public readonly string $logFile;
    public readonly Level $logLevel;
    public readonly string $metricsFile;
    public readonly string $appName;
    public readonly string $enviroment;
    public readonly string $appNamespace;
    public readonly string $appVersion;

    public function __construct(
        ?string $logPath,
        ?string $logLevel
    ) {
        $this->logFile = ProjectLocator::getRootPath() . '/var/log/app.log';
        $this->logLevel = Level::Debug;
        $this->metricsFile = ProjectLocator::getRootPath() . '/var/metrics.json';

        $this->appName = 'app.name';
        $this->appNamespace = 'civi';
        $this->appVersion = '1.0.0';
        $this->enviroment = 'dev';
    }
}
