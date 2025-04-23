<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View\Toolkit\Bootstrap;

class DialogButton
{

    public function __construct(private readonly ?string $action, private readonly ?string $body)
    {
    }

    public function render(): string
    {
        return "<button type=\"button\" ".($this->action?" onclick=\"{$this->action}\"":" data-bs-dismiss=\"modal\"")." class=\"btn ".($this->action?"btn-primary":"btn-secondary")."\">{$this->body}</button>";
    }
}