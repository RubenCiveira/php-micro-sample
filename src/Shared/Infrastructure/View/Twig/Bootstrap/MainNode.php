<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View\Twig\Bootstrap;

use Twig\Compiler;
use Twig\Node\Node;

class MainNode extends Node
{
    public function __construct(Node $body, array $attributes, int $lineno, ?string $tag = null)
    {
        parent::__construct(['body' => $body] + $attributes, [], $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write("yield '<main class=\"container\">';")
            ->subcompile($this->getNode('body'))
            ->write("yield '</main>';\n");
    }
}
