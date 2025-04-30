<?php

declare(strict_types=1);

namespace Civi\Security\Guard;

use Civi\Micro\Kernel\AbstractPipeline;
use Civi\Micro\Telemetry\LoggerAwareInterface;
use Civi\Micro\Telemetry\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class AccessGuard extends AbstractPipeline implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function canExecute(string $action, string $namespace, string $typeName, array $context, array $original): bool
    {
        $this->logWarning("Trying to check canExecute {$action} on {$namespace} for {$typeName}");
        return true;
    }
}
