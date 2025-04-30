<?php

declare(strict_types=1);

namespace Civi\Security\Guard;

interface AccessRuleInterface
{
    public function canExecute(AccessRequest $request, AccessRequestInterfaceHandler $handler): bool;
}