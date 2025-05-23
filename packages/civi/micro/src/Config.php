<?php

declare(strict_types=1);

namespace Civi\Micro;

use Symfony\Component\Yaml\Yaml;
use Dotenv\Dotenv;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
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

    private readonly string $configPath;

    private readonly string $envPath;

    private static array $schemas = [];
    /**
     * Config constructor.
     *
     * @param string $configPath Path to the configuration files directory.
     * @param string $envPath Path to the environment files directory.
     * @param string $file Base filename (without extension) for the YAML configuration.
     */
    public function __construct(?string $configPath=null, ?string $envPath=null)
    {
        $root = ProjectLocator::getRootPath();
        $this->envPath = $envPath ?? "$root";
        $this->configPath = $configPath ?? "$root/config/app";
        $this->loadEnv();
        $this->loadYamlFiles();
        if( file_exists("{$this->configPath}/overrides.json") ) {
            $overrides = json_decode( file_get_contents("{$this->configPath}/overrides.json"), true );
            $this->configData = [...$this->configData, ...$overrides];
        }
        if( $errors = $this->findConfigErrors() ) {
            throw new \RuntimeException("Config errors:\n" . implode("\n", $errors));
        }
    }

    public static function registerConfigSchema($schema)
    {
        self::$schemas[] = json_decode( $schema );
    }

    public static function registerConfigSchemaFile($file)
    {
        self::$schemas[] = json_decode( file_get_contents($file) );
    }

    /**
     * Creates a Config instance using the project root and loads a specific configuration.
     *
     * @param string $prefix The prefix in the flattened configuration to use.
     * @param string $className The fully qualified class name to instantiate with the config values.
     * @param string $file The base name of the YAML file to load.
     * @return object An instance of the target class populated with configuration values.
     */
    public function load(string $prefix, string $className): object
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
    private function loadYamlFiles(): void
    {
        $this->configData = [];

        $env = $_ENV['PROFILE'] ?? $_SERVER['PROFILE'] ?? null;
        $genericFiles = [];
        $envFiles = [];

        foreach (glob($this->configPath . "/*.yaml") as $file) {
            if (preg_match('/\/(?<name>[^\/]+?)(\.(?<env>\w+))?\.yaml$/', $file, $matches)) {
                $fileEnv = $matches['env'] ?? null;
                // Saltar si es override de otro entorno
                if ($fileEnv === null ) {
                    $genericFiles[] = $file;
                    continue;
                } else if( $fileEnv === $env) {
                    $envFiles[] = $file;
                }
            }    
        }
        foreach ($genericFiles as $fullPath) {
            $this->loadYamlFile($fullPath);
        }
        foreach ($envFiles as $fullPath) {
            $this->loadYamlFile($fullPath);
        }
        $this->configData = $this->flatten($this->configData);
    }

    private function loadYamlFile($fullPath) {
        if (file_exists($fullPath)) {
            $data = Yaml::parse(file_get_contents($fullPath) . "\n\n");
            $this->configData = array_merge_recursive($this->configData, $data ?? []);
            $this->loadedFiles[] = $fullPath;
        }
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

    public function findConfigErrors(): array {
        $errors = [];
        $data = json_decode(json_encode(['port' => 'malo']));

        if( self::$schemas ) {
            foreach(self::$schemas as $schema) {
                $validator = new Validator();
                $validator->validate($data, $schema, Constraint::CHECK_MODE_VALIDATE_SCHEMA	| Constraint::CHECK_MODE_COERCE_TYPES | Constraint::CHECK_MODE_APPLY_DEFAULTS);
                if (!$validator->isValid()) {
                    $errors = [...array_map(fn($e) => "- [{$e['property']}] {$e['message']}", $validator->getErrors()), ...$errors];
                }
            }
        }
        return $errors;
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
     * Reemplaza referencias %env(VAR_NAME)% dentro de cualquier string.
     *
     * @param mixed $value Valor a procesar
     * @return mixed Valor con variables de entorno reemplazadas
     */
    private function resolveEnvVar(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return preg_replace_callback('/%env\((.+?)\)%/', function ($matches) {
            $varName = $matches[1] ?? '';
            return $_ENV[$varName] ?? $_SERVER[$varName] ?? '';
        }, $value);
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
            if (!is_string($key)) {
                continue; // Ignorar claves no string
            }
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
