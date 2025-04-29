<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

class ReferenceType
{
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly \Closure $load
    ){}
}