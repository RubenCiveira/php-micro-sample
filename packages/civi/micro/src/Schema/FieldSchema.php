<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

/**
 * Represents an immutable definition of a field within an ActionSchema.
 *
 * This class models a field's properties such as its name, type, label,
 * validation rules (required, readonly, calculated), enumerated values,
 * and optional reference to another entity.
 */
class FieldSchema
{
    /**
     * Creates a new immutable FieldSchema instance.
     *
     * @param string $name The internal name of the field (e.g., 'username', 'email').
     * @param string $type The data type of the field (e.g., 'string', 'integer', 'boolean').
     * @param string $label A human-readable label for the field to be used in user interfaces.
     * @param bool $required Whether the field must be filled (true) or can be left empty (false).
     * @param bool $calculated (Optional) Whether the field value is calculated rather than entered manually. Defaults to false.
     * @param bool $readonly (Optional) Whether the field should be treated as read-only. Defaults to false.
     * @param array<string>|null $enum (Optional) A set of valid values the field can take, if applicable.
     * @param ReferenceType|null $reference (Optional) A reference to another entity for relationship-based fields.
     */
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
