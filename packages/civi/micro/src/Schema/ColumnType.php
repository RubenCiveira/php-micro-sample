<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

class ColumnType
{
    public function __construct(
        public readonly string $name,
        public readonly string $label
    ){}
}