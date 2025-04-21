<?php declare(strict_types=1);

namespace Civi\Repomanager\Features\Repository\Package\Form;

use Civi\Repomanager\Features\Repository\Package\Package;
use Civi\Repomanager\Shared\Infrastructure\Form\FormMetadata;
use Civi\Repomanager\Shared\Infrastructure\Store\EntityFormMetadata;
use Civi\Repomanager\Shared\Infrastructure\Store\Repository;

class PackageFormMetadata
{
    private readonly EntityFormMetadata $repository;
    public function __construct(Repository $repository)
    {
        $this->repository = $repository->formMetadata('repos', Package::class);
    }

    public function build(): FormMetadata
    {
        return $this->repository->build();
    }
}
