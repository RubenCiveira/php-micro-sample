<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View\Toolkit\Bootstrap;

class Form
{
    private readonly string $id;
    public function __construct(private readonly string $name, private readonly string $target, private readonly array $meta, private readonly array $form)
    {
        $this->id = 'fm-' . $name . time();
    }

    public function render(): string
    {
        $inputs = "<input type=\"hidden\" id=\"{$this->id}-{$this->meta['id']}\" name=\"{$this->name}\" />"
                // . "<input type=\"hidden\" id=\"{$this->id}-{$this->meta['id']}\" name=\"{$this->meta['id']}\" />"
                . "";
        $assignsWithValue = "document.getElementById('{$this->id}-{$this->meta['id']}').value = value.{$this->meta['id']};";
        $assignsWithoutValue = "document.getElementById('{$this->id}-{$this->meta['id']}').value = '';";
        
        foreach($this->form as $field) {
            $assignWithValue = "document.getElementById('{$this->id}-{$field['name']}').value = value.{$field['name']};";
            $assignWithoutValue = "document.getElementById('{$this->id}-{$field['name']}').value = '';";
            if( $field['calculated'] ?? false ) {
                $inputs .= "<input type=\"hidden\" id=\"{$this->id}-{$field['name']}\" name=\"{$field['name']}\" />";
            } else {
                $input = "";
                $type = $field['type'] ?? '';
                $att = "id=\"{$this->id}-{$field['name']}\" name=\"{$field['name']}\" " . ($field['required'] ? " required": "");
                if ($field['readonly'] ?? false ) {
                    $input = "<div id=\"{$this->id}-{$field['name']}-label\"></div>";
                    $assignWithValue .= "
                        document.getElementById('{$this->id}-{$field['name']}').style.display = 'none';
                        document.getElementById('{$this->id}-{$field['name']}-label').style.display = '';
                        document.getElementById('{$this->id}-{$field['name']}-label').innerHTML = value.{{ field.name }};
                    ";
                    $assignWithoutValue .= "
                        document.getElementById('{$this->id}-{$field['name']}').style.display = '';
                        document.getElementById('{$this->id}-{$field['name']}-label').style.display = 'none';
                        document.getElementById('{$this->id}-{$field['name']}-label').innerHTML = '';
                    ";
                }
                if ( $type == 'textarea' ) {
                    $input = "<textarea {$att} rows=\"3\"></textarea>";
                } else if( $field['enum'] ?? false ) {
                    $options = "";
                    foreach($field['enum'] as $v) {
                        $options .= "<option value=\"{$v}\">{$v}</option>";
                    }
                    $input = "<select {$att}>{$options}</option>";
                } else {
                    $input = "<input {$att} type=\"{$type}\" />";
                }
                $inputs .= "<div class=\"form-group\"><label for=\"{$field['name']}\">{$field['label']}</label>{$input}</div>\n";
            }
            $assignsWithValue .= $assignWithValue . "\n";
            $assignsWithoutValue .= $assignWithoutValue . "\n";
        }
        return "<div><form id=\"{$this->id}\" method=\"POST\" action=\"{$this->target}\">{$inputs}</form></div><script>"
                . "function assign{$this->name}(value) { if( value ) { {$assignsWithValue}  } else { {$assignsWithoutValue} } }"
                ."</script>";
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