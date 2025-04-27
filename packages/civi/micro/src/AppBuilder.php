<?php

declare(strict_types=1);

namespace Civi\Micro;

use Civi\Micro\Management\HealthManagement;
use Civi\Micro\Management\HealthProviderInterface;
use Civi\Micro\Management\ManagementInterface;
use Civi\Micro\Management\MetricsManagement;
use Civi\Micro\Middleware\GzipMiddleware;
use Civi\Micro\Telemetry\Helper\SlimMetricMiddleware;
use Civi\Micro\Telemetry\LoggerAwareInterface;
use Civi\Micro\Telemetry\MetricAwareInterface;
use Civi\Micro\Telemetry\TelemetryConfig;
use Civi\Micro\Telemetry\TelemetryFactory;
use DI\Container;
use DI\ContainerBuilder;
use Prometheus\CollectorRegistry;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Factory\AppFactory;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class AppBuilder
{
    private static array $routes = [];
    private static array $dependencies = [];
    
    public static function dependencies(string $file)
    {
        self::$dependencies[] = $file;
    }

    public static function routes(string $file)
    {
        self::$routes[] = $file;
    }
    public static function buildApp(): App
    {
        $root = ProjectLocator::getRootPath();
        $builder = new ContainerBuilder();
        $builder->useAutowiring(true);
        self::standarContext($builder);
        $container = $builder->build();
        foreach (self::$dependencies as $dep) {
            $di = require $dep;
            $di($container);
        }
        if (!in_array("$root/dependencies.php", self::$dependencies) && file_exists("$root/dependencies.php")) {
            $di = require "$root/dependencies.php";
            $di($container);
        }
        AppFactory::setContainer($container);
        $app = AppFactory::create();
        $scriptName = $_SERVER['SCRIPT_NAME']; // Devuelve algo como "/midashboard/index.php"
        $basePath = str_replace('/index.php', '', $scriptName); // "/midashboard"
        $app->setBasePath($basePath);

        // Middleware para parsear json
        $app->addBodyParsingMiddleware();
        $app->add(GzipMiddleware::class);
        $app->add(SlimMetricMiddleware::class);
        // $app->add( CorsMiddeleware::class );
        $app->addRoutingMiddleware();
        
        // Los registros de management
        $appConfig = $container->get(AppConfig::class);
        $base = $appConfig->managementEndpoint;
        $interfaces = $container->get(ManagementInterface::class);
        foreach($interfaces as $interface) {
            $name = $interface->name();
            $get = $interface->get();
            if( $get ) {
                $app->get("{$base}/{$name}", function(Request $request, Response $response) use ($get) {
                    $value = $get();
                    if( is_string($value) ) {
                        $response->getBody()->write($value);
                        return $response->withHeader('Content-Type', 'text/plain');    
                    } else {
                        $response->getBody()->write(json_encode($value));
                        return $response->withHeader('Content-Type', 'application/json');
                    }
                });
            }
            $set = $interface->set();
            if( $set ) {
                $app->post("{$base}/{$name}", function(Request $request, Response $response) use ($set) {
                    $data = $request->getParsedBody();
                    $value = $set($data);
                    if( is_string($value) ) {
                        $response->getBody()->write($value);
                        return $response->withHeader('Content-Type', 'text/plain');    
                    } else {
                        $response->getBody()->write(json_encode($value));
                        return $response->withHeader('Content-Type', 'application/json');
                    }
                });
            }
        }

        foreach (self::$routes as $route) {
            $routes = require $route;
            $routes($app);
        }
        if (!in_array("$root/routes.php", self::$routes) && file_exists("$root/routes.php")) {
            $routes = require "$root/routes.php";
            $routes($app);
        }
        $container->set(App::class, \DI\value($app));
        return $app;
    }

    private static function standarContext(ContainerBuilder $builder)
    {
        $builder->addDefinitions([
            CollectorRegistry::class => \DI\factory(function(TelemetryFactory $factory) {
                return $factory->metrics();
            }),
            AppConfig::class => \DI\factory(function () {
                return Config::load('app.server', AppConfig::class, 'application');
            }),
            TelemetryConfig::class => \DI\factory(function () {
                return Config::load('app.telemetry', TelemetryConfig::class, 'application');
            }),
            LoggerInterface::class => \DI\factory(function(TelemetryFactory $factory) {
                return $factory->logger();
            }),
            HealthProviderInterface::class => [],
            ManagementInterface::class => [\DI\get(HealthManagement::class)],
            HealthManagement::class => \DI\factory(function(Container $container) {
                $interfaces = $container->get(HealthProviderInterface::class);
                return new HealthManagement( $interfaces ?? [] );
            }),
            MetricAwareInterface::class => \DI\autowire()
                 ->method('setMetricRegistry', \DI\get(CollectorRegistry::class)),
            LoggerAwareInterface::class => \DI\autowire()
                ->method('setLogger', \DI\get(LoggerInterface::class)),
            // TracerAwareInterface::class => \DI\autowire()
            //     ->method('setTracer', \DI\get(Logger::class)),
        ]);

        $builder->addDefinitions([
            ManagementInterface::class => \DI\add(\DI\get(MetricsManagement::class)),
        ]);
    }
}
