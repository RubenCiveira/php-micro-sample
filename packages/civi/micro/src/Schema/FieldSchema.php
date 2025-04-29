<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

/**
 * Represents an immutable field definition inside an ActionSchema.
 *
 * @api
 */
class FieldSchema
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $label,
        public readonly bool $required,
        public readonly bool $calculated = false,
        public readonly bool $readonly = false,
        public readonly ?array $enum = null,
        public readonly ?ReferenceType $reference = null,
    ) {
    }
}