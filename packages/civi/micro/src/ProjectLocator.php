<?php

declare(strict_types=1);

namespace Civi\Micro;

/**
 * Class ProjectLocator
 *
 * Utility class to locate the root path of a PHP project by searching for common project markers
 * like `vendor/autoload.php` or `composer.json`.
 */
class ProjectLocator
{
    /**
     * @var string|bool|null The cached root path once discovered. Can be a string or boolean (false if not found).
     */
    private static $rootPath;
    private static $innerAutoload = __DIR__ . '/../../vendor/';
    private static $vendorAutoload = __DIR__ . '/../../../../';

    /**
     * Returns the root path of the project.
     *
     * If the root path has already been determined, it returns the cached value.
     * Otherwise, it searches for the root path and caches it.
     *
     * @return string The absolute path to the project root. Returns an empty string if no valid path was found.
     */
    public static function getRootPath(): string
    {
        if (!self::$rootPath) {
            self::$rootPath = self::searchRootPath();
        }
        return is_bool(self::$rootPath) ? '' : self::$rootPath;
    }

    /**
     * Return a path to store compiled file to pre cache code optimizations based on configurations.
     * 
     * @return ?string If the app can store cache code files, the absolute path to the directory. Return null if no valid path was found
     */
    public static function getCompiledPath(): ?string
    {
        $root = self::getRootPath();
        return $root ? $root . '/.cache' : null;
    }
    /**
     * Searches for the root path of the project.
     *
     * This method attempts to locate the root by:
     *  - Checking for an internal vendor/autoload.php.
     *  - Checking for an external autoload.php.
     *  - Traversing upwards from the current working directory to find a composer.json.
     *
     * @return string|bool The absolute path to the project root, or false if not found.
     */
    private static function searchRootPath(): string|bool
    {
        $innerAutoload = self::$innerAutoload;
        $vendorAutoload = self::$vendorAutoload;
        if (file_exists("{$innerAutoload}/autoload.php")) {
            return realpath(dirname($innerAutoload));
        } elseif (file_exists("{$vendorAutoload}/autoload.php")) {
            return realpath(dirname($vendorAutoload));
        }
        $pwd = getcwd();
        while ($pwd && !file_exists("$pwd/composer.json")) {
            $parent = dirname($pwd);
            if ($parent === $pwd) {
                // We have reached the root directory, break to prevent infinite loop
                return false;
            }
            $pwd = $parent;
        }
        return $pwd;
    }
}
