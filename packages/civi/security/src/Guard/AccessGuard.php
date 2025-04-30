<?php

declare(strict_types=1);

namespace Civi\Security\Guard;

use Civi\Micro\Kernel\AbstractPipeline;
use Civi\Micro\Telemetry\LoggerAwareInterface;
use Civi\Micro\Telemetry\LoggerAwareTrait;
use Closure;

class AccessGuard extends AbstractPipeline implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function canExecute(string $action, string $namespace, string $typeName, array $context, array $original): bool
    {
        $all = $this->getPipelineHandlers(AccessRuleInterface::class);
        $response = $this->runInterfacePipeline($all, 
                [AccessRuleInterface::class, 'canExecute'], 
                fn() => true, 
                fn($next) => new class ($next) implements AccessRequestInterfaceHandler {
                    public function __construct(private Closure $delegate) {}
                    public function next(AccessRequest $request): bool
                    {
                        return ($this->delegate)($request);
                    }
                },
                 new AccessRequest($action, $namespace, $typeName, $context, $original));
        $this->logWarning("Trying to check canExecute {$action} on {$namespace} for {$typeName}");
        return $response;
    }
}
