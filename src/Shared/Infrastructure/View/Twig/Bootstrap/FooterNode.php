<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View\Twig\Bootstrap;

use Twig\Compiler;
use Twig\Node\Node;

class FooterNode extends Node
{
    public function __construct(Node $body, array $attributes, int $lineno, ?string $tag = null)
    {
        parent::__construct(['body' => $body] + $attributes, [], $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write("yield '<footer class=\"text-center py-3 fixed-bottom\">';")
            ->subcompile($this->getNode('body'))
            ->write("yield '</footer>';\n");
    }
}
