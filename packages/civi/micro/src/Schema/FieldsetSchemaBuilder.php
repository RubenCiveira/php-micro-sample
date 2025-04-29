<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

/**
 * Represents a dynamic schema for an action, allowing fields to be defined, 
 * marked as calculated, or set as readonly.
 *
 * @api
 */
class FieldsetSchemaBuilder
{
    /**
     * @var array<string, FieldSchema>
     */
    private array $fields = [];

    /**
     * Exports the current schema structure.
     *
     * @return array<string, FieldSchema> 
     */
    public function export(): array
    {
        return $this->fields;
    }

    /**
     * Marks a set of fields as calculated.
     *
     * @param string[] $names List of field names to mark as calculated.
     * @return $this
     */
    public function markCalculated(array $names): FieldsetSchemaBuilder
    {
        foreach ($names as $name) {
            if (isset($this->fields[$name])) {
                $field = $this->fields[$name];
                $this->fields[$name] = new FieldSchema(
                    $field->name,
                    $field->type,
                    $field->label,
                    $field->required,
                    true, // calculated
                    $field->readonly,
                    $field->reference
                );
            }
        }
        return $this;
    }

    /**
     * Marks a set of fields as readonly.
     *
     * @param string[] $names List of field names to mark as readonly.
     * @return $this
     */
    public function markReadonly(array $names): FieldsetSchemaBuilder
    {
        foreach ($names as $name) {
            if (isset($this->fields[$name])) {
                $field = $this->fields[$name];
                $this->fields[$name] = new FieldSchema(
                    $field->name,
                    $field->type,
                    $field->label,
                    $field->required,
                    $field->calculated,
                    true, // readonly
                    $field->reference
                );
            }
        }
        return $this;
    }

    /**
     * Adds a new field to the schema.
     *
     * If no type, label, or required flag is provided, default values will be assigned:
     * - 'type' defaults to 'text'
     * - 'label' defaults to the field name with the first letter capitalized
     * - 'required' defaults to false
     *
     * @param string $name The field name.
     * @param array<string, mixed> $info The field properties.
     * @return $this
     */
    public function addField(string $name, array|FieldSchema $info): FieldsetSchemaBuilder
    {
        if( is_array($info) ) {
            $reference = null;
            if( isset($info['reference']) ) {
                $reference = new ReferenceType($info['reference']['id'], 
                    $info['reference']['label'], $info['reference']['load']);
            }
            $info = new FieldSchema(
                name: $name,
                type: $info['type'] ?? 'text',
                label: $info['label'] ?? ucfirst($name),
                required: $info['required'] ?? false,
                calculated: $info['calculated'] ?? false,
                readonly: $info['readonly'] ?? false,
                enum: $info['enum'] ?? [],
                reference: $reference,
            );
        }
        $this->fields[$name] = $info;
        return $this;
    }
}
