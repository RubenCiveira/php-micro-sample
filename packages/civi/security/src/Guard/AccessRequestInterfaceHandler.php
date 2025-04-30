<?php

declare(strict_types=1);

namespace Civi\Security\Guard;

interface AccessRequestInterfaceHandler
{
    public function next(AccessRequest $request): bool;
}
