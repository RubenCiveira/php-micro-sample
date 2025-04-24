<?php declare(strict_types=1);

namespace Civi\View\Toolkit\Bootstrap;

class Card
{
    public function __construct(private readonly ?string $width, private readonly ?string $title, private readonly ?string $text, private readonly string $body)
    {

    }
    public function render(): string
    {
        return "<div class=\"mb-3".($this->width?" col-lg-{$this->width}":"")."\"><div class=\"card\"><div class=\"card-body\">"
                . ($this->title?"<h4 class=\"card-title\">{$this->title}</h4>":"")
                . ($this->text? "<p class=\"card-text\">{$this->text}</p>": "")
                . $this->body
                ."</div></div></div>";
    }
}