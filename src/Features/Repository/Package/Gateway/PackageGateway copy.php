<?php

namespace Civi\Repomanager\Features\Repository\Package\Gateway;

use Civi\Repomanager\Features\Repository\Package\Package;
use Civi\Repomanager\Shared\Infrastructure\Simple\FileStore;

class PackageGateway
{
    private readonly FileStore $store;

    public function __construct()
    {
        $this->store = new FileStore("../storage/packages.db");
    }

    public function savePackage(Package $credential)
    {
        $this->store->set($credential->id, $credential);
    }
    public function removePackage(string $username)
    {
        $this->store->delete( $username );
    }

    public function listPackages(): array
    {
        return array_map( fn($row) => Package::from($row), $this->store->all());
    }
}