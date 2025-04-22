<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View\Twig\Bootstrap;

use Twig\Compiler;
use Twig\Node\Node;

class MasterDetailNode extends Node
{
    public function __construct(Node $body, array $attributes, int $lineno, ?string $tag = null)
    {
        parent::__construct(['body' => $body] + $attributes, [], $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write("\$_meta = ")
            ->subcompile($this->getNode('meta'))
            ->write(";\$_values = ")
            ->subcompile($this->getNode('value'))
            ->write(";yield '<div class=\"container\">';");
        $compiler->write("yield '<h2>' . \$_meta['title'] . '</h2><p>' . \$_meta['description'] . '</p><div class=\"container\">';");
        $compiler->write("yield '<div class=\"actions\">';\n");
        $this->writeMainMenu($compiler);
        $compiler->write("yield '<input id=\"globalSearch\" type=\"search\" class=\"search text-search\" placeholder=\"Buscar...\">
        </div>';");
        $compiler->write("if ( \$_meta['filters'] ) {\n".
            "yield '<div class=\"filters\">';\n" .
            "foreach(\$_meta['filters'] as \$_filter) {\n" .
            "yield '<div class=\"filter-group\">';\n" .
            "yield '<label for=\"' . \$_filter['name'] . 'Filter\">'. \$_meta['fields'][\$_filter['name']]['label'] . ':</label>';\n" .
                // {% if meta.fields[filter.name].type == 'options' %}
                // <select id="{{filter.name}}Filter" onchange="search()">
                //     <option value="">Todos</option>
                //     {% for k, v in meta.fields[filter.name].options %}
                //     <option value="{{k}}">{{v}}</option>
                //     {% endfor %}
                // </select>
                // {% else %}
                // <input id="{{filter.name}}Filter" type="search" class="text-search" />
                // {% endif %}
            "yield '</div>';\n" .
            "}\n".
            "yield '</div>';\n" .
            "}");
        $compiler
            ->write("yield '</div>';\n");
    }

    private function writeMainMenu(Compiler $compiler)
    {
        $compiler->write("yield '<div>';\n")
            ->write("foreach( \$_meta['actions'] as \$_action ) { if( !\$_action['contextual'] ) {\n")
            ->write("yield '<button class=\"btn btn-' . \$_action['kind'] . ' onclick=\"run(\\'' . \$_action['name'] . '\\')\">' . \$_action['label'] . '</button>';\n")
            ->write("} } yield '</div>';\n")
            ->write("yield '</div>';\n");
    }
}
