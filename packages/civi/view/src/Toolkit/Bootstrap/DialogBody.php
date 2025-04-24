<?php declare(strict_types=1);

namespace Civi\View\Toolkit\Bootstrap;

class DialogBody
{

    public function __construct(private readonly ?string $body)
    {

    }

    public function render(): string
    {
        return "<div class=\"modal-body\">{$this->body}</div>";
    }
}