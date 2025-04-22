<?php declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Service;

use Civi\Repomanager\Shared\Infrastructure\Store\Service\ExecPipeline;
use Closure;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ExecPipelineTest extends TestCase
{
    public function test_returns_result_directly_when_no_object_and_no_pipeline()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $pipeline = new ExecPipeline($container);

        $result = $pipeline->executeOperation(
            'Namespace',
            'Tipo',
            [],
            ['foo' => 'bar']
        );
        $this->assertEquals(['foo' => 'bar'], $result);
    }

    public function test_injects_data_and_original_into_object()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['App::Cliente', true],
            ['App::ClienteWrite', false],
        ]);
        $container->method('get')->willReturnMap([
            ['App::Cliente', MockExecEntity::class],
        ]);

        $pipeline = new ExecPipeline($container);

        $result = $pipeline->executeOperation(
            'App',
            'Cliente',
            ['Write'],
            ['nombre' => 'Juan'],
            ['nombre' => 'Original', '__original_nombre' => 'TbOriginal']
        );

        $this->assertEquals('Juan', $result['nombre']);
        // $this->assertEquals('TbOriginal', $dummy->__original_nombre);
    }

    public function test_pipeline_steps_are_executed()
    {
        $step = function (array $payload, Closure $next): array {
            $payload['data']['estado'] = 'procesado';
            return $next($payload);
        };

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['App::Pedido', false],
            ['App::PedidoWrite', true],
        ]);
        $container->method('get')->willReturnMap([
            ['App::PedidoWrite', [$step]],
        ]);

        $pipeline = new ExecPipeline($container);

        $result = $pipeline->executeOperation(
            'App',
            'Pedido',
            ['Write'],
            ['estado' => 'pendiente']
        );

        $this->assertEquals('procesado', $result['data']['estado']);
    }

    public function test_executes_method_on_object_if_present()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['App::Cliente', true],
            ['App::ClienteEnable', false],
        ]);
        $container->method('get')->willReturnMap([
            ['App::Cliente', MockExecEntityWithMethod::class],
        ]);

        $pipeline = new ExecPipeline($container);

        $result = $pipeline->executeOperation(
            'App',
            'Cliente',
            ['Enable'],
            ['id' => 123, 'name' => 'Luis'],
            ['id' => 123, 'name' => 'Juan']
        );
        $this->assertEquals('Luis', $result['name']);
        $this->assertContains('enable-called-123-Juan', $result['log']);
    }

    public function test_executes_method_without_params_on_object_if_present()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['App::Cliente', true],
            ['App::ClienteEnable', false],
        ]);
        $container->method('get')->willReturnMap([
            ['App::Cliente', MockExecEntityWithEnable::class],
        ]);

        $pipeline = new ExecPipeline($container);

        $result = $pipeline->executeOperation(
            'App',
            'Cliente',
            ['Enable'],
            ['id' => 123, 'name' => 'Luis'],
            ['id' => 123, 'name' => 'Juan']
        );
        $this->assertEquals(true, $result['enabled']);
    }
}

class MockExecEntity
{
    public ?string $nombre = null;
}

class MockExecEntityWithMethod
{
    public ?int $id;
    public ?string $name;
    public array $log = [];

    public function enable(MockExecEntityWithMethod $data): void
    {
        $this->log[] = "enable-called-{$data->id}-{$this->name}";
        $this->name = $data->name;
    }
}

class MockExecEntityWithEnable
{
    public bool $enabled = false;

    public function enable(): void
    {
        $this->enabled = true;
    }
}