<?php

declare(strict_types=1);

namespace Civi\Micro;

use Symfony\Component\Yaml\Yaml;
use Dotenv\Dotenv;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Class Config
 *
 * Loads and manages configuration from YAML files and environment variables.
 *
 * It supports environment profiles (e.g., `.env.dev`, `security.dev.yaml`) and
 * automatically flattens hierarchical YAML structures into dot-notated keys.
 */
class Config
{
    /**
     * @var array<string, mixed> Flattened configuration data loaded from YAML files.
     */
    private array $configData = [];

    /**
     * @var array<string> List of configuration files and environment files that were loaded.
     */
    private array $loadedFiles = [];

    /**
     * Config constructor.
     *
     * @param string $configPath Path to the configuration files directory.
     * @param string $envPath Path to the environment files directory.
     * @param string $file Base filename (without extension) for the YAML configuration.
     */
    public function __construct(private string $configPath, private string $envPath, $file)
    {
        $this->loadEnv();
        $this->loadYamlFiles($file);
    }

    /**
     * Creates a Config instance using the project root and loads a specific configuration.
     *
     * @param string $prefix The prefix in the flattened configuration to use.
     * @param string $className The fully qualified class name to instantiate with the config values.
     * @param string $file The base name of the YAML file to load.
     * @return object An instance of the target class populated with configuration values.
     */
    public static function load(string $prefix, string $className, string $file): object
    {
        $root = ProjectLocator::getRootPath();
        $instance = new self("$root/config", "$root", $file);
        return $instance->build($prefix, $className);
    }

    /**
     * Builds an instance of the specified class populated with configuration values.
     *
     * @param string $prefix The prefix to filter the configuration entries.
     * @param string $className The class name to instantiate.
     * @return object Instantiated object with injected configuration.
     */
    private function build($prefix, $className)
    {
        $data = $this->getFlatConfig($prefix);
        return $this->instantiate($className, $data);
    }

    /**
     * Loads environment variables from `.env` and profile-specific `.env` files.
     */
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
            foreach ($readed as $k => $v) {
                $_ENV[$k] = $v;
            }
            $this->loadedFiles[] = "{$this->envPath}/.env.$profile";
        }
    }

    /**
     * Loads and merges configuration YAML files, including environment-specific variants.
     *
     * @param string $fileName Base name of the YAML file to load.
     */
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

    /**
     * Flattens a nested associative array into a single-level array with dot notation keys.
     *
     * @param array<string, mixed> $array The array to flatten.
     * @param string $prefix (optional) Current prefix for nested keys.
     * @return array<string, mixed> The flattened array.
     */
    private function flatten(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            // Normalizamos el nombre de la clave: minúsculas y puntos
            $normalizedKey = strtolower(str_replace(['_', '-'], '.', $this->camelToDotNotation((string) $key)));
            $fullKey = $prefix ? "$prefix.$normalizedKey" : $normalizedKey;

            if (is_array($value) && array_keys($value) !== range(0, count($value) - 1)) {
                // Si es array asociativo, continuar recursión
                $result += $this->flatten($value, $fullKey);
            } elseif (is_array($value)) {
                $replaced = [];
                foreach ($value as $v) {
                    $replaced[] = $this->resolveEnvVar($v);
                }
                $result[$fullKey] = $replaced;
            } else {
                // Si es array indexado o valor simple, asignar directamente
                $result[$fullKey] = $this->resolveEnvVar($value);
                ;
            }
        }

        return $result;
    }

    /**
     * Converts a camelCase string into dot.notation format.
     *
     * @param string $input The input camelCase string.
     * @return string The dot-notated, lowercase string.
     */
    private function camelToDotNotation(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '.$0', $input));
    }

    /**
     * Resolves environment variable placeholders inside configuration values.
     *
     * Supports format: `%env(VAR_NAME)%`.
     *
     * @param mixed $value The configuration value to resolve.
     * @return mixed The resolved value, or the original if no substitution is needed.
     */
    private function resolveEnvVar(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^%env\((.+?)\)%$/', $value, $matches)) {
            $varName = $matches[1] ?? null;
            if (is_string($varName) && $varName !== '') {
                return $_ENV[$varName] ?? $_SERVER[$varName] ?? null;
            }
        }
        return $value;
    }

    /**
     * Filters the flattened configuration data by a specific prefix.
     *
     * @param string $prefix The prefix to match (case-insensitive).
     * @return array<string, mixed> Filtered configuration data.
     */
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

    /**
     * Instantiates a class using the configuration data to inject constructor arguments.
     *
     * @param string $className The fully qualified class name to instantiate.
     * @param array<string, mixed> $data The data used to fill constructor parameters.
     * @return object Instantiated object with injected values.
     */
    private function instantiate(string $className, array $data): object
    {
        $args = [];
        $refClass = new ReflectionClass($className);
        foreach ($refClass->getConstructor()->getParameters() as $param) {
            $name = $this->camelToDotNotation($param->getName());
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

}
