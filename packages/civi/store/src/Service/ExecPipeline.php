<?php declare(strict_types=1);

namespace Civi\Store\Service;

use Civi\Micro\Kernel\AbstractPipeline;
use Civi\Micro\Kernel\ObjectMapper;
use Closure;
use Psr\Container\ContainerInterface;

class ExecPipeline extends AbstractPipeline
{
    public function __construct(ContainerInterface $container, ObjectMapper $mapper)
    {
        parent::__construct($container, $mapper);
    }

    public function executeOperation(
        string $namespace,
        string $typeName,
        array $operations,
        ?Closure $callback,
        array $data,
        ?array $original = null
    ): mixed {
        $fullyQualified = "$namespace::$typeName";
        $objectType = null;
        $object = null;

        $payload = [$data, $original];

        // Si hay clase registrada, obtenerla e inyectar datos
        if ($this->container->has($fullyQualified)) {
            $objectType = $this->container->get($fullyQualified);
            $object = $this->toObject($original, $objectType);
            $payload = [$this->toObject($data, $objectType), $object];
            // Opcionalmente, incluir en el payload
        }

        // Construir el pipeline combinando todos los pasos declarados
        $handlers = [];

        foreach ($operations as $suffix) {
            $tag = "$namespace::{$typeName}$suffix";
            $steps = $this->getPipelineHandlers($tag);
            $handlers = array_merge($handlers, $steps);
        }

        // Paso final: ejecutar método de la entidad si lo tiene
        if ($object) {
            foreach ($operations as $suffix) {
                if (method_exists($object, $method = lcfirst($suffix))) {
                    $handlers[] = function (mixed $data, Closure $next) use ($object, $method) {
                        $result = $object->$method($data);
                        return $result ?? $object;
                    };
                }
            }
        } else if ($objectType) {
            foreach ($operations as $suffix) {
                if (method_exists($objectType, $method = lcfirst($suffix))) {
                    $handlers[] = fn(mixed $data, Closure $next) => call_user_func([$objectType, $method], $data);
                }
            }
        }
        if ($callback) {
            $handlers[] = function ($param, $next) use ($callback) {
                $callback();
                $result = $next($param);
                return $result;
            };
        }

        // Ejecutar pipeline
        $result = $payload[1] ? $this->runPipeline($handlers, $payload[0], $payload[1]) : $this->runPipeline($handlers, $payload[0]);

        // Devolver la entidad si existe, o el resultado
        return $this->toArray($result);
    }
}