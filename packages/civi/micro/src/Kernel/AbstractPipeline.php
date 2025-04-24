<?php declare(strict_types=1);

namespace Civi\Micro\Kernel;

use Psr\Container\ContainerInterface;
use InvalidArgumentException;

abstract class AbstractPipeline
{
    public function __construct(protected readonly ContainerInterface $container)
    {
    }

    protected function getPipelineHandlers(string $tag): array
    {
        if (!$this->container->has($tag)) {
            return [];
        }

        $handlers = $this->container->get($tag);

        if (!is_iterable($handlers)) {
            throw new InvalidArgumentException("El servicio '$tag' debe ser iterable.");
        }

        return $handlers;
    }
    
    protected function runPipeline(iterable $handlers, mixed $input, mixed ...$args): mixed
    {
        $handler = array_reduce(
            array_reverse(iterator_to_array($handlers)),
            fn($next, $current) => fn($filter) => $this->run( $current, $filter, $next, ...$args),
            fn($filter) =>  $filter
        );
        return $handler($input, ...$args);
    }

    protected function toArray(array|object $object): array
    {
        if( is_array($object) ) {
            return $object;
        } else {
            $response = [];
            $extract = get_object_vars($object);
            foreach($extract as $k=>$v) {
                if( $v !== null ) {
                    $response[$k] = $v;
                }
            }
            return $response;
        }
    }

    protected function toObject(array|null $data, string $typeName): object|null
    {
        if ($data === null) {
            return null;
        }
    
        $object = new $typeName();
        $refClass = new \ReflectionClass($typeName);
    
        foreach ($data as $key => $value) {
            if (!$refClass->hasProperty($key)) {
                continue;
            }
    
            $prop = $refClass->getProperty($key);
            $type = $prop->getType();
    
            if (!$type) {
                $object->$key = $value;
                continue;
            }
    
            $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;
    
            if ($type->allowsNull() && $value === null) {
                $object->$key = null;
            } elseif ($typeName === 'int') {
                $object->$key = (int) $value;
            } elseif ($typeName === 'float') {
                $object->$key = (float) $value;
            } elseif ($typeName === 'bool') {
                $object->$key = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            } elseif ($typeName === 'string') {
                $object->$key = (string) $value;
            } elseif (is_a($typeName, \DateTimeInterface::class, true)) {
                $object->$key = new \DateTimeImmutable($value);
            } else {
                // fallback genérico
                $object->$key = $value;
            }
        }
        return $object;
    }    

    private function run($current, $filter, $next, ...$args)
    {
        if (is_callable($current)) {
            return $current($filter, $next, ...$args);
        } else if (is_string($current) && class_exists($current)) {
            $instance = $this->container->get($current);
            if (is_callable($instance)) {
                return $instance($filter, $next, ...$args);
            }
        }
        if (is_array($current) && count($current) === 2) {
            [$classOrInstance, $method] = $current;
    
            if (is_string($classOrInstance) && class_exists($classOrInstance)) {
                $reflection = new \ReflectionMethod($classOrInstance, $method);
                if ($reflection->isStatic()) {
                    return $classOrInstance::$method($filter, $next, ...$args);
                }
                // Instanciar y llamar método no estático
                $instance = $this->container->get($classOrInstance);
                return $instance->$method($filter, $next, ...$args);
            }
    
            // Ya es una instancia: simplemente invocar
            if (is_object($classOrInstance) && method_exists($classOrInstance, $method)) {
                return $classOrInstance->$method($filter, $next, ...$args);
            }
        }    
        throw new InvalidArgumentException("Handler no invocable: " . print_r($current, true));
    }
}