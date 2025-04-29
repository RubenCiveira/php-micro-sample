<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

/**
 * Represents an immutable field definition inside an ActionSchema.
 *
 * @api
 */
class ActionSchema
{
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly string $kind,
        public readonly bool $contextual,
        public readonly array $fields = [],
        public readonly mixed $callback = null,
        public readonly ?string $code = null,
        public readonly ?string $template = null,
        public readonly ?array $buttons = null,
        public readonly ?string $functions = null,
    ) {
    }
}