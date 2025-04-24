<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View\Toolkit\Bootstrap;

class Indicator
{
    public function __construct(private readonly string $kind, private readonly string $body)
    {

    }
    public function render(): string
    {
        return "<div class=\"alert alert-dismissible alert-{$this->kind} m-4 shadow-lg\"><button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>{$this->body}</div>";
    }
}