<?php

declare(strict_types=1);

namespace Civi\Micro;

use DI\Container;
use Slim\App;
use Slim\Factory\AppFactory;

class AppBuilder
{
    private static array $views = [];
    private static array $routes = [];
    private static array $dependencies = [];
    

    public static function registerView(string $app, string $name, string $path): bool
    {
        self::$views[$app][$name] = $path;
        return true;
    }

    public static function getViews(string $app) {
        return array_reverse( self::$views[$app], true);
    }

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
        $container = new Container();
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
}
