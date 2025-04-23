<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View\Toolkit\Bootstrap;

class DialogFooter
{

    public function __construct(private ?string $body)
    {
    }

    public function addButton(DialogButton $btn) 
    {
      $this->body .= $btn->render();
    }

    public function render(): string
    {
        return "<div class=\"modal-footer\">{$this->body}</div>";
    }
}