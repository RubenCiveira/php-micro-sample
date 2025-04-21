<?php

namespace Civi\Repomanager\Features\Repository\Package\Gateway;

use Civi\Repomanager\Features\Repository\Package\Package;
use Civi\Repomanager\Shared\Infrastructure\Simple\FileStore;
use Civi\Repomanager\Shared\Infrastructure\Store\EntityRepository;
use Civi\Repomanager\Shared\Infrastructure\Store\Repository;

class PackageGateway
{

    private readonly EntityRepository $repository;
    public function __construct(Repository $repository)
    {
        $this->repository = $repository->entityRepository('repos', Package::class);
    }

    public function createPackage(Package $credential)
    {
        $this->repository->create($credential->withStatus("pending"));
    }
    public function updatePackage(string $id, Package $credential)
    {
        $this->repository->modify($id, $credential);
    }
    public function removePackage(string $id)
    {
        $this->repository->delete($id);
    }

    public function listPackages(): array
    {
        return $this->repository->listView([], []);
    }
}