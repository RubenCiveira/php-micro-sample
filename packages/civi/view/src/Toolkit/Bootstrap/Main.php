<?php declare(strict_types=1);

namespace Civi\View\Toolkit\Bootstrap;

class Main
{
    public function __construct(private readonly string $body)
    {

    }
    public function render(): string
    {
        return "<main class=\"container\">{$this->body}</main>";
    }
}