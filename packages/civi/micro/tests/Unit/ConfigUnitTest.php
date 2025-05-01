<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Civi\Micro\Config;

class ConfigUnitTest extends TestCase
{
    private string $configDir;
    private string $rootDir;

    protected function setUp(): void
    {
        $this->rootDir = \Civi\Micro\ProjectLocator::getRootPath();
        $this->configDir = "{$this->rootDir}/config";

        if (!is_dir($this->configDir)) {
            mkdir($this->configDir, 0777, true);
        }

        // Borrar envs anteriores
        unset($_ENV['FOO'], $_ENV['COMMON'], $_ENV['PROFILE'], $_SERVER['FOO'], $_SERVER['COMMON'], $_SERVER['PROFILE']);

        // Crear archivos de entorno
        file_put_contents("{$this->rootDir}/.env", "FOO=bar\nCOMMON=value\n");
        file_put_contents("{$this->rootDir}/.env.dev", "PROFILE_VAR=dev_value\nCOMMON=dev_override\n");

        // Crear archivos de configuración
        file_put_contents("{$this->configDir}/security.yaml", <<<YAML
app:
  security:
    public-routes:
      - verify
      - login
    protected-routes:
      - dashboard
      - admin
YAML);

        file_put_contents("{$this->configDir}/security.dev.yaml", <<<YAML
app:
  security:
    protected-routes:
      - dev-dashboard
YAML);

        $_ENV['PROFILE'] = 'dev';
    }

    protected function tearDown(): void
    {
        $files = array_merge(
            glob($this->configDir . '/*'),
            glob($this->rootDir . '/.env*')
        );
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== '.' && basename($file) !== '..') {
                unlink($file);
            }
        }
        @rmdir($this->configDir);
    }

    public function testLoadedFilesTracking(): void
    {
        $expectedFiles = [
            $this->rootDir . '/.env',
            $this->rootDir . '/.env.dev',
            $this->configDir . '/security.yaml',
            $this->configDir . '/security.dev.yaml',
        ];

        foreach ($expectedFiles as $path) {
            $this->assertFileExists($path, "Archivo esperado no encontrado: $path");
        }

        $config = new Config($this->configDir, $this->rootDir);
        $reflection = new \ReflectionClass($config);
        $property = $reflection->getProperty('loadedFiles');
        $property->setAccessible(true);
        $files = $property->getValue($config);

        foreach ($expectedFiles as $path) {
            $this->assertContains($path, $files, "El archivo cargado esperado no se encontró: $path");
        }
    }

    public function testEnvVariableSubstitutionAndFlattening(): void
    {
        file_put_contents($this->configDir . '/envtest.yaml', <<<YAML
app:
  sample:
    api-key: "%env(FOO)%"
    shared: "%env(COMMON)%"
YAML);

        $config = new Config($this->configDir, $this->rootDir);
        $refClass = new \ReflectionClass(Config::class);
        $method = $refClass->getMethod('getFlatConfig');
        $method->setAccessible(true);
        $flat = $method->invoke($config, 'app.sample');

        $this->assertEquals('bar', $flat['api.key']);
        $this->assertEquals('dev_override', $flat['shared']);
    }

    public function testClassInstantiation(): void
    {
        eval(<<<'PHP'
        namespace Civi\Repomanager\Shared;

        class SecurityConfig {
            public function __construct(
                public readonly array $protectedRoutes,
                public readonly array $publicRoutes
            ) {}
        }
        PHP);

        $config = new Config($this->configDir, $this->configDir);
        $instance = $config->load('app.security', 'Civi\Repomanager\Shared\SecurityConfig');

        $this->assertInstanceOf('Civi\Repomanager\Shared\SecurityConfig', $instance);
        $this->assertEquals(['verify', 'login'], $instance->publicRoutes);
        $this->assertContains('dev-dashboard', $instance->protectedRoutes);
    }

    public function testInstantiateWithNonArrayParameters(): void
    {
        eval(<<<'PHP'
        namespace Civi\Repomanager\Shared;

        class AppConfig {
            public function __construct(
                public readonly string $appName,
                public readonly int $appVersion
            ) {}
        }
        PHP);

        file_put_contents($this->configDir . '/app.yaml', <<<YAML
app:
  config:
    app-name: "TestApp"
    app-version: 42
YAML);

        $config = new Config($this->configDir, $this->configDir);
        $instance = $config->load('app.config', 'Civi\Repomanager\Shared\AppConfig');

        $this->assertInstanceOf('Civi\Repomanager\Shared\AppConfig', $instance);
        $this->assertEquals('TestApp', $instance->appName);
        $this->assertEquals(42, $instance->appVersion);
    }

    public function testStaticLoad(): void
    {
        eval(<<<'PHP'
        namespace Civi\Repomanager\Shared;

        class StaticSecurityConfig {
            public function __construct(
                public readonly array $protectedRoutes,
                public readonly array $publicRoutes
            ) {}
        }
        PHP);

        $config = new Config($this->configDir, $this->rootDir);
        $instance = $config->load('app.security', 'Civi\Repomanager\Shared\StaticSecurityConfig');

        $this->assertInstanceOf('Civi\Repomanager\Shared\StaticSecurityConfig', $instance);
        $this->assertEquals(['verify', 'login'], $instance->publicRoutes);
        $this->assertContains('dev-dashboard', $instance->protectedRoutes);
    }

    public function testOverridesJson(): void
    {
        file_put_contents("{$this->configDir}/app.yaml", <<<YAML
app:
  settings:
    theme: "light"
    timeout: 30
YAML);

        file_put_contents("{$this->configDir}/overrides.json", json_encode([
            'app.settings.theme' => 'dark'
        ]));

        $config = new Config($this->configDir, $this->rootDir);
        $flat = (new \ReflectionClass($config))->getMethod('getFlatConfig');
        $flat->setAccessible(true);

        $data = $flat->invoke($config, 'app.settings');

        $this->assertEquals('dark', $data['theme']);
        $this->assertEquals(30, $data['timeout']);
    }

    public function testJsonSchemaValidationFails(): void
    {
        $schema = json_encode([
            'type' => 'object',
            'properties' => [
                'port' => ['type' => 'integer']
            ],
            'required' => ['port']
        ]);

        Config::registerConfigSchema($schema);

        file_put_contents("{$this->configDir}/fake.yaml", "port: notanumber\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches("/Config errors:/");

        new Config($this->configDir, $this->rootDir);
    }

    public function testRegisterConfigSchemaFile(): void
    {
        $schemaPath = "{$this->configDir}/app.schema.json";
        file_put_contents($schemaPath, json_encode([
            'type' => 'object',
            'properties' => [
                'port' => ['type' => 'integer']
            ],
            'required' => ['port']
        ]));

        Config::registerConfigSchemaFile($schemaPath);

        file_put_contents("{$this->configDir}/app.yaml", <<<YAML
port: "notanumber"
YAML);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches("/Config errors:/");

        new Config($this->configDir, $this->rootDir);
    }
}
