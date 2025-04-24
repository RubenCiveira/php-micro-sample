<?php declare(strict_types=1);

namespace Tests\Shared;

use PHPUnit\Framework\TestCase;
use Civi\Micro\Config;

class ConfigUnitTest extends TestCase
{
    private string $configDir;

    protected function setUp(): void
    {
        $this->configDir = __DIR__ . '/test-config';

        if (!is_dir($this->configDir)) {
            mkdir($this->configDir, 0777, true);
        }

        file_put_contents($this->configDir . '/.env', "FOO=bar\nCOMMON=value\n");
        file_put_contents($this->configDir . '/.env.dev', "PROFILE_VAR=dev_value\nCOMMON=dev_override\n");

        file_put_contents($this->configDir . '/security.yaml', <<<YAML
app:
  security:
    public-routes:
      - verify
      - login
    protected-routes:
      - dashboard
      - admin
YAML);

        file_put_contents($this->configDir . '/security.dev.yaml', <<<YAML
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
            '/.env',
            '/.env.dev',
            '/security.yaml',
            '/security.dev.yaml',
        ];

        foreach ($expectedFiles as $relative) {
            $path = $this->configDir . $relative;
            $this->assertFileExists($path, "Archivo esperado no encontrado: $path");
        }

        $config = new Config($this->configDir, $this->configDir, 'security');
        $files = $config->getLoadedFiles();

        foreach ($expectedFiles as $relative) {
            $this->assertContains($this->configDir . $relative, $files);
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
        $config = $refClass->newInstance($this->configDir, $this->configDir, 'envtest');

        // Forzamos el acceso al método privado getFlatConfig()
        $method = $refClass->getMethod('getFlatConfig');
        $method->setAccessible(true);
        $flat = $method->invoke($config, 'app.sample');
        $this->assertEquals('bar', $flat['api.key']);
        $this->assertEquals('dev_override', $flat['shared']);
    }

    public function testClassInstantiation(): void
    {
        eval (<<<'PHP'
        namespace Civi\Repomanager\Shared;

        class SecurityConfig {
            public function __construct(
                public readonly array $protectedRoutes,
                public readonly array $publicRoutes
            ) {}
        }
        PHP);

        $config = new Config($this->configDir, $this->configDir, 'security');
        $instance = $config->build('app.security', 'Civi\Repomanager\Shared\SecurityConfig');
        // $instance = Config::load('app.security', 'Civi\Repomanager\Shared\SecurityConfig', 'security');

        $this->assertInstanceOf('Civi\Repomanager\Shared\SecurityConfig', $instance);

        $this->assertEquals(['verify', 'login'], $instance->publicRoutes);
        $this->assertContains('dev-dashboard', $instance->protectedRoutes);
    }
}