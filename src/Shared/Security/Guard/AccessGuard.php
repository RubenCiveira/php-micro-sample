<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Security\Guard;

use Civi\Repomanager\Shared\Kernel\AbstractPipeline;

class AccessGuard extends AbstractPipeline
{
    public function canExecute(string $action, string $namespace, string $typeName, array $context): bool
    {
        return true;
    }
}