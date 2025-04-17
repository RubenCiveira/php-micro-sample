<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared;

use Symfony\Component\Yaml\Yaml;
use Dotenv\Dotenv;
use ReflectionClass;
use ReflectionNamedType;

class Config
{
    private array $configData = [];
    private array $loadedFiles = [];

    public function __construct(private string $configPath, private string $envPath, $file)
    {
        $this->loadEnv();
        $this->loadYamlFiles($file);
    }

    public static function load(string $prefix, string $className, string $file): object
    {
        $instance = new self('../config', '../', $file);
        return $instance->build($prefix, $className);
    }
    
    public function build($prefix, $className) {
        $data = $this->getFlatConfig($prefix);
        return $this->instantiate($className, $data);
    }

    private function loadEnv(): void
    {
        $dotenv = Dotenv::createMutable($this->envPath);
        if (file_exists("{$this->envPath}/.env")) {
            $dotenv->load();
            $this->loadedFiles[] = "{$this->envPath}/.env";
        }

        $profile = $_ENV['PROFILE'] ?? $_SERVER['PROFILE'] ?? null;
        if ($profile && file_exists("{$this->envPath}/.env.$profile")) {
            $dotenv = Dotenv::createMutable($this->envPath, [".env.$profile"]);
            $readed = $dotenv->load();
            foreach($readed as $k=>$v) {
                $_ENV[$k] = $v;
            }
            $this->loadedFiles[] = "{$this->envPath}/.env.$profile";
        }
    }

    private function loadYamlFiles(string $fileName): void
    {
        $this->configData = [];

        $files = ["{$fileName}.yaml"];

        $profile = $_ENV['PROFILE'] ?? $_SERVER['PROFILE'] ?? null;

        if ($profile && file_exists("{$this->configPath}/{$fileName}.$profile.yaml")) {
            $files[] = "{$fileName}.$profile.yaml";
        }

        foreach ($files as $file) {
            $fullPath = "{$this->configPath}/$file";
            if (file_exists($fullPath)) {
                $data = Yaml::parse(file_get_contents($fullPath) . "\n\n");
                $this->configData = array_merge_recursive($this->configData, $data ?? []);
                $this->loadedFiles[] = $fullPath;
            }
        }
        $this->configData = $this->flatten($this->configData);
    }

    private function flatten(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            // Normalizamos el nombre de la clave: minúsculas y puntos
            $normalizedKey = strtolower(str_replace(['_', '-'], '.', $this->camelToDotNotation( (string) $key)) );
            $fullKey = $prefix ? "$prefix.$normalizedKey" : $normalizedKey;

            if (is_array($value) && array_keys($value) !== range(0, count($value) - 1)) {
                // Si es array asociativo, continuar recursión
                $result += $this->flatten($value, $fullKey);
            } else if (is_array($value) ) {
                $replaced = [];
                foreach($value as $v) {
                    $replaced[] = $this->resolveEnvVar($v);
                }
                $result[$fullKey] = $replaced;
            } else {
                // Si es array indexado o valor simple, asignar directamente
                $result[$fullKey] = $this->resolveEnvVar($value);;
            }
        }

        return $result;
    }

    private function camelToDotNotation(string $input): string 
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '.$0', $input));
    }

    private function resolveEnvVar(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^%env\((.+?)\)%$/', $value, $matches)) {
            return $_ENV[$matches[1]] ?? $_SERVER[$matches[1]] ?? null;
        }
        return $value;
    }

    private function getFlatConfig(string $prefix): array
    {
        $data = [];
        $prefix = strtolower($prefix);
        foreach ($this->configData as $key => $value) {
            if (str_starts_with($key, $prefix . ".")) {
                $shortKey = substr($key, strlen($prefix) + 1);
                $data[$shortKey] = $value;
            }
        }
        return $data;
    }

    private function instantiate(string $className, array $data): object
    {
        $args = [];
        $refClass = new ReflectionClass($className);
        foreach ($refClass->getConstructor()->getParameters() as $param) {
            $name = $this->camelToDotNotation( $param->getName() );
            $type = $param->getType();
            if ($type instanceof ReflectionNamedType && $type->getName() === 'array') {
                $arg = $data[$name] ?? [];
                $args[] = is_array($arg) ? $arg : explode(",", $arg);
            } else {
                $args[] = $data[$name] ?? null;
            }
        }
        return $refClass->newInstanceArgs($args);
    }

    public function getLoadedFiles(): array
    {
        return $this->loadedFiles;
    }

    public function getLatestModified(): ?int
    {
        $max = null;

        foreach ($this->loadedFiles as $file) {
            if (file_exists($file)) {
                $mtime = filemtime($file);
                $max = max($max ?? 0, $mtime);
            }
        }

        return $max;
    }
}