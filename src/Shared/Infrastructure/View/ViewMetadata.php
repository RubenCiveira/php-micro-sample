<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View;

use Ramsey\Uuid\Uuid;

class ViewMetadata
{
    // private array $fields = [];
    private readonly FormMetadata $formMetadata;
    private array $columns = [];
    private array $hideColumns = [];
    private array $hideFilters = [];

    private array $actions = [];

    private array $filters = [];

    public function __construct(private readonly string $name, private readonly string $title, private readonly string $id)
    {
        $this->formMetadata = new FormMetadata();
    }

    public function export() {
        $fields = $this->formMetadata->export()['fields'];
        $columns = $this->columns;
        if( empty($columns) ) {
            foreach($fields as $v) {
                if( !in_array($v['name'], $this->hideColumns) ) {
                    $columns[$v['name']] = [ 'name' => $v['name'], 'label' => $v['label']];
                }
            }
        }
        return [
            'title' => $this->name,
            'description' => $this->title,
            'id' => $this->id,
            'canUpdate' => false, // $this->canUpdate,
            'canDelete' => false, // $this->canDelete,
            'fields' => $fields,
            'filters' => $this->filters,
            'columns' => $columns,
            'actions' => $this->actions,
        ];
    }

    public function addField(string $name, array $info): ViewMetadata
    {
        $this->formMetadata->addField($name, $info);
        return $this;
    }

    public function addColumn(string $name, string $label): ViewMetadata
    {
        $this->columns[$name] = ['name' => $name, 'label' => $label];
        return $this;
    }

    public function addContextualConfirmAction(string $name, string $label, $callback ): ViewMetadata
    {
        $this->actions[$name] = ['name' => $name, 'label' => $label, 'contextual' => true, 'kind' => 'danger', 'callback' => $callback ];
        return $this;
    }

    public function addStandaloneFormAction(string $name, string $label, FormMetadata|array $form, $callback): ViewMetadata
    {
        if( is_array($form) ) {
            $defaults = $this->formMetadata->export()['fields'];
            $formView = new FormMetadata();
            foreach($form as $field) {
                $formView->addField( $field, $defaults[$field] );
            }
        } else {
            $formView = $form;
        }
        $this->actions[$name] = ['name' => $name, 'label' => $label, 'contextual' => false, 'kind' => 'success', 
                'form' => $formView->export()['fields'],
                'callback' => $callback ];
        return $this;

    }
    public function addContextualFormAction(string $name, string $label, FormMetadata|array $form, $callback): ViewMetadata
    {
        if( is_array($form) ) {
            $defaults = $this->formMetadata->export()['fields'];
            $formView = new FormMetadata();
            foreach($form as $field) {
                $formView->addField( $field, $defaults[$field] );
            }
        } else {
            $formView = $form;
        }
        $this->actions[$name] = ['name' => $name, 'label' => $label, 'contextual' => true, 'kind' => 'warn', 
                'form' => $formView->export()['fields'],
                'callback' => $callback ];
        return $this;
    }

    public function exec(array $data): bool
    {
        $processed = false;
        foreach($this->actions as $name => $info) {
            if( isset($data[$name]) && isset($info['callback'])) {
                $data[$this->id] = $data[$name];
                if( !$data[$this->id] ) {
                    $data[$this->id] = Uuid::uuid4()->toString();
                }
                if( $info['callback'] instanceof \Closure) {
                    $info['callback']($data);
                } else {
                    call_user_func( $info['callback'], $data);
                }
                $processed = true;
            }
        }
        return $processed;
    }

    public function addResumeAction(string $name, string $label, string $format): ViewMetadata
    {
        $this->actions[$name] = ['name' => $name, 'label' => $label, 'contextual' => true, 'kind' => 'info',
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

    public function excludeColumn(string $name): ViewMetadata
    {
        $this->hideColumns[] = $name;
        return $this;
    }
    public function addFilter(string $name): ViewMetadata
    {
        $this->filters[$name] = ['name' => $name];
        return $this;
    }
}