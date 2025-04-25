<?php

declare(strict_types=1);

namespace Civi\Store;

use Civi\Store\Service\DataService;

class Repository
{
    public function __construct(
        private readonly Schemas $schemas,
        private readonly DataService $dataService,
        private readonly Validator $validator
    ) {}

    public function entityRepository(string $namespace, string $kind): EntityRepository
    {
        return new EntityRepository($namespace, $kind, $this->schemas, $this->dataService, $this->validator);
    }

    public function formMetadata(string $namespace, string $kind): EntityViewMetadata
    {
        return new EntityViewMetadata($namespace, $kind, $this->schemas, $this->dataService, $this->validator);
    }
}
