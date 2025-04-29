<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry;

use Civi\Micro\ProjectLocator;
use Monolog\Level;

/**
 * TelemetryConfig provides configuration settings related to telemetry,
 * including log file location, log level, metrics storage file, application
 * name, environment, namespace, and version.
 *
 * @api
 */
class TelemetryConfig
{
    /**
     * Path to the application's log file.
     *
     * If not provided, defaults to "<project-root>/var/log/app.log".
     */
    public readonly string $logFile;
    /**
     * Minimum logging level.
     *
     * Always set to Level::Debug by default.
     */
    public readonly Level $logLevel;
    /**
     * Path to the application's metrics storage file.
     *
     * If not provided, defaults to "<project-root>/var/metrics.json".
     */
    public readonly string $metricsFile;
    /**
     * The name of the application.
     *
     * Defaults to "app.name".
     */
    public readonly string $appName;
    /**
     * The application environment (e.g., "dev", "prod").
     *
     * Defaults to "dev".
     */
    public readonly string $enviroment;
    /**
     * The namespace used by the application for telemetry identification.
     *
     * Defaults to "civi".
     */
    public readonly string $appNamespace;

    /**
     * The application version string.
     *
     * Defaults to "1.0.0".
     */
    public readonly string $appVersion;

    /**
     * Initializes telemetry configuration settings.
     *
     * @param string|null $logPath   Custom path for the log file. If null, uses the default.
     * @param string|null $logLevel  Custom path for the metrics file. If null, uses the default.
     */
    public function __construct(
        ?string $logPath,
        ?string $logLevel
    ) {
        $this->logFile = $logPath ?? ProjectLocator::getRootPath() . '/var/log/app.log';
        $this->logLevel = Level::Debug;
        $this->metricsFile = $logLevel ?? ProjectLocator::getRootPath() . '/var/metrics.json';

        $this->appName = 'app.name';
        $this->appNamespace = 'civi';
        $this->appVersion = '1.0.0';
        $this->enviroment = 'dev';
    }
}
