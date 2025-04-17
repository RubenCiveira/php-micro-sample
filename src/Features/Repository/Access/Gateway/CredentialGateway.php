<?php

namespace Civi\Repomanager\Features\Repository\Access\Gateway;

use Civi\Repomanager\Features\Repository\Access\Credential;
use Civi\Repomanager\Shared\Infrastructure\FileStore;

class CredentialGateway
{
    private readonly FileStore $store;

    public function __construct()
    {
        $this->store = new FileStore("../storage/credentials.db");
    }

    public function saveCredential(Credential $credential)
    {
        $this->store->set($credential->user, $credential);
    }
    public function removeCredential(string $username)
    {
        $this->store->delete( $username );
    }

    public function listCredentials(): array
    {
        return array_map( fn($row) => Credential::from($row), $this->store->all());
    }
}