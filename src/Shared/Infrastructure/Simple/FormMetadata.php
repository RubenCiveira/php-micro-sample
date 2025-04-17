<?php

namespace Civi\Repomanager\Shared\Infrastructure\Simple;

class FormMetadata
{
    private array $fields = [];
    private array $columns = [];
    private array $hideColumns = [];
    private array $hideFilters = [];

    private bool $canUpdate = true;
    private bool $canDelete = true;
    public function __construct(private readonly string $name, private readonly string $title, private readonly string $id)
    {
    }

    public function export() {
        $columns = $this->columns;
        if( empty($columns) ) {
            foreach($this->fields as $v) {
                if( !in_array($v['name'], $this->hideColumns) ) {
                    $columns[] = [ 'name' => $v['name'], 'label' => $v['label']];
                }
            }
        }
        return [
            'title' => $this->name,
            'description' => $this->title,
            'id' => $this->id,
            'canUpdate' => $this->canUpdate,
            'canDelete' => $this->canDelete,
            'fields' => $this->fields,
            'columns' => $columns
        ];
    }
    public function addRequiredTextField(string $name, string $label): FormMetadata
    {
        $this->fields[] = ['name' => $name, 'label' => $label, 'required' => true, 'type' => 'text' ];
        return $this;
    }
    public function addRequiredTextareaField(string $name, string $label): FormMetadata
    {
        $this->fields[] = ['name' => $name, 'label' => $label, 'required' => true, 'type' => 'textare' ];
        return $this;
    }
    public function addRequiedDateField(string $name, string $label): FormMetadata
    {
        $this->fields[] = ['name' => $name, 'label' => $label, 'required' => true, 'type' => 'date' ];
        return $this;
    }
    public function addRequiredOptionsField(string $name, string $label, array $options): FormMetadata
    {
        $this->fields[] = ['name' => $name, 'label' => $label, 'required' => true, 'type' => 'options', 'options' => $options ];
        return $this;
    }

    public function addTextField(string $name, string $label): FormMetadata
    {
        $this->fields[] = ['name' => $name, 'label' => $label, 'required' => false, 'type' => 'text' ];
        return $this;
    }
    public function addTextareaField(string $name, string $label): FormMetadata
    {
        $this->fields[] = ['name' => $name, 'label' => $label, 'required' => false, 'type' => 'textare' ];
        return $this;
    }
    public function addDateField(string $name, string $label): FormMetadata
    {
        $this->fields[] = ['name' => $name, 'label' => $label, 'required' => false, 'type' => 'date' ];
        return $this;
    }
    public function addOptionsField(string $name, string $label, array $options): FormMetadata
    {
        $this->fields[] = ['name' => $name, 'label' => $label, 'required' => false, 'type' => 'options', 'options' => $options ];
        return $this;
    }

    public function addColumn(string $name, string $label): FormMetadata
    {
        $this->columns[] = ['name' => $name, 'label' => $label];
        return $this;
    }

    public function excludeColumn(string $name): FormMetadata
    {
        $this->hideColumns[] = $name;
        return $this;
    }
    public function addFilter(): FormMetadata
    {
        return $this;
    }
}