<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

/**
 * Represents an immutable schema definition for an entity.
 *
 * This class encapsulates all metadata necessary to describe
 * the structure, fields, columns, filters, and actions available
 * for a specific entity within the application.
 *
 * @api
 */
class TypeSchema
{
    /**
     * Constructs a new TypeSchema instance.
     *
     * @param string $name Unique name identifying the entity type.
     * @param string $title Human-readable title describing the entity.
     * @param string $id Name of the field used as the primary identifier.
     * @param FieldSchemaCollection $fields Collection of field definitions associated with the entity.
     * @param FilterTypeCollection $filters Collection of filter definitions available for querying the entity.
     * @param ColumnTypeCollection $columns Collection of column definitions used for displaying data.
     * @param ActionSchemaCollection $actions Collection of actions (contextual, form-based, or resume) applicable to the entity.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $title,
        public readonly string $id,
        public readonly FieldSchemaCollection $fields,
        public readonly FilterTypeCollection $filters,
        public readonly ColumnTypeCollection $columns,
        public readonly ActionSchemaCollection $actions
    ) {
    }

    /**
     * Retrieves a field schema definition by its name.
     *
     * @param string $name The name of the field to retrieve.
     * @return FieldSchema|null Returns the FieldSchema object if found, or null if no field matches the given name.
     */
    public function getField(string $name): ?FieldSchema
    {
        return $this->fields->findByName($name);
    }
}
