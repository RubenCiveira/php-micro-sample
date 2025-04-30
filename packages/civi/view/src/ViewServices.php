<?php

declare(strict_types=1);

namespace Civi\View;

class ViewServices
{
    public function __construct(
        public readonly ViewConfig $config, 
        public readonly ViewGuard $guard
    ){}
}
