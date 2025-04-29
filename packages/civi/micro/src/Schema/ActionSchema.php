<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

/**
 * @api
 */
class ActionSchema
{
    private array $fields = [];

    public function export(): array
    {
        return [ 'fields' => $this->fields ];
    }

    public function markCalculated(array $names): ActionSchema
    {
        foreach ($names as $name) {
            $this->fields[$name]['calculated'] = true;
        }
        return $this;
    }
    public function markReadonly(array $names): ActionSchema
    {
        foreach ($names as $name) {
            $this->fields[$name]['readonly'] = true;
        }
        return $this;
    }
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
    // public function addRequiredTextField(string $name, string $label): EntitySchema
    // {
    //     $this->fields[$name] = ['name' => $name, 'label' => $label, 'required' => true, 'type' => 'text' ];
    //     return $this;
    // }
    // public function addTextField(string $name, string $label): EntitySchema
    // {
    //     $this->fields[$name] = ['name' => $name, 'label' => $label, 'required' => false, 'type' => 'text' ];
    //     return $this;
    // }
    // public function addRequiredTextareaField(string $name, string $label): EntitySchema
    // {
    //     $this->fields[$name] = ['name' => $name, 'label' => $label, 'required' => true, 'type' => 'textare' ];
    //     return $this;
    // }
    // public function addTextareaField(string $name, string $label): EntitySchema
    // {
    //     $this->fields[$name] = ['name' => $name, 'label' => $label, 'required' => false, 'type' => 'textare' ];
    //     return $this;
    // }
    // public function addRequiedDateField(string $name, string $label): EntitySchema
    // {
    //     $this->fields[$name] = ['name' => $name, 'label' => $label, 'required' => true, 'type' => 'date' ];
    //     return $this;
    // }
    // public function addDateField(string $name, string $label): EntitySchema
    // {
    //     $this->fields[$name] = ['name' => $name, 'label' => $label, 'required' => false, 'type' => 'date' ];
    //     return $this;
    // }
    // public function addRequiredOptionsField(string $name, string $label, array $options): EntitySchema
    // {
    //     $this->fields[$name] = ['name' => $name, 'label' => $label, 'required' => true, 'type' => 'options', 'options' => $options ];
    //     return $this;
    // }
    // public function addOptionsField(string $name, string $label, array $options): EntitySchema
    // {
    //     $this->fields[$name] = ['name' => $name, 'label' => $label, 'required' => false, 'type' => 'options', 'options' => $options ];
    //     return $this;
    // }
    // public function addRequiredPasswordField(string $name, string $label): EntitySchema
    // {
    //     $this->fields[$name] = ['name' => $name, 'label' => $label, 'required' => true, 'type' => 'password' ];
    //     return $this;
    // }
    // public function addPasswordField(string $name, string $label): EntitySchema
    // {
    //     $this->fields[$name] = ['name' => $name, 'label' => $label, 'required' => false, 'type' => 'password' ];
    //     return $this;
    // }
}
