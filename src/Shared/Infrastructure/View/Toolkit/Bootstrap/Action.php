<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View\Toolkit\Bootstrap;

class Action
{
    public readonly string $id;
    public function __construct(private readonly string $target, private readonly array $meta, private readonly array $action) 
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
        $body =  "";// "Accion {$this->action['label']}";
        $form = null;
        $code = $this->action['code']??'';
        if( $this->action['template'] ?? false) {
            $body .= $this->action['template'];
        } else if( $this->action['form']  ?? false ) {
            $form = new Form($this->action['name'], $this->target, $this->meta, $this->action['form']);
            $body .= $form->render();
            $code .= $form->assign("value");
        } else {
            $code = "document.getElementById('sel-{$this->id}').value = value ? value.{$this->meta['id']} : '';{$code}";
            $body = "<form method=\"POST\" target=\"{$this->target}\" id=\"frm-{$this->id}\">"
                . "<input type=\"hidden\" name=\"{$this->action['name']}\" id=\"sel-{$this->id}\" /></form>"
                . "<p>¿Estás seguro de que deseas {$this->action['label']}?</p>";
        }
        $dialog = new Dialog(id: "{$this->action['name']}-{$this->id}", title: $this->action['label'], size: null, subtitle: null, body: '');
        $dialog->appendBody( new DialogBody($body));
        $footer = new DialogFooter(null);
        if( $this->action['buttons'] ?? false ) {
            foreach($this->action['buttons'] as $btCall => $btLabel) {
                $footer->addButton( new DialogButton($btCall, $btLabel) );
            }
        } else  {
            $footer->addButton(new DialogButton(null, 'Cancel'));
            $footer->addButton(new DialogButton("submit{$this->id}()", $this->action['name']));
        }
        $dialog->appendFooter( $footer );
        return $dialog->render() . "<script>
            function run{$this->id}(value) {
                const myModal = new bootstrap.Modal(document.getElementById('{$this->action['name']}-{$this->id}'));
                {$code}
                myModal.show();
            }
            ".($this->action['functions']??"function submit{$this->id}() {".($form? $form->submit() : "document.getElementById('frm-{$this->id}').submit()")."};")."
        </script>";
    }
}