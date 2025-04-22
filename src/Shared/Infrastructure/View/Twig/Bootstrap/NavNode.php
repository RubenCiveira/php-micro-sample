<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View\Twig\Bootstrap;

use Twig\Compiler;
use Twig\Node\Node;

class NavNode extends Node
{
    public function __construct(Node $body, array $attributes, int $lineno, ?string $tag = null)
    {
        parent::__construct(['body' => $body] + $attributes, [], $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        $id = 'main-navbar-' . time();
        $compiler
            ->addDebugInfo($this)
            ->write("yield '<nav class=\"navbar navbar-expand-lg bg-primary\" data-bs-theme=\"dark\"><div class=\"container\">';");

        if ($this->hasNode('title') || $this->hasNode('smallTitle') || $this->hasNode('longTitle')) {
            $compiler
                ->write("yield '<a class=\"navbar-brand\" href=\"'.");
            if ( $this->hasNode('url') ) {
                $compiler->subcompile($this->getNode('url'));
            } else {
                $compiler->write("'#'");
            }
            $compiler->write(".'\">' . ");
            if ($this->hasNode('title')) {
                $compiler->subcompile($this->getNode('title'))
                    ->write('.');
            } else  {
                if ($this->hasNode('smallTitle')) {
                    $compiler
                        ->write("'<span class=\"d-inline d-sm-none\">' . ")
                        ->subcompile($this->getNode('smallTitle'))
                        ->write(". '</span>' . ");
                }
                if ($this->hasNode('longTitle')) {
                    $compiler
                        ->write("'<span class=\"d-none d-sm-inline\">' . ")
                        ->subcompile($this->getNode('longTitle'))
                        ->write(". '</span>'. ");
                }
            }
            $compiler
                ->write("'</a>';");
        }
        $compiler
            ->write("yield '<button class=\"navbar-toggler collapsed\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#{$id}\" aria-controls=\"{$id}\" aria-expanded=\"false\" aria-label=\"Toggle navigation\"><span class=\"navbar-toggler-icon\"></span></button><div class=\"navbar-collapse collapse\" id=\"{$id}\"><ul class=\"navbar-nav me-auto\">';")
            ->subcompile($this->getNode('body'))
            ->write("yield '</ul></div></div></nav>';\n");
    }
}
