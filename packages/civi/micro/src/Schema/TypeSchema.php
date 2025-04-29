<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

/**
 * Represents an immutable schema definition for an entity.
 *
 * @api
 */
class TypeSchema
{
    /**
     * @param string $name Entity unique name.
     * @param string $title Human-readable entity title.
     * @param string $id Unique identifier field.
     * @param FieldSchemaCollection $fields List of field definitions.
     * @param FilterTypeCollection $filters Available filters.
     * @param ColumnTypeCollection $columns Display columns.
     * @param ActionSchemaCollection $actions Available actions (forms, resumes, confirm actions).
     */
    public function __construct(
        public readonly string $name,
        public readonly string $title,
        public readonly string $id,
        public readonly FieldSchemaCollection $fields,
        public readonly FilterTypeCollection $filters,
        public readonly ColumnTypeCollection $columns,
        public readonly ActionSchemaCollection $actions
    ) {}


}
