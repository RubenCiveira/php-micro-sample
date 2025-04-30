<?php

declare(strict_types=1);

namespace Civi\Security\Engine;

use Civi\Security\Guard\AccessRequest;
use Civi\Security\Guard\AccessRequestInterfaceHandler;
use Civi\Security\Guard\AccessRuleInterface;

class PolicyEngine implements AccessRuleInterface
{
    public function canExecute(AccessRequest $request, AccessRequestInterfaceHandler $handler): bool
    {
        if( $request->namespace == '#view' && $request->typeName == '/roles' ) {
            return false;
        }
        if( $request->namespace == '#view' && $request->typeName == '/users' ) {
            return false;
        }
        return $handler->next($request);
    }
}