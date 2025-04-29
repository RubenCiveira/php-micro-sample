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
     * @param FieldSchema[] $fields List of field definitions.
     * @param array<string, FilterType> $filters Available filters.
     * @param array<string, ColumnType> $columns Display columns.
     * @param array<string, ActionSchema> $actions Available actions (forms, resumes, confirm actions).
     */
    public function __construct(
        public readonly string $name,
        public readonly string $title,
        public readonly string $id,
        public readonly array $fields,
        public readonly array $filters,
        public readonly array $columns,
        public readonly array $actions
    ) {}

    /**
     * Exports the TypeSchema as an associative array.
     *
     * @return array<string, mixed>
     */
    public function export(): array
    {
        return [
            'title' => $this->name,
            'description' => $this->title,
            'id' => $this->id,
            'fields' => array_map(fn(FieldSchema $field) => get_object_vars($field), $this->fields),
            'filters' => $this->filters,
            'columns' => $this->columns,
            'actions' => $this->actions,
        ];
    }
}
