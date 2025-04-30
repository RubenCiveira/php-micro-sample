<?php

declare(strict_types=1);

namespace Civi\Micro\Schema;

use Ramsey\Uuid\Uuid;

/**
 * Represents a builder for defining a dynamic schema of an entity within the Civi Micro framework.
 * This builder allows configuration of fields, columns, filters, and actions, supporting export to a strongly typed immutable TypeSchema.
 *
 * @api
 */
class TypeSchemaBuilder
{
    /**
     * Internal schema for managing fields.
     *
     * @var FieldsetSchemaBuilder
     */
    private readonly FieldsetSchemaBuilder $fieldsSchema;
    /**
     * Custom columns definitions to override default field-based columns.
     *
     * @var array<string, ColumnType>
     */
    private array $columns = [];

    /**
     * List of field names to exclude from columns automatically generated from fields.
     *
     * @var array<string>
     */
    private array $hideColumns = [];

    /**
     * Registered actions associated with the entity (contextual, standalone, resume, etc.).
     *
     * @var array<string, ActionSchema>
     */
    private array $actions = [];

    /**
     * Registered filters available for querying the entity.
     *
     * @var array<string, FilterType>
     */
    private array $filters = [];

    /**
     * Creates a new TypeSchemaBuilder.
     *
     * @param string $name Unique entity name.
     * @param string $title Human-readable title of the entity.
     * @param string $id Field used as unique identifier.
     */
    public function __construct(private readonly string $name, private readonly string $title, private readonly string $id)
    {
        $this->fieldsSchema = new FieldsetSchemaBuilder();
    }

    /**
     * Exports the entire entity schema structure, including fields, filters, columns, and actions.
     *
     * @return TypeSchema The exported schema definition.
     */
    public function export(): TypeSchema
    {
        $fields = $this->fieldsSchema->export();
        $columns = $this->columns;
        if (empty($columns)) {
            foreach ($fields as $v) {
                if (!in_array($v->name, $this->hideColumns)) {
                    if ($v->reference) {
                        $columns[$v->name] = new ColumnType("{$v->name}.{$v->reference->label}", $v->label);
                    } else {
                        $columns[$v->name] = new ColumnType($v->name, $v->label);
                    }
                }
            }
        }
        // Llamada para obtener los datos aqui.
        return new TypeSchema(
            name: $this->name,
            title: $this->title,
            id: $this->id,
            fields: new FieldSchemaCollection($fields),
            filters: new FilterTypeCollection($this->filters),
            columns: new ColumnTypeCollection($columns),
            actions: new ActionSchemaCollection($this->actions)
        );
    }

    /**
     * Adds a new field to the entity.
     *
     * @param string $name Field name.
     * @param array<string, mixed> $info Field configuration details.
     * @return $this
     */
    public function addField(string $name, array $info): TypeSchemaBuilder
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
    public function addColumn(string $name, string $label): TypeSchemaBuilder
    {
        $this->columns[$name] = new ColumnType($name, $label);
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
    public function addContextualConfirmAction(string $name, string $label, $callback): TypeSchemaBuilder
    {
        $this->actions[$name] = new ActionSchema($name, $label, 'danger', true, callback: $callback);
        return $this;
    }

    /**
     * Adds a standalone form action (non-contextual).
     *
     * @param string $name Action name.
     * @param string $label Action display label.
     * @param FieldsetSchemaBuilder|array<string> $form Form schema or list of field names to use.
     * @param callable|\Closure $callback Callback to execute.
     * @return $this
     */
    public function addStandaloneFormAction(string $name, string $label, FieldsetSchemaBuilder|array $form, $callback): TypeSchemaBuilder
    {
        if (is_array($form)) {
            $defaults = $this->fieldsSchema->export();
            $formView = new FieldsetSchemaBuilder();
            foreach ($form as $field) {
                $formView->addField($field, $defaults[$field]);
            }
        } else {
            $formView = $form;
        }
        $this->actions[$name] = new ActionSchema($name, $label, 'success', false, $formView->export(), $callback);
        return $this;
    }

    /**
     * Adds a contextual form action.
     *
     * @param string $name Action name.
     * @param string $label Action display label.
     * @param FieldsetSchemaBuilder|array<string> $form Form schema or list of field names to use.
     * @param callable|\Closure $callback Callback to execute.
     * @return $this
     */
    public function addContextualFormAction(string $name, string $label, FieldsetSchemaBuilder|array $form, callable|\Closure $callback): TypeSchemaBuilder
    {
        if (is_array($form)) {
            $defaults = $this->fieldsSchema->export();
            $formView = new FieldsetSchemaBuilder();
            foreach ($form as $field) {
                $formView->addField($field, $defaults[$field]);
            }
        } else {
            $formView = $form;
        }
        $this->actions[$name] = new ActionSchema($name, $label, 'warn', true, $formView->export(), $callback);
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
            if (isset($data[$name]) && isset($info->callback)) {
                $data[$this->id] = $data[$name];
                if (!$data[$this->id]) {
                    $data[$this->id] = Uuid::uuid4()->toString();
                }
                if ($info->callback instanceof \Closure) {
                    $callback = $info->callback;
                    $callback($data);
                } else {
                    call_user_func($info->callback, $data);
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
    public function addResumeAction(string $name, string $label, string $format): TypeSchemaBuilder
    {
        $this->actions[$name] = new ActionSchema(
            $name,
            $label,
            'info',
            true,
            code: <<<JS
                document.getElementById('jsonContent').textContent = {$format}
                JS,
            template: <<<HTML
                <div id="jsonContent" class="json-display"></div>
                HTML,
            buttons: [
                'copyToClipboard()' => 'Copiar al portapapeles',
                'downloadAuthJson()' => 'Descargar .auth.json'
            ],
            functions: <<<JS
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
                JS
        );
        return $this;
    }

    /**
     * Marks a column to be excluded from default columns list.
     *
     * @param string $name Column name.
     * @return $this
     */
    public function excludeColumn(string $name): TypeSchemaBuilder
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
    public function addFilter(string $name): TypeSchemaBuilder
    {
        $this->filters[$name] = new FilterType($name);
        return $this;
    }
}
