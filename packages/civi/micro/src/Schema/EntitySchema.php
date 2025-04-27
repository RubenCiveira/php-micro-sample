<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

use Ramsey\Uuid\Uuid;

class EntitySchema
{
    // private array $fields = [];
    private readonly ActionSchema $ActionSchema;
    private array $columns = [];
    private array $hideColumns = [];
    private array $hideFilters = [];

    private array $actions = [];

    private array $filters = [];

    public function __construct(private readonly string $name, private readonly string $title, private readonly string $id)
    {
        $this->ActionSchema = new ActionSchema();
    }

    public function export()
    {
        $fields = $this->ActionSchema->export()['fields'];
        $columns = $this->columns;
        if (empty($columns)) {
            foreach ($fields as $v) {
                if (!in_array($v['name'], $this->hideColumns)) {
                    if (isset($v['reference'])) {
                        $columns[$v['name']] = [ 'name' => "{$v['name']}.{$v['reference']['label']}", 'label' => $v['label']];
                    } else {
                        $columns[$v['name']] = [ 'name' => $v['name'], 'label' => $v['label']];
                    }
                }
            }
        }
        // Llamada para obtener los datos aqui.
        return [
            'title' => $this->name,
            'description' => $this->title,
            'id' => $this->id,
            'fields' => $fields,
            'filters' => $this->filters,
            'columns' => $columns,
            'actions' => $this->actions,
        ];
    }

    public function addField(string $name, array $info): EntitySchema
    {
        $this->ActionSchema->addField($name, $info);
        return $this;
    }

    public function addColumn(string $name, string $label): EntitySchema
    {
        $this->columns[$name] = ['name' => $name, 'label' => $label];
        return $this;
    }

    public function addContextualConfirmAction(string $name, string $label, $callback): EntitySchema
    {
        $this->actions[$name] = ['name' => $name, 'label' => $label, 'contextual' => true, 'kind' => 'danger', 'callback' => $callback ];
        return $this;
    }

    public function addStandaloneFormAction(string $name, string $label, ActionSchema|array $form, $callback): EntitySchema
    {
        if (is_array($form)) {
            $defaults = $this->ActionSchema->export()['fields'];
            $formView = new ActionSchema();
            foreach ($form as $field) {
                $formView->addField($field, $defaults[$field]);
            }
        } else {
            $formView = $form;
        }
        $this->actions[$name] = ['name' => $name, 'label' => $label, 'contextual' => false, 'kind' => 'success',
                'form' => $formView->export()['fields'],
                'callback' => $callback ];
        return $this;

    }
    public function addContextualFormAction(string $name, string $label, ActionSchema|array $form, $callback): EntitySchema
    {
        if (is_array($form)) {
            $defaults = $this->ActionSchema->export()['fields'];
            $formView = new ActionSchema();
            foreach ($form as $field) {
                $formView->addField($field, $defaults[$field]);
            }
        } else {
            $formView = $form;
        }
        $this->actions[$name] = ['name' => $name, 'label' => $label, 'contextual' => true, 'kind' => 'warn',
                'form' => $formView->export()['fields'],
                'callback' => $callback ];
        return $this;
    }

    public function exec(array $data): ?string
    {
        $processed = null;
        foreach ($this->actions as $name => $info) {
            if (isset($data[$name]) && isset($info['callback'])) {
                $data[$this->id] = $data[$name];
                if (!$data[$this->id]) {
                    $data[$this->id] = Uuid::uuid4()->toString();
                }
                if ($info['callback'] instanceof \Closure) {
                    $info['callback']($data);
                } else {
                    call_user_func($info['callback'], $data);
                }
                $processed = "Se ha {$name} correctamente";
            }
        }
        return $processed;
    }

    public function addResumeAction(string $name, string $label, string $format): EntitySchema
    {
        $this->actions[$name] = ['name' => $name, 'label' => $label, 'contextual' => true, 'kind' => 'info',
            'code' => <<<JS
                document.getElementById('jsonContent').textContent = {$format}
                JS,
            'template' => <<<HTML
                <div id="jsonContent" class="json-display"></div>
                HTML,
            'buttons' => [
                'copyToClipboard()' => 'Copiar al portapapeles',
                'downloadAuthJson()' => 'Descargar .auth.json'
            ],
            'functions' => <<<JS
                function copyToClipboard() {
                    const jsonContent = document.getElementById('jsonContent').textContent;
                    navigator.clipboard.writeText(jsonContent)
                        .then(() => {
                            alert('Contenido copiado al portapapeles');
                        })
                        .catch(err => {
                            console.error('Error al copiar: ', err);
                            alert('No se pudo copiar al portapapeles');
                        });
                }
                
                function downloadAuthJson() {
                    const jsonContent = document.getElementById('jsonContent').textContent;
                    const blob = new Blob([jsonContent], { type: 'application/json' });
                    const url = URL.createObjectURL(blob);
                    
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = '.auth.json';
                    document.body.appendChild(a);
                    a.click();
                    
                    // Limpiar
                    setTimeout(() => {
                        document.body.removeChild(a);
                        window.URL.revokeObjectURL(url);
                    }, 0);
                }
                JS];
        return $this;
    }

    public function excludeColumn(string $name): EntitySchema
    {
        $this->hideColumns[] = $name;
        return $this;
    }
    public function addFilter(string $name): EntitySchema
    {
        $this->filters[$name] = ['name' => $name];
        return $this;
    }
}
