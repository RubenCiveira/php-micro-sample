<?php declare(strict_types=1);

namespace Civi\View\Toolkit\Bootstrap;

class CardAction
{
    public function __construct(private readonly ?string $url, private readonly string $body)
    {

    }
    public function render(): string
    {
        return "<a class=\"card-link\" href=\"". ( $this->url??"#" )."\">{$this->body}</a>";
    }
}