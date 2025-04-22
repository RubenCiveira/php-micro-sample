<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View\Twig\Bootstrap;

use Twig\Compiler;
use Twig\Node\Node;

class CardNode extends Node
{
    public function __construct(Node $body, array $attributes, int $lineno, ?string $tag = null)
    {
        parent::__construct(['body' => $body] + $attributes, [], $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write("yield '<div class=\"mb-3';");
        if ($this->hasNode('width')) {
            $compiler->write("yield ' col-lg-' . ")->subcompile($this->getNode('width'))->write(";");
        }
        $compiler
            ->write("yield '\"><div class=\"card\"><div class=\"card-body\">';\n");

        if ($this->hasNode('title')) {
            $compiler
                ->write("yield '<h4 class=\"card-title\">' . ")
                ->subcompile($this->getNode('title'))
                ->write(" . '</h4>';\n");
        }

        if ($this->hasNode('title')) {
            $compiler
                ->write("yield '<p class=\"card-text\">' . ")
                ->subcompile($this->getNode('text'))
                ->write(". '</p>';");
        }
        $compiler->subcompile($this->getNode('body'));

        $compiler->write("yield '</div>';\n")
            ->write("yield '</div></div>';\n");
    }
}
