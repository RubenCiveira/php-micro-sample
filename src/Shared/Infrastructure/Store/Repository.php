<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\Store;

use Civi\Repomanager\Shared\Infrastructure\Store\Gateway\DataGateway;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use InvalidArgumentException;

class Repository
{
    public function __construct(private readonly Schemas $schemas, private readonly DataGateway $dataGateway, private readonly Validator $validator)
    {
    }

    public function entityRepositor(string $namespace, string $kind): EntityRepository
    {
        return new EntityRepository($namespace, $kind, $this->schemas, $this->dataGateway, $this->validator);
    }
}