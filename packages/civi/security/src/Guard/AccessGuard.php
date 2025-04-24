<?php declare(strict_types=1);

namespace Civi\Security\Guard;

use Civi\Micro\Kernel\AbstractPipeline;

class AccessGuard extends AbstractPipeline
{
    public function canExecute(string $action, string $namespace, string $typeName, array $context, array $original): bool
    {
        return true;
    }
}