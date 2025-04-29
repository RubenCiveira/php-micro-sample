<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

use Ramsey\Uuid\Uuid;

/**
 * @api
 *
 * Represents the schema definition for an entity within the Civi Micro framework.
 * It allows the configuration of fields, columns, actions, and filters associated with an entity.
 * Provides export capabilities for UI representation and action execution logic.
 */
class EntitySchema
{
    /**
     * Internal schema for managing fields.
     *
     * @var ActionSchema
     */
    private readonly ActionSchema $fieldsSchema;
    /**
     * Custom columns definitions to override default field-based columns.
     *
     * @var array<string, array>
     */
    private array $columns = [];
    /**
     * List of field names to exclude from columns automatically generated from fields.
     *
     * @var string[]
     */
    private array $hideColumns = [];

    /**
     * Registered actions associated with the entity (contextual, standalone, resume, etc.).
     *
     * @var array<string, array>
     */
    private array $actions = [];

    /**
     * Registered filters available for querying the entity.
     *
     * @var array<string, array>
     */
    private array $filters = [];

    /**
     * Creates a new EntitySchema.
     *
     * @param string $name Unique entity name.
     * @param string $title Human-readable title of the entity.
     * @param string $id Field used as unique identifier.
     */
    public function __construct(private readonly string $name, private readonly string $title, private readonly string $id)
    {
        $this->fieldsSchema = new ActionSchema();
    }

    /**
     * Exports the entire entity schema structure, including fields, filters, columns, and actions.
     *
     * @return array<string, mixed> The exported schema definition.
     */
    public function export()
    {
        $fields = $this->fieldsSchema->export()['fields'];
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

    /**
     * Adds a new field to the entity.
     *
     * @param string $name Field name.
     * @param array<string, mixed> $info Field configuration details.
     * @return $this
     */
    public function addField(string $name, array $info): EntitySchema
    {
        $this->fieldsSchema->addField($name, $info);
        return $this;
    }

    /**
     * Adds a custom column to the entity.
     *
     * @param string $name Column internal name.
     * @param string $label Column display label.
     * @return $this
     */
    public function addColumn(string $name, string $label): EntitySchema
    {
        $this->columns[$name] = ['name' => $name, 'label' => $label];
        return $this;
    }

    /**
     * Adds a contextual confirmation action, typically used for dangerous operations.
     *
     * @param string $name Action name.
     * @param string $label Action display label.
     * @param callable|\Closure $callback Callback to execute when the action is triggered.
     * @return $this
     */
    public function addContextualConfirmAction(string $name, string $label, $callback): EntitySchema
    {
        $this->actions[$name] = ['name' => $name, 'label' => $label, 'contextual' => true, 'kind' => 'danger', 'callback' => $callback ];
        return $this;
    }

    /**
     * Adds a standalone form action (non-contextual).
     *
     * @param string $name Action name.
     * @param string $label Action display label.
     * @param ActionSchema|array<string> $form Form schema or list of field names to use.
     * @param callable|\Closure $callback Callback to execute.
     * @return $this
     */
    public function addStandaloneFormAction(string $name, string $label, ActionSchema|array $form, $callback): EntitySchema
    {
        if (is_array($form)) {
            $defaults = $this->fieldsSchema->export()['fields'];
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
    
    /**
     * Adds a contextual form action.
     *
     * @param string $name Action name.
     * @param string $label Action display label.
     * @param ActionSchema|array<string> $form Form schema or list of field names to use.
     * @param callable|\Closure $callback Callback to execute.
     * @return $this
     */
    public function addContextualFormAction(string $name, string $label, ActionSchema|array $form, $callback): EntitySchema
    {
        if (is_array($form)) {
            $defaults = $this->fieldsSchema->export()['fields'];
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

    /**
     * Executes the appropriate action based on provided data.
     *
     * @param array<string, mixed> $data Request data.
     * @return string|null Result message, or null if no action was matched.
     */
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

    /**
     * Adds a resume action to present information as downloadable or copyable JSON.
     *
     * @param string $name Action name.
     * @param string $label Action label.
     * @param string $format JavaScript expression to generate JSON content.
     * @return $this
     */
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

    /**
     * Marks a column to be excluded from default columns list.
     *
     * @param string $name Column name.
     * @return $this
     */
    public function excludeColumn(string $name): EntitySchema
    {
        $this->hideColumns[] = $name;
        return $this;
    }

    /**
     * Adds a filter field to the entity.
     *
     * @param string $name Filter field name.
     * @return $this
     */
    public function addFilter(string $name): EntitySchema
    {
        $this->filters[$name] = ['name' => $name];
        return $this;
    }
}
