<?php

declare(strict_types=1);

namespace Civi\Micro\Kernel;

use Closure;
use Psr\Container\ContainerInterface;
use InvalidArgumentException;
use Traversable;

/**
 * AbstractPipeline
 *
 * Base class to manage a sequence of processing handlers (pipeline) dynamically.
 * It supports retrieving handlers from a container, composing them into a chain,
 * and running data through them. It also delegates object-to-array and array-to-object
 * transformations to an injected ObjectMapper.
 *
 * @api
 */
abstract class AbstractPipeline
{
    /**
     * Constructor.
     *
     * @param ContainerInterface $container The dependency injection container.
     * @param ObjectMapper        $mapper    The object mapper for array/object transformations.
     */
    public function __construct(protected readonly ContainerInterface $container, private readonly ObjectMapper $mapper)
    {
    }

    /**
     * Retrieves the pipeline handlers associated with a given container tag.
     *
     * If no service is found for the tag, an empty array is returned.
     * If the service exists but is not iterable, an exception is thrown.
     *
     * @param string $tag The container service tag to look for.
     *
     * @return mixed[] The pipeline handlers.
     *
     * @throws InvalidArgumentException If the service is not iterable.
     */
    protected function getPipelineHandlers(string $tag): array
    {
        if (!$this->container->has($tag)) {
            return [];
        }
        $handlers = $this->container->get($tag);
        if (!($handlers instanceof Traversable) && !is_array($handlers)) {
            throw new InvalidArgumentException("El servicio '$tag' debe ser iterable.");
        }
        return $handlers instanceof Traversable ? iterator_to_array($handlers) : $handlers;
    }

    /**
     * Executes the given pipeline handlers sequentially.
     *
     * Each handler can transform the input and pass it to the next one.
     * Handlers are composed dynamically into a single callable.
     *
     * @param mixed[] $handlers The list of handlers to execute.
     * @param mixed             $input    The initial input to process.
     * @param mixed             ...$args  Additional arguments passed to handlers.
     *
     * @return mixed The final result after all handlers have processed the input.
     */
    protected function runPipeline(array $handlers, mixed $input, mixed ...$args): mixed
    {
        $handler = array_reduce(
            array_reverse([...$handlers]),
            fn ($next, $current) => fn ($filter) => $this->run($current, $filter, $next, ...$args),
            fn ($filter) =>  $filter
        );
        return $handler($input);
    }

        /**
     * Executes a handler pipeline enforcing interface conformance and dynamic resolution.
     *
     * This method builds a pipeline of handlers (either class names or instances),
     * ensuring each handler implements the expected interface. Each handler
     * is invoked in order, passing the output of the previous as the input of the next.
     *
     * The pipeline can inject a custom "next" generator per step to modify the behavior
     * or signature of the downstream handlers.
     *
     * @param array<int, object|string> $handlers
     *     A list of handler objects or class names to be resolved from the container.
     * @param array{0: class-string, 1: string} $stepHandler
     *     Tuple containing the expected interface name and the method to call on each handler.
     * @param \Closure $last
     *     Final callable to execute when no more handlers remain.
     * @param \Closure|null $nextHandler
     *     Optional closure that receives the next callable and returns a wrapped version.
     *     Useful to control how the "next" handler is passed to each pipeline step.
     * @param mixed ...$args
     *     Arguments to pass into the pipeline.
     *
     * @return mixed
     *     The result of the final pipeline execution.
     *
     * @throws \InvalidArgumentException
     *     If the interface or method name are invalid, or if a handler cannot be resolved.
     * @throws \RuntimeException
     *     If a resolved handler does not implement the required interface.
     */
    protected function runInterfacePipeline(
        array $handlers,
        array $stepHandler,
        Closure $last,
        ?Closure $nextHandler = null,
        ...$args
    ): mixed {
        $expectedInterface = $stepHandler[0];
        $handleMethod = $stepHandler[1];
    
        if (!interface_exists($expectedInterface)) {
            throw new \InvalidArgumentException("Interfaz inválida: $expectedInterface");
        }
    
        if (!is_string($handleMethod)) {
            throw new \InvalidArgumentException("Método inválido: $handleMethod");
        }
    
        $pipeline = array_reduce(
            array_reverse([...$handlers]),
            function ($next, $current) use ($expectedInterface, $handleMethod, $nextHandler) {
                return function (...$args) use ($current, $next, $expectedInterface, $handleMethod, $nextHandler) {
                    $instance = $this->interfaceInstance($current, $expectedInterface);
                    $typedNext = $nextHandler ? $nextHandler($next) : $next;
                    $params = [...$args, $typedNext];
                    return $instance->{$handleMethod}(...$params);
                };
            },
            fn(...$args) => $last(...$args) // función final si no hay más pasos
        );
    
        return $pipeline(...$args);
    }

    private function interfaceInstance(mixed $current, string $expectedInterface): mixed
    {
        if (is_string($current) && class_exists($current)) {
            $instance = $this->container->get($current);
        } else {
            $instance = $current;
        }

        if (!($instance instanceof $expectedInterface)) {
            throw new \RuntimeException(sprintf(
                'El objeto %s no implementa %s',
                is_object($instance) ? get_class($instance) : (string)$instance,
                $expectedInterface
            ));
        }
        return $instance;
    }

    /**
     * Converts an object or array to an array representation.
     *
     * Delegates the conversion to the injected ObjectMapper.
     *
     * @param array|object $object The object or array to convert.
     *
     * @return array The array representation.
     */
    protected function toArray(array|object $object): array
    {
        return $this->mapper->toArray($object);
    }

    /**
     * Converts an array to an object of the specified type.
     *
     * Delegates the conversion to the injected ObjectMapper.
     *
     * @param array|null $data     The data to convert.
     * @param string     $typeName The fully qualified class name of the target object.
     *
     * @return object|null The resulting object, or null if the input is null.
     */
    protected function toObject(array|null $data, string $typeName): object|null
    {
        return $this->mapper->toObject($data, $typeName);
    }

    /**
     * Resolves and executes a handler.
     *
     * Supported handler types:
     *  - Callable (function, closure, invokable class).
     *  - Class name (retrieved and instantiated from the container).
     *  - [ClassName, Method] array (static or instance methods).
     *  - [ObjectInstance, Method] array (instance methods).
     *
     * @param mixed $current The handler to run.
     * @param mixed $filter  The input to pass to the handler.
     * @param mixed $next    The next handler to call.
     * @param mixed ...$args Additional arguments for the handler.
     *
     * @return mixed The result of the handler execution.
     *
     * @throws InvalidArgumentException If the handler cannot be resolved or invoked.
     */
    private function run($current, $filter, $next, ...$args)
    {
        if (is_callable($current)) {
            return $current($filter, $next, ...$args);
        } elseif (is_string($current) && class_exists($current)) {
            $instance = $this->container->get($current);
            if (is_callable($instance)) {
                return $instance($filter, $next, ...$args);
            }
        }
        if (is_array($current) && count($current) === 2) {
            [$classOrInstance, $method] = $current;

            if (is_string($classOrInstance) && class_exists($classOrInstance)) {
                // Instanciar y llamar método no estático
                $instance = $this->container->get($classOrInstance);
                return $instance->$method($filter, $next, ...$args);
            }
        }
        throw new InvalidArgumentException("Handler no invocable: " . print_r($current, true));
    }


}
