<?php

declare(strict_types=1);

namespace Civi\Security\Guard;

use Civi\Micro\Kernel\AbstractPipeline;
use Civi\Micro\Telemetry\LoggerAwareInterface;
use Civi\Micro\Telemetry\LoggerAwareTrait;

class AccessGuard extends AbstractPipeline implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function canExecute(string $action, string $namespace, string $typeName, array $context, array $original): bool
    {
        $all = $this->getPipelineHandlers(AccessRuleInterface::class);
        $response = $this->runInterfacePipeline($all, fn() => true, 
                [AccessRuleInterface::class, 'canExecute'], 
                [AccessRequestInterfaceHandler::class, 'next'], new AccessRequest($action, $namespace, $typeName, $context, $original));
        $this->logWarning("Trying to check canExecute {$action} on {$namespace} for {$typeName}");
        return $response;
    }
}
