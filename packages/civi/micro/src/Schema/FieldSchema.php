<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

/**
 * Represents an immutable definition of a field within an ActionSchema.
 *
 * This class models a field's properties such as its name, type, label,
 * validation rules (required, readonly, calculated), enumerated values,
 * and optional reference to another entity.
 *
 * @api
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

    /**
     * Returns a new FieldSchema instance with the 'calculated' flag set to true.
     *
     * This method is used to indicate that the field value should be calculated
     * automatically instead of being manually entered by the user. Other properties
     * of the field remain unchanged.
     *
     * @return FieldSchema A new instance with 'calculated' set to true.
     */
    public function asReadonly(): FieldSchema
    {
        return new FieldSchema(
            name: $this->name,
            type: $this->type,
            label: $this->label,
            required: $this->required,
            calculated: $this->readonly, // calculated
            readonly: true, 
            enum: $this->enum,
            reference: $this->reference
        );
    }

    /**
     * Returns a new FieldSchema instance with the 'readonly' flag set to true.
     *
     * This method is used to indicate that the field should be treated as read-only,
     * meaning its value cannot be modified by the user. The 'calculated' property
     * retains its original value, and all other field properties remain unchanged.
     *
     * @return FieldSchema A new instance with 'readonly' set to true.
     */
    public function asCalculated(): FieldSchema
    {
        return new FieldSchema(
            name: $this->name,
            type: $this->type,
            label: $this->label,
            required: $this->required,
            calculated: true,
            readonly: $this->readonly,
            enum: $this->enum,
            reference: $this->reference
        );
    }
}
