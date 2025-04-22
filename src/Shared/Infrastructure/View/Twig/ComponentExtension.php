<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View\Twig;

use Civi\Repomanager\Shared\Infrastructure\View\Twig\Bootstrap\CardNode;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class ComponentExtension extends AbstractExtension implements GlobalsInterface
{
    public function getGlobals(): array
    {
        return [];
    }
    public function getTokenParsers(): array
    {
        return [new ComponentTokenParser('card'), 
            new ComponentTokenParser('cardAction'), 
            new ComponentTokenParser('grid'),
            new ComponentTokenParser('footer'),
            new ComponentTokenParser('main'),
            new ComponentTokenParser('nav'),
            new ComponentTokenParser('masterDetail'),
            new ComponentTokenParser('navLink')];
    }
}