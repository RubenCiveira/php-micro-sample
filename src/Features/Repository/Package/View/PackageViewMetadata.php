<?php declare(strict_types=1);

namespace Civi\Repomanager\Features\Repository\Package\View;

use Civi\Repomanager\Features\Repository\Package\Package;
use Civi\Repomanager\Shared\Infrastructure\View\ViewMetadata;
use Civi\Repomanager\Shared\Infrastructure\Store\EntityViewMetadata;
use Civi\Repomanager\Shared\Infrastructure\Store\Repository;

class PackageViewMetadata
{
    private readonly EntityViewMetadata $repository;
    public function __construct(Repository $repository)
    {
        $this->repository = $repository->formMetadata('repos', Package::class);
    }

    public function build(): ViewMetadata
    {
        return $this->repository->build();
    }
}
