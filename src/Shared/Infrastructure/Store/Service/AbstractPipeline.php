<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\Store\Service;

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
            fn($next, $current) => fn($filter) => $current($filter, $next, ...$args),
            fn($filter) => $filter
        );
        return $handler($input, ...$args);
    }

    protected function toArray(array|object $object): array
    {
        return is_array($object) ? $object : get_object_vars($object);
    }

    protected function toObject(array|null $data, string $typeName): object|null
    {
        if ($data) {
            $objectData = new $typeName();
            foreach ($data as $key => $value) {
                if (property_exists($objectData, $key)) {
                    $objectData->$key = $value;
                }
            }
            return $objectData;
        } else {
            return null;
        }
    }
}