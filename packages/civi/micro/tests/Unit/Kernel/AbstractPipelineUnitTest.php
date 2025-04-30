<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Civi\Micro\Kernel\AbstractPipeline;
use Civi\Micro\Kernel\ObjectMapper;

final class AbstractPipelineUnitTest extends TestCase
{
    private $container;
    private $mapper;
    private $pipeline;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->mapper = $this->createMock(ObjectMapper::class);

        $this->pipeline = new class ($this->container, $this->mapper) extends AbstractPipeline {
            public function publicGetPipelineHandlers(string $tag)
            {
                return $this->getPipelineHandlers($tag);
            }
            public function publicRunInterfacePipeline(
                array $handlers,
                array $stepHandler,
                \Closure $last,
                ?\Closure $nextHandler = null,
                ...$args
            ): mixed {
                return $this->runInterfacePipeline($handlers, $stepHandler, $last, $nextHandler, ...$args);
            }
            public function publicRunPipeline(iterable $handlers, mixed $input, mixed ...$args)
            {
                return $this->runPipeline($handlers, $input, ...$args);
            }
            public function publicToArray(array|object $object)
            {
                return $this->toArray($object);
            }
            public function publicToObject(?array $data, string $typeName)
            {
                return $this->toObject($data, $typeName);
            }
        };
    }

    public function testGetPipelineHandlersReturnsEmptyIfServiceNotFound(): void
    {
        $this->container->method('has')->willReturn(false);

        $handlers = $this->pipeline->publicGetPipelineHandlers('nonexistent_tag');
        $this->assertIsArray($handlers);
        $this->assertEmpty($handlers);
    }

    public function testGetPipelineHandlersThrowsIfNotIterable(): void
    {
        $this->container->method('has')->willReturn(true);
        $this->container->method('get')->willReturn('not_iterable');

        $this->expectException(InvalidArgumentException::class);
        $this->pipeline->publicGetPipelineHandlers('invalid_service');
    }

    public function testRunPipelineExecutesHandlersInOrder(): void
    {
        $handlers = [
            fn ($input, $next) => $next($input . 'A'),
            fn ($input, $next) => $next($input . 'B'),
        ];

        $result = $this->pipeline->publicRunPipeline($handlers, 'start');

        $this->assertEquals('startAB', $result);
    }

    public function testRunPipelineExecutesInvokableClassFromContainer(): void
    {
        // Definir una clase invocable anónima
        $handlerClass = new class () {
            public function __invoke($input, $next)
            {
                return $next($input . 'X');
            }
        };

        // Crear una clase nombre dinámico para registrar en el contenedor
        $className = get_class($handlerClass);

        // El contenedor devuelve la instancia cuando se pide por nombre de clase
        $this->container
            ->method('get')
            ->with($className)
            ->willReturn($handlerClass);

        $this->container
            ->method('has')
            ->willReturn(true);

        // Pasamos el nombre de la clase como handler
        $handlers = [
            $className
        ];

        $result = $this->pipeline->publicRunPipeline($handlers, 'start');

        $this->assertEquals('startX', $result);
    }

    public function testToArrayDelegatesToMapper(): void
    {
        $object = (object)['a' => 1];
        $expected = ['a' => 1];

        $this->mapper
            ->expects($this->once())
            ->method('toArray')
            ->with($object)
            ->willReturn($expected);

        $result = $this->pipeline->publicToArray($object);
        $this->assertSame($expected, $result);
    }

    public function testRunPipelineExecutesStaticMethodFromClassName(): void
    {
        // Crear una clase dinámica con método estático
        $className = 'StaticHandler';

        $handlers = [
            [$className, 'handle']
        ];

        $result = $this->pipeline->publicRunPipeline($handlers, 'start');

        $this->assertEquals('startS', $result);
    }

    public function testRunPipelineExecutesNonStaticMethodFromContainer(): void
    {
        // Crear clase dinámica con método no estático
        $className = 'NonStaticHandler';
        $instance = new NonStaticHandler();

        $this->container
            ->method('get')
            ->with($className)
            ->willReturn($instance);

        $this->container
            ->method('has')
            ->willReturn(true);

        $handlers = [
            [$className, 'handle']
        ];

        $result = $this->pipeline->publicRunPipeline($handlers, 'start');

        $this->assertEquals('startN', $result);
    }

    public function testRunPipelineExecutesMethodFromObjectInstance(): void
    {
        // Crear una instancia manual
        $handlerObject = new class () {
            public function handle($input, $next)
            {
                return $next($input . 'O');
            }
        };

        $handlers = [
            [$handlerObject, 'handle']
        ];

        $result = $this->pipeline->publicRunPipeline($handlers, 'start');

        $this->assertEquals('startO', $result);
    }

    public function testToObjectDelegatesToMapper(): void
    {
        $data = ['id' => 1];
        $type = 'DummyType';
        $expected = new stdClass();

        $this->mapper
            ->expects($this->once())
            ->method('toObject')
            ->with($data, $type)
            ->willReturn($expected);

        $result = $this->pipeline->publicToObject($data, $type);
        $this->assertSame($expected, $result);
    }

    public function testRunPipelineThrowsExceptionWhenHandlerIsInvalid(): void
    {
        $invalidHandler = 'this_is_not_a_class_or_callable';

        $handlers = [$invalidHandler];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Handler no invocable/');

        $this->pipeline->publicRunPipeline($handlers, 'start');
    }

    public function testGetPipelineHandlersReturnsArrayFromContainer(): void
    {
        $handlers = [
            fn ($input, $next) => $next($input . 'A'),
            fn ($input, $next) => $next($input . 'B')
        ];

        $tag = 'pipeline.handlers';

        $this->container
            ->method('has')
            ->with($tag)
            ->willReturn(true);

        $this->container
            ->method('get')
            ->with($tag)
            ->willReturn($handlers);

        $result = $this->pipeline->publicGetPipelineHandlers($tag);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertSame($handlers, $result);
    }

    public function testRunInterfacePipelineWithObjectInstances(): void
    {
        $handler = new class () implements MyStepInterface {
            public function handle($input, $next)
            {
                return $next($input . '1');
            }
        };

        $result = $this->pipeline->publicRunInterfacePipeline(
            [$handler],
            [MyStepInterface::class, 'handle'],
            fn ($input) => $input . 'X',
            null,
            'A'
        );

        $this->assertEquals('A1X', $result);
    }

    public function testRunInterfacePipelineWithClassNames(): void
    {
        $className = MyHandlerClass::class;
        $this->container->method('get')->with($className)->willReturn(new MyHandlerClass());

        $result = $this->pipeline->publicRunInterfacePipeline(
            [$className],
            [MyStepInterface::class, 'handle'],
            fn ($input) => $input . 'X',
            null,
            'A'
        );

        $this->assertEquals('A1X', $result);
    }

    public function testRunInterfacePipelineWithClassNamesAndWrongPrimitiveParam(): void
    {
        $className = MyHandlerClass::class;
        $this->container->method('get')->with($className)->willReturn(22);
        $this->expectException(RuntimeException::class);

        $this->pipeline->publicRunInterfacePipeline(
            [$className],
            [MyStepInterface::class, 'handle'],
            fn ($input) => $input . 'X',
            null,
            'A'
        );
    }

    public function testRunInterfacePipelineThrowsOnInvalidInterface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pipeline->publicRunInterfacePipeline([], ['Invalid\Interface', 'handle'], fn () => null);
    }

    public function testRunInterfacePipelineThrowsOnInvalidMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pipeline->publicRunInterfacePipeline([], [MyStepInterface::class, null], fn () => null);
    }

    public function testRunInterfacePipelineThrowsIfNotImplementingInterface(): void
    {
        $badHandler = new class () {}; // no implementa MyStepInterface

        $this->expectException(RuntimeException::class);
        $this->pipeline->publicRunInterfacePipeline(
            [$badHandler],
            [MyStepInterface::class, 'handle'],
            fn ($input) => $input
        );
    }

    public function testRunInterfacePipelineWithCustomNextWrapper(): void
    {
        $handler = new class () implements MyStepInterface {
            public function handle($input, $next)
            {
                return $next->next($input . 'A');
            }
        };
        $nextWrapper = fn ($next) => new class($next) implements MyStepHandler {
            public function __construct(private readonly Closure $next){}
            public function next($input) {
                $next = $this->next;
                return '[' . $next($input) . ']';
            }
        };

        $result = $this->pipeline->publicRunInterfacePipeline(
            [$handler],
            [MyStepInterface::class, 'handle'],
            fn ($input) => $input . 'Z',
            $nextWrapper,
            'X'
        );

        $this->assertEquals('[XAZ]', $result);
    }
}

class NonStaticHandler
{
    public function handle($input, $next)
    {
        return $next($input . 'N');
    }
}

class StaticHandler
{
    public static function handle($input, $next)
    {
        return $next($input . 'S');
    }
}

interface MyStepInterface
{
    public function handle($input, $next);
}

interface MyStepHandler
{
    public function next($input);
}


class MyHandlerClass implements MyStepInterface
{
    public function handle($input, $next)
    {
        return $next($input . '1');
    }
}