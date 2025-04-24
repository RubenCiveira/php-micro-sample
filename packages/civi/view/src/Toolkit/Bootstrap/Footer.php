<?php declare(strict_types=1);

namespace Civi\View\Toolkit\Bootstrap;

class Footer
{
    public function __construct(private readonly string $body)
    {

    }
    public function render(): string
    {
        return "<footer class=\"text-center py-3 fixed-bottom\">{$this->body}</footer>";
    }
}