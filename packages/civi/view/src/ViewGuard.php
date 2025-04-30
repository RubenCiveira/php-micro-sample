<?php

declare(strict_types=1);

namespace Civi\View;

use Civi\Security\Guard\AccessGuard;

class ViewGuard
{
    public function __construct(private readonly AccessGuard $guard)
    {

    }
    public function canView(string $viewName): bool
    {
        if( $viewName == '/roles' ) {
            return false;
        }
        return $this->guard->canExecute('view', '#view', $viewName, [], []);
    }
}
