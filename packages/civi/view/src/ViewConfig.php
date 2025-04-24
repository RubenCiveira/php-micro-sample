<?php

declare(strict_types=1);

namespace Civi\View;

class ViewConfig
{
    public function __construct(
        public readonly string $rootTemplateDir
    ) {
    }
}