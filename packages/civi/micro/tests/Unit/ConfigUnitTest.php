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
            glob($this->configDir . '/.env*') // incluir .env, .env.dev, etc.
        );
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== '.' && basename($file) !== '..') {
                unlink($file);
            }
        }
        rmdir($this->configDir);
    }

    public function testLoadedFilesTracking(): void
    {
        // Precondición: los archivos deben existir
        $expectedFiles = [
            $this->rootDir . '/.env',
            $this->rootDir . '/.env.dev',
            $this->configDir . '/security.yaml',
            $this->configDir . '/security.dev.yaml',
        ];

        foreach ($expectedFiles as $path) {
            $this->assertFileExists($path, "Archivo esperado no encontrado: $path");
        }

        $config = new Config($this->configDir, $this->rootDir, 'security');
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

        $refClass = new \ReflectionClass(Config::class);
        $config = $refClass->newInstance($this->configDir, $this->rootDir, 'envtest');

        // Forzamos el acceso al método privado getFlatConfig()
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

        $config = new Config($this->configDir, $this->configDir, 'security');
        $refClass = new \ReflectionClass(Config::class);
        $method = $refClass->getMethod('build');
        $method->setAccessible(true);
        $instance = $method->invoke($config, 'app.security', 'Civi\Repomanager\Shared\SecurityConfig');

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

        $config = new Config($this->configDir, $this->configDir, 'app');
        $refClass = new \ReflectionClass(Config::class);
        $method = $refClass->getMethod('build');
        $method->setAccessible(true);
        $instance = $method->invoke($config, 'app.config', 'Civi\Repomanager\Shared\AppConfig');

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

        $instance = Config::load('app.security', 'Civi\Repomanager\Shared\StaticSecurityConfig', 'security');

        $this->assertInstanceOf('Civi\Repomanager\Shared\StaticSecurityConfig', $instance);
        $this->assertEquals(['verify', 'login'], $instance->publicRoutes);
        $this->assertContains('dev-dashboard', $instance->protectedRoutes);
    }

}
