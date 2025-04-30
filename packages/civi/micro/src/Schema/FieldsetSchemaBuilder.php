<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

/**
 * Represents a dynamic schema builder for a set of fields,
 * allowing fields to be added, marked as calculated, or marked as readonly.
 *
 * This builder supports both creating fields from simple arrays or
 * from fully-typed FieldSchema objects.
 *
 * @api
 */
class FieldsetSchemaBuilder
{
    /**
     * Holds the collection of fields in the schema.
     *
     * @var array<string, FieldSchema> Associative array of fields indexed by their name.
     */
    private array $fields = [];

    /**
     * Exports the current structure of the schema.
     *
     * @return array<string, FieldSchema> The complete list of fields currently in the schema.
     */
    public function export(): array
    {
        return $this->fields;
    }

    /**
     * Marks the specified fields as "calculated".
     *
     * This sets the `calculated` flag to true on each specified field,
     * without modifying any other properties.
     *
     * @param string[] $names List of field names to mark as calculated.
     * @return $this Fluent interface.
     */
    public function markCalculated(array $names): FieldsetSchemaBuilder
    {
        foreach ($names as $name) {
            if (isset($this->fields[$name])) {
                $field = $this->fields[$name];
                $this->fields[$name] = $field->asCalculated();
            }
        }
        return $this;
    }

    /**
     * Marks the specified fields as "readonly".
     *
     * This sets the `readonly` flag to true on each specified field,
     * without modifying any other properties.
     *
     * @param string[] $names List of field names to mark as readonly.
     * @return $this Fluent interface.
     */
    public function markReadonly(array $names): FieldsetSchemaBuilder
    {
        foreach ($names as $name) {
            if (isset($this->fields[$name])) {
                $field = $this->fields[$name];
                $this->fields[$name] = $field->asReadonly();
            }
        }
        return $this;
    }

    /**
     * Adds a new field to the schema.
     *
     * You can add either a FieldSchema object or an array describing the field.
     * When passing an array, default values are automatically assigned if not specified:
     * - `type` defaults to 'text'
     * - `label` defaults to the capitalized field name
     * - `required` defaults to false
     *
     * If a `reference` is provided in the array, it is converted into a ReferenceType object.
     *
     * @param string $name The field name.
     * @param array<string, mixed>|FieldSchema $info The field definition as an array or a prebuilt FieldSchema.
     * @return $this Fluent interface.
     */
    public function addField(string $name, array|FieldSchema $info): FieldsetSchemaBuilder
    {
        if (is_array($info)) {
            $reference = null;
            if (isset($info['reference'])) {
                $reference = new ReferenceType(
                    $info['reference']['id'],
                    $info['reference']['label'],
                    $info['reference']['load']
                );
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
