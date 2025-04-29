<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

/**
 * Represents a dynamic schema for an action, allowing fields to be defined, 
 * marked as calculated, or set as readonly.
 *
 * @api
 */
class ActionSchema
{
    /**
     * @var array<string, array<string, mixed>> Stores the field definitions.
     */
    private array $fields = [];

    /**
     * Exports the current schema structure.
     *
     * @return array<string, array<string, mixed>> An array containing all defined fields.
     */
    public function export(): array
    {
        return [ 'fields' => $this->fields ];
    }

    /**
     * Marks a set of fields as calculated.
     *
     * @param string[] $names List of field names to mark as calculated.
     * @return $this
     */
    public function markCalculated(array $names): ActionSchema
    {
        foreach ($names as $name) {
            $this->fields[$name]['calculated'] = true;
        }
        return $this;
    }

    /**
     * Marks a set of fields as readonly.
     *
     * @param string[] $names List of field names to mark as readonly.
     * @return $this
     */
    public function markReadonly(array $names): ActionSchema
    {
        foreach ($names as $name) {
            $this->fields[$name]['readonly'] = true;
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
    public function addField(string $name, array $info): ActionSchema
    {
        $info['name'] = $name;
        if (!isset($info['required'])) {
            $info['required'] = false;
        }
        if (!isset($info['type'])) {
            $info['type'] = 'text';
        }
        if (!isset($info['label'])) {
            $info['label'] = ucfirst($name);
        }
        $this->fields[$name] = $info;
        return $this;
    }
}
