<?php

declare(strict_types=1);

namespace Civi\Micro\Telemetry\Helper;

use Civi\Micro\AppConfig;
use Civi\Micro\Telemetry\TelemetryConfig;
use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\Histogram;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;
use Slim\Routing\Route;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Routing\RouteParser;
use Slim\Routing\RoutingResults;

class SlimMetricMiddlewareUnitTest extends TestCase
{
    private $middleware;
    private $registry;

    protected function setUp(): void
    {
        $appConfig = new AppConfig('/management');

        $config = new TelemetryConfig('', '');

        $this->registry = $this->createMock(CollectorRegistry::class);

        $this->middleware = new SlimMetricMiddleware($appConfig, $config, $this->registry);
    }

    public function testInvokeSkipsManagementPath(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(Response::class);
        $response->method('getStatusCode')->willReturn(200); // aunque no se usa realmente porque se salta métricas

        $route = $this->createMock(Route::class);
        $route->method('getPattern')->willReturn('/management/health');
        $routeParser = $this->createMock(RouteParser::class);
        $routingResults = $this->createMock(RoutingResults::class);

        // Crear un Request real y añadir el atributo 'route'
        $request = (new \Slim\Psr7\Factory\ServerRequestFactory())
            ->createServerRequest('GET', '/management/health')
            ->withAttribute(RouteContext::ROUTE, $route)
            ->withAttribute(RouteContext::ROUTE_PARSER, $routeParser)
            ->withAttribute(RouteContext::ROUTING_RESULTS, $routingResults);
        ;

        $handler->expects($this->once())->method('handle')->willReturn($response);

        $result = $this->middleware->__invoke($request, $handler);

        $this->assertSame($response, $result);
    }

    public function testInvokeHandlesNormalPathAndAllStatusCodes(): void
    {
        $statuses = [200, 401, 403, 404, 500, 502];

        foreach ($statuses as $status) {
            $handler = $this->createMock(RequestHandlerInterface::class);
            $response = $this->createMock(Response::class);
            $response->method('getStatusCode')->willReturn($status);

            $route = $this->createMock(Route::class);
            $route->method('getPattern')->willReturn('/normal/path');
            $routeParser = $this->createMock(RouteParser::class);
            $routingResults = $this->createMock(RoutingResults::class);
    
            // Crear Request real con el atributo 'route'
            $request = (new \Slim\Psr7\Factory\ServerRequestFactory())
                ->createServerRequest('GET', '/normal/path')
                ->withAttribute(RouteContext::ROUTE, $route)
                ->withAttribute(RouteContext::ROUTE_PARSER, $routeParser)
                ->withAttribute(RouteContext::ROUTING_RESULTS, $routingResults);

            $handler->expects($this->once())->method('handle')->willReturn($response);

            // Set expectations for metrics registration based on status
            $this->mockMetricRegistrations($status);

            $result = $this->middleware->__invoke($request, $handler);
            $this->assertSame($response, $result);
        }
    }

    private function mockMetricRegistrations(int $statusCode): void
    {
        $this->registry->expects($this->atLeastOnce())
            ->method('getOrRegisterGauge')
            ->willReturn($this->createMock(Gauge::class));

        $this->registry->expects($this->atLeastOnce())
            ->method('getOrRegisterHistogram')
            ->willReturn($this->createMock(Histogram::class));

        $this->registry->expects($this->atLeastOnce())
            ->method('getOrRegisterCounter')
            ->willReturn($this->createMock(Counter::class));
    }
}
