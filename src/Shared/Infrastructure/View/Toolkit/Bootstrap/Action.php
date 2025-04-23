<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View\Toolkit\Bootstrap;

class Action
{
    public readonly string $id;
    public function __construct(private readonly array $meta, private readonly array $action) 
    {
        $this->id = $action['name'] . time();
    }

    public function callback(string $value): string
    {
        return "run{$this->id}({$value});";
    }

    public function inStandaloneToolbar(): string
    {
        return $this->action['contextual'] ? "" : "<button class=\"btn btn-{$this->action['kind']}\" onclick=\"run{$this->id}()\">{$this->action['label']}</button>";;
    }

    public function inDinamicContextMenu(string $node, string $value): string
    {
        return $this->action['contextual'] ? "const btn{$this->action['name']} = document.createElement(\"a\");btn{$this->action['name']}.className = \"dropdown-item\"; btn{$this->action['name']}.href=\"#\"; btn{$this->action['name']}.textContent = \"{$this->action['label']}\"; btn{$this->action['name']}.onclick = () => { run{$this->id}({$value}); return false; };{$node}.append(btn{$this->action['name']});" : "";
    }
    public function render(): string
    {
        $body = "Accion {$this->action['label']}"
            . "<input type=\"hidden\" name=\"{$this->action['name']}\" id=\"sel-{$this->id}\" />";
        if( $this->action['template'] ?? false) {
            $body .= $this->action['template'];
        }
        $dialog = new Dialog(id: "{$this->action['name']}-{$this->id}", title: $this->action['label'], size: null, subtitle: null, body: $body);
        if( $this->action['buttons'] ?? false ) {
            $footer = new DialogFooter(null);
            foreach($this->action['buttons'] as $btCall => $btLabel) {
                $footer->addButton( new DialogButton($btCall, $btLabel) );
            }
            $dialog->appendFooter( $footer );
        }
        return $dialog->render() . "<script>
            function run{$this->id}(value) {
                const myModal = new bootstrap.Modal(document.getElementById('{$this->action['name']}-{$this->id}'));
                document.getElementById('sel-{$this->id}').value = value.{$this->meta['id']};
                ".($this->action['code']??'')."
                myModal.show();
            }
            ".($this->action['functions']??'')."
        </script>";
    }
}