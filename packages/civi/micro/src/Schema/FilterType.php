<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

class FilterType
{
    public function __construct(
        public readonly string $name
    ){}
}