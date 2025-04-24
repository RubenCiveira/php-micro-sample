<?php declare(strict_types=1);

namespace Civi\Store;

use Civi\Store\Gateway\DataGateway;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use InvalidArgumentException;

class Repository
{
    public function __construct(private readonly Schemas $schemas, private readonly DataGateway $dataGateway, private readonly Validator $validator)
    {
    }

    public function entityRepository(string $namespace, string $kind): EntityRepository
    {
        return new EntityRepository($namespace, $kind, $this->schemas, $this->dataGateway, $this->validator);
    }

    public function formMetadata(string $namespace, string $kind): EntityViewMetadata
    {
        return new EntityViewMetadata($namespace, $kind, $this->schemas, $this->dataGateway, $this->validator);
    }
}