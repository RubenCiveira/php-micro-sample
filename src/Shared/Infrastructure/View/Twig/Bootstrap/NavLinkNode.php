<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View\Twig\Bootstrap;

use Twig\Compiler;
use Twig\Node\Node;

class NavLinkNode extends Node
{
    public function __construct(Node $body, array $attributes, int $lineno, ?string $tag = null)
    {
        parent::__construct(['body' => $body] + $attributes, [], $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        $id = 'main-navbar-' . time();
        $compiler
            ->addDebugInfo($this);
        if( $this->getNode('url')) {
            $compiler->write("\$__nav_href = ")
            ->subcompile($this->getNode('url'))
            ->raw(";\n")
            ->write("\$__current_path = parse_url(\$_SERVER['REQUEST_URI'], PHP_URL_PATH);\n")
            ->write("\$__is_active = rtrim(\$__current_path, '/') === rtrim(\$__nav_href, '/');\n");
        } else {
            $compiler->write("\$__is_active = false;");
        }
        $compiler->write("yield '<li class=\"nav-item\">
                <a class=\"nav-link' . (\$__is_active ? ' active' : '' ) . '\" href=\"'.");
                if ( $this->hasNode('url') ) {
                    $compiler->subcompile($this->getNode('url'));
                } else {
                    $compiler->write("'#'");
                }
        $compiler->write(".'\">';")
                ->subcompile($this->getNode('body'))
                ->write("yield \$__is_active ? '<span class=\"visually-hidden\">(current)</span>' : '';")
                ->write("yield '</a></li>';");
    }
}
