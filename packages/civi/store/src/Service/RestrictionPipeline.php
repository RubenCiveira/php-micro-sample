<?php declare(strict_types=1);

namespace Civi\Store\Service;

use Civi\Micro\Kernel\AbstractPipeline;
use Civi\Micro\Kernel\ObjectMapper;
use Psr\Container\ContainerInterface;

class RestrictionPipeline extends AbstractPipeline
{
    public function __construct(ContainerInterface $container, ObjectMapper $mapper)
    {
        parent::__construct($container, $mapper);
    }

    public function restrictFilter(string $namespace, string $typeName, array $query): array
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

        $pipelineTag = "$namespace::{$typeName}Restriction";
        $handlers = $this->getPipelineHandlers($pipelineTag);

        $finalFilter = $this->runPipeline($handlers, $filterObject ?? $filterArray);

        // Convertir de vuelta a array si se usó objeto
        $result[$filterKey] = $this->toArray($finalFilter);
        return $result;
    }
}