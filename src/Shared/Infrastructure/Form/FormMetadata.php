<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\Form;

class FormMetadata
{
    private array $fields = [];
    private array $columns = [];
    private array $hideColumns = [];
    private array $hideFilters = [];

    private array $actions = [];

    private array $filters = [];

    private bool $canUpdate = true;
    private bool $canDelete = true;

    public string $createSchema = '';
    public string $updateSchema = '';
    public function __construct(private readonly string $name, private readonly string $title, private readonly string $id)
    {
    }

    public function export() {
        $columns = $this->columns;
        if( empty($columns) ) {
            foreach($this->fields as $v) {
                if( !in_array($v['name'], $this->hideColumns) ) {
                    $columns[$v['name']] = [ 'name' => $v['name'], 'label' => $v['label']];
                }
            }
        }
        return [
            'title' => $this->name,
            'description' => $this->title,
            'createSchmea' => $this->createSchema,
            'updateSchmea' => $this->updateSchema,
            'id' => $this->id,
            'canUpdate' => $this->canUpdate,
            'canDelete' => $this->canDelete,
            'fields' => $this->fields,
            'filters' => $this->filters,
            'columns' => $columns,
            'actions' => $this->actions,
        ];
    }


    public function markCalculated(array $names): FormMetadata
    {
        foreach($names as $name) {
            $this->fields[$name]['calculated'] = true;
        }
        return $this;
    }
    public function markReadonly(array $names): FormMetadata
    {
        foreach($names as $name) {
            $this->fields[$name]['readonly'] = true;
        }
        return $this;
    }
    public function addRequiredTextField(string $name, string $label): FormMetadata
    {
        $this->fields[$name] = ['name' => $name, 'label' => $label, 'required' => true, 'type' => 'text' ];
        return $this;
    }
    public function addTextField(string $name, string $label): FormMetadata
    {
        $this->fields[$name] = ['name' => $name, 'label' => $label, 'required' => false, 'type' => 'text' ];
        return $this;
    }
    public function addRequiredTextareaField(string $name, string $label): FormMetadata
    {
        $this->fields[$name] = ['name' => $name, 'label' => $label, 'required' => true, 'type' => 'textare' ];
        return $this;
    }
    public function addTextareaField(string $name, string $label): FormMetadata
    {
        $this->fields[$name] = ['name' => $name, 'label' => $label, 'required' => false, 'type' => 'textare' ];
        return $this;
    }
    public function addRequiedDateField(string $name, string $label): FormMetadata
    {
        $this->fields[$name] = ['name' => $name, 'label' => $label, 'required' => true, 'type' => 'date' ];
        return $this;
    }
    public function addDateField(string $name, string $label): FormMetadata
    {
        $this->fields[$name] = ['name' => $name, 'label' => $label, 'required' => false, 'type' => 'date' ];
        return $this;
    }
    public function addRequiredOptionsField(string $name, string $label, array $options): FormMetadata
    {
        $this->fields[$name] = ['name' => $name, 'label' => $label, 'required' => true, 'type' => 'options', 'options' => $options ];
        return $this;
    }
    public function addOptionsField(string $name, string $label, array $options): FormMetadata
    {
        $this->fields[$name] = ['name' => $name, 'label' => $label, 'required' => false, 'type' => 'options', 'options' => $options ];
        return $this;
    }
    public function addRequiredPasswordField(string $name, string $label): FormMetadata
    {
        $this->fields[$name] = ['name' => $name, 'label' => $label, 'required' => true, 'type' => 'password' ];
        return $this;
    }
    public function addPasswordField(string $name, string $label): FormMetadata
    {
        $this->fields[$name] = ['name' => $name, 'label' => $label, 'required' => false, 'type' => 'password' ];
        return $this;
    }

    public function addColumn(string $name, string $label): FormMetadata
    {
        $this->columns[$name] = ['name' => $name, 'label' => $label];
        return $this;
    }

    public function addResumeAction(string $name, string $label, string $format): FormMetadata
    {
        $this->actions[$name] = ['name' => $name, 'label' => $label, 'contextual' => true,
            'code' => <<<JS
                document.getElementById('jsonContent').textContent = {$format}
                JS, 
            'template' => <<<HTML
                <div id="jsonContent" class="json-display"></div>
                <div style="display: flex; justify-content: space-between;">
                    <button class="copy-btn" onclick="copyToClipboard()">Copiar al Portapapeles</button>
                    <button class="btn" onclick="downloadAuthJson()">Descargar .auth.json</button>
                </div>
                HTML,
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

    public function excludeColumn(string $name): FormMetadata
    {
        $this->hideColumns[] = $name;
        return $this;
    }
    public function addFilter(string $name): FormMetadata
    {
        $this->filters[$name] = ['name' => $name];
        return $this;
    }
}