<?php

declare(strict_types=1);

namespace Civi\Store\Gateway;

use Civi\Micro\ProjectLocator;
use Civi\Store\DataQueryParam;
use Civi\Store\Filter\DataQueryCondition;
use Civi\Store\Filter\DataQueryFilter;
use Civi\Store\Filter\DataQueryOperator;
use Civi\Store\JsonDb\Engine;
use Civi\Store\StoreSchema;

class DataGateway
{

    public function __construct(private readonly Engine $jsonDb)
    {
    }

    public function read(string $namespace, string $typeName, StoreSchema $meta, DataQueryParam $filters): array
    {
        return $this->jsonDb->read($namespace, $typeName, $meta, $filters);
    }

    public function save(string $namespace, string $typeName, StoreSchema $meta, array $data)
    {
        return $this->jsonDb->save($namespace, $typeName, $meta, $data);
    }

    public function delete(string $namespace, string $typeName, array $read, StoreSchema $meta): void
    {
        $this->jsonDb->delete($namespace, $typeName, $read, $meta);
    }
}
