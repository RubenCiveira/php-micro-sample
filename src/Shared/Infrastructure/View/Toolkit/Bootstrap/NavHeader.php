<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View\Toolkit\Bootstrap;

class NavHeader
{
    private static $counter = 0;
    public readonly string $id;

    public function __construct(private readonly ?string $title, private readonly ?string $smallTitle, private readonly ?string $url, private readonly string $body)
    {
        $this->id = 'main-navbar-' . (++self::$counter);
    }
    public function render(): string
    {
        $content = "";
        if( $this->title ) {
            $content .= "<a class=\"navbar-brand\" href=\"" .($this->url??"#"). "\">";
            if( $this->smallTitle ) {
                $content .= "<span class=\"d-inline d-sm-none\">{$this->smallTitle}</span><span class=\"d-none d-sm-inline\">{$this->title}</span>";
            } else {
                $content .= $this->title;
            }
            $content .= "</a>";
        }
        return "<nav class=\"navbar navbar-expand-lg bg-primary\" data-bs-theme=\"dark\"><div class=\"container\">{$content}"
                    . "<button class=\"navbar-toggler collapsed\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#{$this->id}\" aria-controls=\"{$this->id}\" aria-expanded=\"false\" aria-label=\"Toggle navigation\"><span class=\"navbar-toggler-icon\"></span></button>"
                    . "<div class=\"navbar-collapse collapse\" id=\"{$this->id}\"><ul class=\"navbar-nav me-auto\">"
                    ."{$this->body}</ul></div></div></nav>";
    }
}