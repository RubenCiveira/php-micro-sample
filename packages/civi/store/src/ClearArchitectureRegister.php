<?php declare(strict_types=1);

namespace Civi\Store;

use Civi\Micro\ProjectLocator;
use DI\Container;

class ClearArchitectureRegister
{
    public static function mappers(Container $container, $name, $type)
    {
        $array = [];
        self::scanRegister($array, $name, $type);
        foreach($array as $k=>$v) {
            $container->set($k, $v);
        }
    }

    public static function scanRegister(array &$container, $name, $type)
    {
        // 1. Registrar la clase principal del tipo
        $container[$name] = $type;

        // 2. Intentar registrar el Filter si existe
        $filterClass = self::buildFilterClass($type);
        if (class_exists($filterClass)) {
            $container["{$name}Filter"] = $filterClass;
        }

        // 3. Buscar la ruta base donde está definida la clase (por Composer)
        $basePath = self::resolveClassPath($type);

        if (!$basePath || !is_dir($basePath)) {
            return;
        }

        // 4. Buscar en subdirectorios Rule/ y Policy/
        foreach (['Rule', 'Policy', 'Trigger'] as $subDir) {
            $path = $basePath . DIRECTORY_SEPARATOR . $subDir;
            if (is_dir($path)) {
                $classes = self::findClassesInPath($path, self::getNamespacePrefix($type) . "\\$subDir");
                foreach ($classes as $class) {
                    self::registerHandler($container, $name, $class);
                }
            }
        }
    }

    private static function registerHandler(array &$container, string $name, string $class): void
    {
        $basename = (new \ReflectionClass($class))->getShortName();

        preg_match_all('/[A-Z][a-z0-9]*/', $basename, $matches);
        $suffix = end($matches[0]);
        
        $tag = "{$name}{$suffix}";
        $prev = $container[$tag] ?? [];
        $container[$tag] = array_merge([$class], $prev);
    }

    private static function buildFilterClass(string $type): string
    {
        $parts = explode('\\', $type);
        $className = array_pop($parts);
        return implode('\\', [...$parts, 'Query', $className . 'Filter']);
    }

    private static function resolveClassPath(string $fqcn): ?string
    {
        $composerMap = require ProjectLocator::getRootPath() . '/vendor/composer/autoload_psr4.php';
        
        foreach ($composerMap as $namespace => $paths) {
            if (str_starts_with($fqcn, $namespace)) {
                $relative = str_replace('\\', '/', substr($fqcn, strlen($namespace)));
                $relative = preg_replace('/[^\/]+$/', '', $relative); // quitar nombre de clase
                return rtrim($paths[0], '/') . '/' . $relative;
            }
        }
        return null;
    }

    private static function getNamespacePrefix(string $fqcn): string
    {
        return implode('\\', array_slice(explode('\\', $fqcn), 0, -1));
    }

    private static function findClassesInPath(string $path, string $namespace): array
    {
        $classes = [];
        foreach (glob($path . '/*.php') as $file) {
            $class = $namespace . '\\' . basename($file, '.php');
            if (class_exists($class)) {
                $classes[] = $class;
            }
        }
        return $classes;
    }
}