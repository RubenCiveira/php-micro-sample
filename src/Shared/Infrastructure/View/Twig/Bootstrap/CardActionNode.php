<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View\Twig\Bootstrap;

use Twig\Compiler;
use Twig\Node\Node;

class CardActionNode extends Node
{
    public function __construct(Node $body, array $attributes, int $lineno, ?string $tag = null)
    {
        parent::__construct(['body' => $body] + $attributes, [], $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        // Lo compilamos como una función PHP que devuelve el HTML del botón
        $compiler
            ->addDebugInfo($this)
            ->write("yield '<a class=\"card-link\"");
        if( $this->hasNode('url')) {
            $compiler->write(" href=\"' . ")
                ->subcompile($this->getNode('url'))
                ->write(" . '\"'");
            }
        $compiler->write(" .'>';");
        $compiler->subcompile($this->getNode('body'))
            ->write("yield '</a>';\n");
    }
}
