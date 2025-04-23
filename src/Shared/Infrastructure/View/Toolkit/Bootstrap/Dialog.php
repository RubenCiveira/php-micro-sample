<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View\Toolkit\Bootstrap;

class Dialog
{

    public function __construct(private readonly ?string $id, private readonly ?string $title, private readonly ?string $size, private readonly ?string $subtitle, private ?string $body)
    {
    }

    public function appendBody(DialogBody $body): static
    {
        $this->body .= $body->render();
        return $this;
    }

    public function appendFooter(DialogFooter $footer): static
    {
        $this->body .= $footer->render();
        return $this;
    }

    public function render(): string
    {
        return "<div id=\"{$this->id}\" class=\"modal\">
  <div class=\"modal-dialog\" role=\"document\">
    <div class=\"modal-content\">
      <div class=\"modal-header\">
        <h5 class=\"modal-title\">{$this->title}</h5>
        <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"modal\" aria-label=\"Close\">
          <span aria-hidden=\"true\"></span>
        </button>
      </div>
      {$this->body}
    </div>
  </div>
</div>";
    }
}