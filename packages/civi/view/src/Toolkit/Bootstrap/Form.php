<?php declare(strict_types=1);

namespace Civi\View\Toolkit\Bootstrap;

use Civi\Micro\Schema\TypeSchema;

class Form
{
    private static $counter = 0;
    private readonly string $id;
    public function __construct(private readonly string $name, private readonly string $target, private readonly TypeSchema $meta, private readonly array $form)
    {
        $this->id = 'fm-' . $name . (++self::$counter);
    }

    public function render(): string
    {
        $inputs = "<input type=\"hidden\" id=\"{$this->id}-{$this->meta->id}\" name=\"{$this->name}\" />"
            // . "<input type=\"hidden\" id=\"{$this->id}-{$this->meta->id}\" name=\"{$this->meta->id}\" />"
            . "";
        $assignsWithValue = "document.getElementById('{$this->id}-{$this->meta->id}').value = value.{$this->meta->id};";
        $assignsWithoutValue = "document.getElementById('{$this->id}-{$this->meta->id}').value = '';";

        foreach ($this->form as $field) {
            $assignWithValue = "document.getElementById('{$this->id}-{$field->name}').value = value.{$field->name};";
            $assignWithoutValue = "document.getElementById('{$this->id}-{$field->name}').value = '';";
            if ($field->calculated ?? false) {
                $inputs .= "<input type=\"hidden\" id=\"{$this->id}-{$field->name}\" name=\"{$field->name}\" />";
            } else {
                $input = "";
                $type = $field->type ?? '';
                $att = "id=\"{$this->id}-{$field->name}\" name=\"{$field->name}\" " . ($field->required ? " required" : "");
                if ($field->readonly ?? false) {
                    $input = "<div id=\"{$this->id}-{$field->name}-label\"></div>";
                    $assignWithValue .= "
                        document.getElementById('{$this->id}-{$field->name}').style.display = 'none';
                        document.getElementById('{$this->id}-{$field->name}-label').style.display = '';
                        document.getElementById('{$this->id}-{$field->name}-label').innerHTML = value.{{ field.name }};
                    ";
                    $assignWithoutValue .= "
                        document.getElementById('{$this->id}-{$field->name}').style.display = '';
                        document.getElementById('{$this->id}-{$field->name}-label').style.display = 'none';
                        document.getElementById('{$this->id}-{$field->name}-label').innerHTML = '';
                    ";
                }
                if( $field->reference ?? false) {
                    $id = $field->reference->id;
                    $name = $field->reference->label;
                    $tempId = md5("{$this->id}{$field->name}");
                    $assignWithoutValue = "document.select{$tempId}.clear();";
                    $assignWithValue = "if(value.{$field->name} && value.{$field->name}.{$id}) { document.select{$tempId}.setValue( value.{$field->name}.{$id} ); } else { document.select{$tempId}.clear(); }";
                    $input = "<link href=\"https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.bootstrap5.css\" rel=\"stylesheet\"><script src=\"https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js\"></script>"
                            . "<select {$att}></select>"
                            . "<script>"
                            . "function loadFor{$tempId}(query, callback){"
                            . "fetch('?fetch=true&field=rol&q=' + encodeURIComponent(query)).then(response => response.json()).then(json => { callback(json); }).catch(() => { callback(); });"
                            . "};"
                            . "loadFor{$tempId}(\"\", (data) => { "
                            . "document.select{$tempId} = new TomSelect('#{$this->id}-{$field->name}', { allowEmptyOption: true, preload: false, valueField: '{$id}', labelField: '{$name}', searchField: '{$name}', options: data, load: loadFor{$tempId} });"
                            . "});"
                            . "</script>";
                } else if ($type == 'textarea') {
                    $input = "<textarea class=\"form-control\" {$att} rows=\"3\"></textarea>";
                } else if ($field->enum ?? false) {
                    $options = "";
                    foreach ($field->enum as $v) {
                        $options .= "<option value=\"{$v}\">{$v}</option>";
                    }
                    $input = "<select class=\"form-select\" {$att}>{$options}</select>";
                } else {
                    $input = "<input class=\"form-control\" {$att} type=\"{$type}\" />";
                }
                $inputs .= "<div class=\"form-group\"><label class=\"col-form-label mt-4\" for=\"{$field->name}\">{$field->label}</label>{$input}</div>\n";
            }
            $assignsWithValue .= $assignWithValue . "\n";
            $assignsWithoutValue .= $assignWithoutValue . "\n";
        }
        return "<div><form id=\"{$this->id}\" method=\"POST\" action=\"{$this->target}\">{$inputs}</form></div><script>"
            . "function assign{$this->name}(value) { if( value ) { {$assignsWithValue}  } else { {$assignsWithoutValue} } }"
            . "</script>";
    }

    public function submit(): string
    {
        return "document.getElementById('{$this->id}').submit();";
    }

    public function assign(string $value): string
    {
        return "assign{$this->name}({$value});";
    }
}