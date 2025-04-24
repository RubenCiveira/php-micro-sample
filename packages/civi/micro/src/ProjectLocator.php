<?php declare(strict_types=1);

namespace Civi\Micro;

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
}