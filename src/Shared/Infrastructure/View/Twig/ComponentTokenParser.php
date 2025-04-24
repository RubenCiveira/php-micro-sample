<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View\Twig;

use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\Node\Node;

class ComponentTokenParser extends AbstractTokenParser
{
    public function __construct(private readonly ?string $as = null, private readonly array $attributes = [], private readonly bool $withBody = false) {
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

        $tag = lcfirst($this->as);

        $body = $this->parser->subparse(fn(Token $t) => $t->test("end{$tag}"), true);

        $stream->expect(Token::BLOCK_END_TYPE); // for endcard
        return new ComponentNode($body, $attributes, $lineno, $this->getTag(), $this->as, $this->attributes, $this->withBody);
    }

    public function getTag(): string
    {
        return lcfirst($this->as);
    }
}