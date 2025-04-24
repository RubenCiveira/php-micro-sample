<?php declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Service;

use Civi\Repomanager\Shared\Infrastructure\Store\Service\AccessPipeline;
use Civi\Repomanager\Shared\Infrastructure\Store\Service\RestrictionPipeline;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class AccessPipelineTest extends TestCase
{
    public function test_returns_query_unchanged_if_no_filter_class_or_pipeline()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $pipeline = new RestrictionPipeline($container);

        $input = ['filter' => ['activo' => true]];
        $result = $pipeline->restrictFilter('AnyNamespace', 'Cliente', $input);

        $this->assertSame($input, $result);
    }

    public function test_applies_filter_object_and_runs_pipeline()
    {
        $pipelineStep = function ($filter, callable $next) {
            $filter->estado .= '-modificado';
            return $next($filter);
        };

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['TestNamespace::ClienteFilter', true],
            ['TestNamespace::ClienteRestriction', true],
        ]);
        $container->method('get')->willReturnMap([
            ['TestNamespace::ClienteFilter', MockAccessFilter::class],
            ['TestNamespace::ClienteRestriction', [$pipelineStep]],
        ]);

        $pipeline = new RestrictionPipeline($container);

        $input = ['filter' => ['estado' => 'activo']];
        $result = $pipeline->restrictFilter('TestNamespace', 'Cliente', $input);

        $this->assertEquals(['filter' => ['estado' => 'activo-modificado']], $result);
    }

    public function test_pipeline_handles_array_directly_when_no_filter_class()
    {
        $pipelineStep = function ($filter, callable $next) {
            $filter['estado'] = 'forzado';
            return $next($filter);
        };

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['TestNamespace::ClienteFilter', false],
            ['TestNamespace::ClienteRestriction', true],
        ]);
        $container->method('get')->willReturnMap([
            ['TestNamespace::ClienteRestriction', [$pipelineStep]],
        ]);

        $pipeline = new RestrictionPipeline($container);

        $input = ['filter' => ['estado' => 'original']];
        $result = $pipeline->restrictFilter('TestNamespace', 'Cliente', $input);

        $this->assertEquals(['filter' => ['estado' => 'forzado']], $result);
    }

    public function test_multipe_pipeline_handles_array_order_directly_when_no_filter_class()
    {
        $firstPipelineStep = function ($filter, callable $next) {
            $filter['estado'][] = 'antes-primero';
            $result = $next($filter);
            $result['estado'][] = 'despues-primero';
            return $result;
        };
        $secondPipelineStep = function ($filter, callable $next) {
            $filter['estado'][] = 'antes-segundo';
            $result = $next($filter);
            $result['estado'][] = 'despues-segundo';
            return $result;
        };

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['TestNamespace::ClienteFilter', false],
            ['TestNamespace::ClienteRestriction', true],
        ]);
        $container->method('get')->willReturnMap([
            ['TestNamespace::ClienteRestriction', [$firstPipelineStep, $secondPipelineStep]],
        ]);

        $pipeline = new RestrictionPipeline($container);

        $input = ['filter' => ['estado' => ['original']]];
        $result = $pipeline->restrictFilter('TestNamespace', 'Cliente', $input);

        $this->assertEquals(['filter' => ['estado' => ['original', 'antes-primero', 'antes-segundo', 'despues-segundo', 'despues-primero']]], 
                $result);
    }
}

class MockAccessFilter {
    public ?string $estado = null;   
}