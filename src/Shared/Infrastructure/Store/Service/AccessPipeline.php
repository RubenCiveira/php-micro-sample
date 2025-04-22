<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\Store\Service;

use Psr\Container\ContainerInterface;

class AccessPipeline extends AbstractPipeline
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }

    public function applyAccessPipeline(string $namespace, string $typeName, array $query): array
    {
        $filterKey = 'filter';
        $filterObject = null;
        $result = $query;
        $filterArray = $query[$filterKey] ?? [];

        $filterClass = "$namespace::{$typeName}Filter";
        if ($this->container->has($filterClass)) {
            $filterType = $this->container->get($filterClass);
            $filterObject = $this->toObject($filterArray, $filterType);
        }

        $pipelineTag = "$namespace::{$typeName}Access";
        $handlers = $this->getPipelineHandlers($pipelineTag);

        $finalFilter = $this->runPipeline($handlers, $filterObject ?? $filterArray);

        // Convertir de vuelta a array si se usó objeto
        $result[$filterKey] = $this->toArray($finalFilter);
        return $result;
    }
}