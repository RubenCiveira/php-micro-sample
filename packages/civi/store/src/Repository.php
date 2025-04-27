<?php

declare(strict_types=1);

namespace Civi\Store;

use Civi\Micro\Telemetry\LoggerAwareInterface;
use Civi\Store\Service\DataService;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class Repository
{
    public function __construct(
        private readonly Schemas $schemas,
        private readonly DataService $dataService,
        private readonly Validator $validator,
        private readonly LoggerInterface $logger,
    ) {}

    public function entityRepository(string $namespace, string $kind): EntityRepository
    {
        $repo = new EntityRepository($namespace, $kind, $this->schemas, $this->dataService, $this->validator);
        $this->dataService->setLogger( $this->logger );
        $repo->setLogger( $this->logger );
        return $repo;
    }

    public function formMetadata(string $namespace, string $kind): EntitySchemaBuilder
    {
        $repo = new EntitySchemaBuilder($namespace, $kind, $this->schemas, $this->dataService, $this->validator);
        $this->dataService->setLogger( $this->logger );
        return $repo;
    }
}
