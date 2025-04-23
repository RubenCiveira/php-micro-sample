<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View\Toolkit\Bootstrap;

class Nav
{
    public function __construct(private readonly ?string $title, private readonly ?string $smallTitle, private readonly ?string $url, private readonly string $body)
    {
    }
    public function render(): string
    {
        $id = 'main-navbar-' . time();

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
                    . "<button class=\"navbar-toggler collapsed\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#{$id}\" aria-controls=\"{$id}\" aria-expanded=\"false\" aria-label=\"Toggle navigation\"><span class=\"navbar-toggler-icon\"></span></button>"
                    . "<div class=\"navbar-collapse collapse\" id=\"{$id}\"><ul class=\"navbar-nav me-auto\">"
                    ."{$this->body}</ul></div></div></nav>";
    }
}