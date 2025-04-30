<?php

declare(strict_types=1);

namespace Civi\Security\Policy;

use Civi\Security\Guard\AccessRequest;
use Civi\Security\Guard\AccessRequestInterfaceHandler;
use Civi\Security\Guard\AccessRuleInterface;

class PolicyEngine implements AccessRuleInterface
{
    public function canExecute(AccessRequest $request, AccessRequestInterfaceHandler $handler): bool
    {
        return $handler->next($request);
    }
}