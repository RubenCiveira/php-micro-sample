<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View\Toolkit\Bootstrap;

class Grid
{
    public function __construct(private readonly string $body)
    {

    }
    public function render(): string
    {
        return "<div class=\"row\">{$this->body}</div>";
    }
}