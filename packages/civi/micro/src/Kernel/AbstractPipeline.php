<?php

declare(strict_types=1);

namespace Civi\Micro\Kernel;

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
