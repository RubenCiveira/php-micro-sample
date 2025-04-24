<?php declare(strict_types=1);

namespace Civi\Micro;

use DI\Container;
use Slim\App;
use Slim\Factory\AppFactory;

class ProjectLocator
{
    public static $rootPath;
    public static array $routes = [];
    public static array $dependencies = [];
    public static function getRootPath(): string
    {
        if (!self::$rootPath) {
            self::$rootPath = self::searchRootPath();
        }
        return self::$rootPath;
    }
    private static function searchRootPath(): string
    {
        $innerAutoload = __DIR__ . '/../../vendor/';
        $vendorAutolad = __DIR__ . '/../../../../autoload.php';
        if (file_exists("{$innerAutoload}autoload.php")) {
            return realpath(dirname($innerAutoload));
        } else if (file_exists("{$vendorAutolad}autoload.php")) {
            return realpath(dirname($vendorAutolad));
        }
        $pwd = getcwd();
        while( $pwd && !file_exists("$pwd/composer.json") ) {
            $pwd = dirname($pwd);
        }
        return $pwd;
    }

    public static function registerDi(string $file)
    {
    }

    public static function buildApp(): App
    {
        $root = self::getRootPath();
        $container = new Container();
        foreach(self::$dependencies as $dep) {
            $di = require $dep;
            $di( $container );
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
        foreach(self::$routes as $route) {
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

    public static function buildApplication(App $app)
    {
        $root = self::getRootPath();
    }
}