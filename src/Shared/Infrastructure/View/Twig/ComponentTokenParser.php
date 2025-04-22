<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View\Twig;

use Civi\Repomanager\Shared\Infrastructure\View\Twig\Bootstrap\CardNode;
use Civi\Repomanager\Shared\Infrastructure\View\Twig\Bootstrap\GridNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\Node\Node;

class ComponentTokenParser extends AbstractTokenParser
{
    private readonly string $kind;
    public function __construct(private readonly string $name) {
        $this->kind = 'Civi\\Repomanager\\Shared\\Infrastructure\\View\\Twig\\Bootstrap\\' . ucfirst($name) . 'Node';
    }

    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $attributes = [];
        while (!$stream->test(Token::BLOCK_END_TYPE)) {
            $name = $stream->expect(Token::NAME_TYPE)->getValue();
            $stream->expect(Token::OPERATOR_TYPE, '=');
            $value = $this->parser->getExpressionParser()->parseExpression();
            $attributes[$name] = $value;
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        $body = $this->parser->subparse(fn(Token $t) => $t->test("end{$this->name}"), true);

        $stream->expect(Token::BLOCK_END_TYPE); // for endcard

        return new $this->kind($body, $attributes, $lineno, $this->getTag());
    }

    public function getTag(): string
    {
        return $this->name;
    }
}