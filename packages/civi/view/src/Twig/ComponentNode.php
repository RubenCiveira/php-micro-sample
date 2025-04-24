<?php declare(strict_types=1);

namespace Civi\View\Twig;

use Twig\Compiler;
use Twig\Node\Node;

class ComponentNode extends Node
{
    public function __construct(
        Node $child,
        array $attrs,
        int $lineno,
        ?string $tag,
        private readonly string $kind,
        private readonly array $dumpAttributes,
        private bool $withBody
    ) {
        parent::__construct(['body' => $child] + $attrs, [], $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this);
        if ($this->withBody) {
            $compiler
                // Iniciar buffer de salida
                ->write("ob_start();\n")
                // Compilar el cuerpo (esto va a hacer `echo ...`)
                ->subcompile($this->getNode('body'))
                // Capturar el contenido como variable
                ->write("\$__component_body = ob_get_clean();\n");
        }
        $compiler
            ->write("echo (new \\Civi\\View\\Toolkit\\Bootstrap\\" . $this->kind . "(");
        $first = true;
        foreach($this->dumpAttributes as $att) {
            $compiler->write(($first?"":", ") . $att.": ");
            if( $this->hasNode($att) ) {
                $compiler->subcompile( $this->getNode($att) );
            } else {
                $compiler->raw('null');
            }
            $first = false;
        }
        if ($this->withBody) {
            $compiler
                ->write( ($this->dumpAttributes ? ", ": "") . "body: \$__component_body");
        }
        $compiler
            ->write("))->render();\n");
    }
}
