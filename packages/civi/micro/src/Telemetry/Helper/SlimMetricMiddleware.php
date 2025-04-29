<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry\Helper;

use Civi\Micro\AppConfig;
use Civi\Micro\Telemetry\TelemetryConfig;
use Prometheus\CollectorRegistry;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

/**
 * Middleware to record application metrics for each HTTP request.
 * 
 * It captures memory usage, CPU load, request duration, and HTTP status codes,
 * and registers these metrics in a Prometheus CollectorRegistry.
 */
class SlimMetricMiddleware
{
    /**
     * @api
     * 
     * @param AppConfig $appConfig Configuration for application paths and management endpoints
     * @param TelemetryConfig $config Telemetry-specific configuration
     * @param CollectorRegistry $registry Prometheus registry where metrics are collected
     */
    public function __construct(
        private readonly AppConfig $appConfig,
        private readonly TelemetryConfig $config,
        private readonly CollectorRegistry $registry
    ) {
    }

    /**
     * Handles the incoming request, collects and registers metrics if the path is not a management endpoint.
     *
     * @param Request $request The incoming HTTP request
     * @param RequestHandlerInterface $handler The next request handler
     * @return Response The HTTP response after metric collection
     */
    public function __invoke(Request $request, RequestHandlerInterface $handler): Response
    {
        $start = microtime(true);
        $routeContext = RouteContext::fromRequest($request);
        $response = $handler->handle($request);
        $route = $routeContext->getRoute();
        $path = $route->getPattern();
        if (!$this->isManagementPath($path)) {
            $this->serverLoad($path);
            $this->executionTime($path, $start);
            $this->httpStatus($path, $response->getStatusCode());
        }
        return $response;
    }

    /**
     * Determines whether the given path is a management endpoint.
     *
     * @param string $path
     * @return bool
     */
    private function isManagementPath($path)
    {
        return str_starts_with($path, $this->appConfig->managementEndpoint);
    }

    /**
     * Records memory usage and CPU load for the given path.
     *
     * @param string $path
     */
    private function serverLoad($path)
    {
        // Obtener el uso de memoria actual en bytes
        $memoryUsage = memory_get_usage();

        // Obtener la carga de CPU usando getrusage()
        $cpuUsage = getrusage();
        $userTime = $cpuUsage["ru_utime.tv_sec"] + $cpuUsage["ru_utime.tv_usec"] / 1e6; // Tiempo de usuario en segundos
        $systemTime = $cpuUsage["ru_stime.tv_sec"] + $cpuUsage["ru_stime.tv_usec"] / 1e6; // Tiempo de sistema en segundos
        $totalCpuLoad = $userTime + $systemTime;

        // Registrar el Gauge para el uso de memoria
        $memoryUsageGauge = $this->registry->getOrRegisterGauge(
            $this->namespace(),         // Namespace
            'memory_usage_bytes',     // Nombre de la métrica
            'Uso de memoria en bytes', // Descripción
            ['path']                // Etiquetas opcionales, como el nombre del script
        );
        $memoryUsageGauge->set($memoryUsage, [$path]); // Ajustar el uso de memoria
        // Registrar el Gauge para la carga de CPU
        $cpuLoadGauge = $this->registry->getOrRegisterGauge(
            $this->namespace(),   // Namespace
            'cpu_load',         // Nombre de la métrica
            'Carga del procesador actual', // Descripción
            ['path']
        );
        // Ajustar la carga de CPU
        $cpuLoadGauge->set($totalCpuLoad, [$path]);
    }

    /**
     * Records the execution time for the given path.
     *
     * @param string $path
     * @param float $executionTime
     */
    private function executionTime($path, $executionTime)
    {
        $histogram = $this->registry->getOrRegisterHistogram(
            $this->namespace(),
            'request_duration_seconds',
            'Tiempo de ejecución del script',
            ['script'],
            [0.03, 0.07, 0.1, 0.5, 1, 5, 10]
        );
        $histogram->observe($executionTime, [$path]);
    }

    /**
     * Records HTTP status codes and categorizes them for telemetry.
     *
     * @param string $path
     * @param int $status
     */
    private function httpStatus($path, $status)
    {
        // Crear y registrar los contadores
        $httpStatusCounter = $this->registry->getOrRegisterCounter(
            $this->namespace(),    // Namespace
            'http_status_codes', // Nombre de la métrica
            'Contador de códigos de estado HTTP', // Descripción
            ['status', 'path']   // Etiquetas: código de estado y ruta
        );
        // Incrementar el contador general de códigos de estado
        $httpStatusCounter->incBy(1, [$status, $path]);
        // Incrementar contadores específicos según el código de estado
        if ($status >= 200 && $status < 300) {
            // Crear contadores adicionales para éxitos y errores
            $successCounter = $this->registry->getOrRegisterCounter(
                $this->namespace(),
                'http_success',
                'Contador de respuestas exitosas (2xx)',
                ['path']
            );
            $successCounter->incBy(1, [$path]); // Incrementa el contador de éxitos
        } elseif ($status >= 400 && $status < 500) {
            $error4xxCounter = $this->registry->getOrRegisterCounter(
                $this->namespace(),
                'http_4xx_errors',
                'Contador de errores del cliente (4xx)',
                ['path']
            );
            $error4xxCounter->incBy(1, [$path]); // Incrementa el contador de errores 4xx
        } elseif ($status >= 500 && $status < 600) {
            $error5xxCounter = $this->registry->getOrRegisterCounter(
                $this->namespace(),
                'http_5xx_errors',
                'Contador de errores del servidor (5xx)',
                ['path']
            );
            $error5xxCounter->incBy(1, [$path]); // Incrementa el contador de errores 5xx
        }
        if ($status == 401) {
            $this->unauthorized($path);
        } elseif ($status == 403) {
            $this->forbidden($path);
        } elseif ($status == 502) {
            $this->bad_gateway($path);
        }
    }

    /**
     * Records unauthorized (401) HTTP errors.
     *
     * @param string $path
     */
    private function unauthorized($path)
    {
        // Crear y registrar los contadores para errores de seguridad
        $error401Counter = $this->registry->getOrRegisterCounter(
            $this->namespace(),
            'http_401_errors',
            'Contador de errores de autenticación (401)',
            ['path']
        );
        $error401Counter->incBy(1, [$path]);
    }

    /**
     * Records forbidden (403) HTTP errors.
     *
     * @param string $path
     */
    private function forbidden($path)
    {
        $error403Counter = $this->registry->getOrRegisterCounter(
            $this->namespace(),
            'http_403_errors',
            'Contador de errores de autorización (403)',
            ['path']
        );
        $error403Counter->incBy(1, [$path]);
    }

    /**
     * Records bad gateway (502) HTTP errors.
     *
     * @param string $path
     */
    private function bad_gateway($path)
    {
        $error502Counter = $this->registry->getOrRegisterCounter(
            $this->namespace(),
            'http_502_errors',
            'Contador de errores de llamada remota (502)',
            ['path']
        );
        $error502Counter->incBy(1, [$path]);
    }

    /**
     * Returns a valid namespace for metrics on prometeus, replacing dots with underscores.
     *
     * @return string
     */
    private function namespace()
    {
        return str_replace('.', '_', $this->config->appName);
    }
}
