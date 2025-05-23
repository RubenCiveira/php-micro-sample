<?php

namespace Civi\SecurityStore\Features\Access\Rol\Gateway;

use Civi\SecurityStore\Features\Access\Rol\Rol;
use Civi\Store\EntityRepository;
use Civi\Store\Repository;

class RolGateway
{
    private readonly EntityRepository $repository;
    /**
     * @autogenerated
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository->entityRepository('usermanagement', Rol::class);
    }
    /**
     * @autogenerated
     */
    public function createRol(Rol $input)
    {
        $this->repository->create('create', $input);
    }
    /**
     * @autogenerated
     */
    public function updateRol(string $id, Rol $input)
    {
        $this->repository->modify('update', $id, $input);
    }
    /**
     * @autogenerated
     */
    public function deleteRol(string $id)
    {
        $this->repository->change('delete', $id);
    }
    /**
     * @autogenerated
     */
    public function listRoles(): array
    {
        return $this->repository->listView([], []);
    }
}
