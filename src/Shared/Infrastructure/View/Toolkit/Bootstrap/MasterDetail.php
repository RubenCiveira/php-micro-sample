<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View\Toolkit\Bootstrap;

class MasterDetail
{
    private readonly string $id;
    private array $actions;

    public function __construct(
        private readonly ?array $meta,
        private readonly ?array $values,
        private readonly ?string $url,
        private readonly string $body
    ) {
        $this->id = 'md' . time();
        $this->actions = [];
        foreach($meta['actions'] as $action) {
            $this->actions[] = new Action( $meta, $action );
        }
    }
    public function render(): string
    {
        return "<div class=\"container\">"
            . $this->header()
            . "<div class=\"container\">"
            . "</div>"
            . "</div>";
    }

    private function header(): string
    {
        return $this->title()
            . "<div class=\"container\">"
            . $this->writeMainMenu()
            . $this->writeFilters()
            . $this->writeSearchBox()
            . "</div>"
            . $this->writeTable()
            . "<script>\n"
            . $this->writeData()
            . "</script>"
            . $this->writeDialogs()
            . "";
    }

    private function writeData(): string
    {
        $rows = "";
        foreach($this->values as $value) {
            $rows .= "{ {$this->meta['id']}: \"{$value[$this->meta['id']]}\",";
            foreach($this->meta['fields'] as $field) {
                if( $field['type'] == 'date' ) {
                    $rows .= "{$field['name']}: \"" . $this->dateFormat( $value[$field['name']] ) . "\", ";
                } else {
                    $rows .= "{$field['name']}: \"{$value[$field['name']]}\", ";
                }
            }
            $rows .= "},\n";
        }
        $cells = "";
        foreach($this->meta['columns'] as $column) {
            $cells .= "const td{$column['name']} = document.createElement('td');\n";
            $field = $this->field( $column );
            if( $field['type'] == 'date') {
                $cells .= "
                    const [yyyy{$column['name']}, mm{$column['name']}, dd{$column['name']}] = item.{$column['name']}.split('-');\n 
                    td{$column['name']}.textContent = `\${dd{$column['name']}-\${mm{$column['name']}-\${yyyy{$column['name']}`;\n";
            } else {
                $cells .= "td{$column['name']}.textContent = item.{$column['name']}\n";
            }
            $cells .= "tr.appendChild(td{$column['name']});\n";
        }
        return "\nconst values = [\n".$rows."]\n;
    function load(values, forEmpty) {
        const table = document.getElementById('table-content{$this->id}');
        const tbody = table.querySelector('tbody');
        const thead = table.querySelector('thead tr');
        tbody.innerHTML = ''; 
        const columnCount = thead ? thead.children.length : 1;
        if (!values || values.length === 0) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = columnCount;
            td.style.textAlign = 'center';
            td.textContent = forEmpty;
            tr.appendChild(td);
            tbody.appendChild(tr);
            return;
        }

        values.forEach(item => {
            const tr = document.createElement('tr');
            {$cells}
            const tdActions = document.createElement('td');
            tdActions.className = 'cell-actions';
            ".$this->buildContextualMenu("tdActions", "item")."
            tr.appendChild(tdActions);
            tbody.appendChild(tr);
        });
    }
    load( values, 'No hay valores registradas' );";
    }

    private function writeDialogs(): string
    {
        $dialogs = "";
        foreach($this->actions as $action) {
            $dialogs .= $action->render();
        }
        return $dialogs;
    }

    private function writeTable(): string
    {
        $cols = "";
        foreach($this->meta['columns'] as $column) {
            $cols .= "<th>{$column['label']}</th>";
        }
        return "<table id=\"table-content{$this->id}\"><thead><tr>{$cols}<th class=\"cell-actions\"></th></tr></thead><tbody></tbody></table>";
    }

    private function writeMainMenu(): string
    {
        $rows = "";
        foreach($this->actions as $action) {
            $rows .= $action->inStandaloneToolbar();
        }
        return "<div class=\"standalone-actions\">{$rows}</div>";
    }

    private function buildContextualMenu(string $nodeName, string $item): string
    {
        $actions = "";
        foreach($this->actions as $action) {
            $actions .= $action->inDinamicContextMenu("root", $item);
        }
        return "{$nodeName}.innerHTML = '' +
            '<div class=\"nav-item dropdown\">'+
            '    <a class=\"nav-link hvr-glow d-flex align-items-center justify-content-center\" style=\"border-radius:50%; width:30px; height: 30px;\" data-bs-toggle=\"dropdown\" href=\"#\" role=\"button\" aria-haspopup=\"true\" aria-expanded=\"false\"><i class=\"bi bi-three-dots-vertical\"></i></a>' +
            '    <div class=\"dropdown-menu\"></div>' +
            '</div>';
            const root = {$nodeName}.querySelector(\".dropdown-menu\");{$actions}";
    }

    private function writeSearchBox(): string
    {
        return "<input id=\"global-search-{$this->id}\" type=\"search\" class=\"search text-search\" placeholder=\"Buscar...\">";
    }

    private function writeFilters(): string
    {
        $filters = "";
        foreach($this->meta['filters'] as $filter) {
            $field = $this->field($filter);
            $filters .= "<div class=\"filter-group\"><label for=\"{$field['name']}-filter\">{$field['label']}:</label>".$this->fieldFilter($field)."</div>";
        }
        return "<div class=\"filters\">{$filters}</div>";
    }

    private function title(): string
    {
        return "<h2>{$this->meta['title']}</h2><p>{$this->meta['description']}</p>";
    }

    private function field(array $from) {
        return $this->meta['fields'][$from['name']];
    }

    private function dateFormat($value) {
        if( $value instanceof \DateTimeInterface ) {
            return $value->format('Y-m-d');
        } else {
            return date('Y-m-d', (int)$value);
        }
    }
    private function actionButton(array $action): string
    {
        return "<button class=\"btn btn-" . $action['kind'] . " onclick=\"run" . $action['name'] . "()\">" . $action['label'] . "</button>";
    }


    private function fieldFilter(array $field): string {
        if( $field['enum'] ?? false ) {
            $options = "";
            foreach($field['enum'] as $k=>$v) {
                $options .= "<option value=\"{$k}\">{$v}</option>";
            }
            return "<select id=\"{$field['name']}-filter\" onchange=\"search()\">{$options}</select>";
        } else {
            return "<input id=\"{$field['name']}-filter\" type=\"search\" class=\"text-search\" />";
        }
    }
}