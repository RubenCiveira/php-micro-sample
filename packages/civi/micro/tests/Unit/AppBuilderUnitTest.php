<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Civi\Micro\AppBuilder;
use Slim\App;
use Psr\Log\LoggerInterface;

final class AppBuilderUnitTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetAppBuilderStatic();
    }

    private function resetAppBuilderStatic(): void
    {
        $ref = new ReflectionClass(AppBuilder::class);

        foreach (['dependencies', 'routes'] as $property) {
            $prop = $ref->getProperty($property);
            $prop->setAccessible(true);
            $prop->setValue(null, []);
        }
    }

    public function testDependenciesStoresFiles(): void
    {
        AppBuilder::dependencies('fake_dep.php');
        $ref = new ReflectionClass(AppBuilder::class);
        $prop = $ref->getProperty('dependencies');
        $prop->setAccessible(true);
        $deps = $prop->getValue();

        $this->assertContains('fake_dep.php', $deps);
    }

    public function testRoutesStoresFiles(): void
    {
        AppBuilder::routes('fake_route.php');
        $ref = new ReflectionClass(AppBuilder::class);
        $prop = $ref->getProperty('routes');
        $prop->setAccessible(true);
        $routes = $prop->getValue();

        $this->assertContains('fake_route.php', $routes);
    }

    public function testBuildAppReturnsSlimApp(): void
    {
        // Preparar entorno mínimo para que AppBuilder::buildApp() funcione
        $root = sys_get_temp_dir() . '/appbuilder_test';
        if (!is_dir($root)) {
            mkdir($root);
        }
        file_put_contents($root . '/di.container.php', "<?php return function(\$c) {};");

        $this->mockProjectLocator($root);

        $app = AppBuilder::buildApp();

        $this->assertInstanceOf(App::class, $app);

        // Cleanup
        unlink($root . '/di.container.php');
        rmdir($root);
    }

    public function testBuildAppProcessesDependencies(): void
    {
        $tempDir = sys_get_temp_dir() . '/appbuilder_test';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $depFile = $tempDir . '/di.container.php';
        file_put_contents($depFile, <<<PHP
<?php
return function(\$container) {
    // mock dependency
};
PHP);

        $this->mockProjectLocator($tempDir);

        AppBuilder::dependencies($depFile);

        $app = AppBuilder::buildApp();

        $this->assertInstanceOf(App::class, $app);

        // Cleanup
        unlink($depFile);
        rmdir($tempDir);
    }

    public function testBuildAppProcessesRoutes(): void
    {
        $tempDir = sys_get_temp_dir() . '/appbuilder_test_routes';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $routeFile = $tempDir . '/routes.php';
        file_put_contents($routeFile, <<<PHP
<?php
return function(\$app) {
    \$app->get('/test', function(\$request, \$response) {
        \$response->getBody()->write('Test OK');
        return \$response;
    });
};
PHP);

        $this->mockProjectLocator($tempDir);

        AppBuilder::routes($routeFile);

        $app = AppBuilder::buildApp();

        $this->assertInstanceOf(App::class, $app);

        // Cleanup
        unlink($routeFile);
        $this->clean($tempDir);
    }

    public function testBuildAppProcessesRootRoutesIfNotRegistered(): void
    {
        $tempDir = sys_get_temp_dir() . '/appbuilder_test_rootroutes';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        // Crear un routes.php en el "root" (pero NO registrar en AppBuilder::routes())
        $rootRouteFile = $tempDir . '/routes.php';
        file_put_contents($rootRouteFile, <<<PHP
<?php
return function(\$app) {
    \$app->get('/root', function(\$request, \$response) {
        \$response->getBody()->write('Root Route OK');
        return \$response;
    });
};
PHP);

        $this->mockProjectLocator($tempDir);

        // No llamamos a AppBuilder::routes() para este archivo

        $app = AppBuilder::buildApp();

        $this->assertInstanceOf(App::class, $app);

        // Verificamos que la ruta /root existe registrando el response
        // Cleanup
        unlink($rootRouteFile);
        $this->clean($tempDir);
    }

    public function testManagementExecution(): void
    {
        $tempDir = sys_get_temp_dir() . '/appbuilder_test_logger';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        // Crear dependencies.php vacío porque sino falla
        $depFile = $tempDir . '/di.container.php';
        file_put_contents($depFile, "<?php \n"
            . "use Civi\Micro\Management\ManagementInterface;\n"
            . "class MockTxtManagementSample implements ManagementInterface {\n"
            . "public function name(): string { return \"mock-txt\"; }\n"
            . "public function get(): ?Closure { return fn() => \"GET\"; }\n"
            . "public function set(): ?Closure { return fn() => \"SET\"; }\n"
            . "}\n"
            . "class MockJsonManagementSample implements ManagementInterface {\n"
            . "public function name(): string { return \"mock-json\"; }\n"
            . "public function get(): ?Closure { return fn() => [\"name\" => \"NAME GET\"]; }\n"
            . "public function set(): ?Closure { return fn() => [\"name\" => \"NAME SET\"]; }\n"
            . "}\n"
            ."return function(\$c) {\n"
            ."\$c->addDefinitions([ManagementInterface::class => \\DI\\add([\n"
                . "\\DI\\factory(fn() => new MockJsonManagementSample() ),\n"
                . "\\DI\\factory(fn() => new MockTxtManagementSample() ),\n"
            . "])]);\n"
            // ."\$c->set(ManagementInterface::class, \\DI\\add(\\DI\\factory(fn() => new MockTxtManagementSample() ) ) );\n"
            ." };");

        $this->mockProjectLocator($tempDir);

        AppBuilder::dependencies($depFile);
        $app = AppBuilder::buildApp();
        $app->setBasePath('');
        $getRequest = (new \Slim\Psr7\Factory\ServerRequestFactory())->createServerRequest('GET', '/management/mock-txt');
        $getResponse = $app->handle($getRequest);
        $this->assertEquals(200, $getResponse->getStatusCode());
        $this->assertEquals("GET", $getResponse->getBody());
        $postRequest = (new \Slim\Psr7\Factory\ServerRequestFactory())->createServerRequest('POST', '/management/mock-txt')
                                ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                                ->withParsedBody(['foo' => 'bar']);
        ;
        $postResponse = $app->handle($postRequest);
        $this->assertEquals(200, $postResponse->getStatusCode());
        $this->assertEquals("SET", $postResponse->getBody());
        $getRequest = (new \Slim\Psr7\Factory\ServerRequestFactory())->createServerRequest('GET', '/management/mock-json');
        $getResponse = $app->handle($getRequest);
        $this->assertEquals(200, $getResponse->getStatusCode());
        $this->assertEquals(["name" => "NAME GET"], json_decode((string)$getResponse->getBody(), true));
        $postRequest = (new \Slim\Psr7\Factory\ServerRequestFactory())->createServerRequest('POST', '/management/mock-json')
                                ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                                ->withParsedBody(['foo' => 'bar']);
        ;
        $postResponse = $app->handle($postRequest);
        $this->assertEquals(200, $postResponse->getStatusCode());
        $this->assertEquals(["name" => "NAME SET"], json_Decode((string)$postResponse->getBody(), true));
    }

    public function testLoggerInterfaceIsRegisteredInContainer(): void
    {
        $tempDir = sys_get_temp_dir() . '/appbuilder_test_logger';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        // Crear dependencies.php vacío porque sino falla
        $depFile = $tempDir . '/di.container.php';
        file_put_contents($depFile, "<?php return function(\$c) {};");

        $this->mockProjectLocator($tempDir);

        AppBuilder::dependencies($depFile);

        $app = AppBuilder::buildApp();

        $container = $app->getContainer();

        $this->assertTrue($container->has(LoggerInterface::class), 'LoggerInterface should be registered.');

        $logger = $container->get(LoggerInterface::class);

        $this->assertInstanceOf(LoggerInterface::class, $logger);

        // Cleanup
        unlink($depFile);
        $this->clean($tempDir);
    }

    private function clean($tempDir)
    {
        if (file_exists($tempDir . '/.env')) {
            unlink($tempDir . '/.env');
        }
        if (file_exists($tempDir . '/.env.dev')) {
            unlink($tempDir . '/.env.dev');
        }
        rmdir($tempDir);
    }


    private function mockProjectLocator(string $root): void
    {
        $ref = new ReflectionClass(\Civi\Micro\ProjectLocator::class);
        $prop = $ref->getProperty('rootPath');
        $prop->setAccessible(true);
        $prop->setValue(null, $root);
    }
}
