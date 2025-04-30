<?php

declare(strict_types=1);

namespace Civi\Security\Guard;

class AccessRequest
{
    public function __construct(
        public readonly string $action,
        public readonly string $namespace,
        public readonly string $typeName,
        public readonly array $context,
        public readonly array $original
    ) {
    }
}
