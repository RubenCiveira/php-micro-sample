<?php declare(strict_types=1);

namespace Civi\View\Twig;

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
        return [new ComponentTokenParser( 'Card', ['width', 'title', 'text'], true), 
            new ComponentTokenParser( 'CardAction', ['url'], true), 
            new ComponentTokenParser( 'Grid', [], true),
            new ComponentTokenParser( 'Footer', [], true),
            new ComponentTokenParser( 'Main', [], true),
            new ComponentTokenParser( 'Indicator', ['kind'], true),
            new ComponentTokenParser( 'NavHeader', ['url', 'title', 'smallTitle'], true),
            new ComponentTokenParser( 'NavLink', ['url'], true),
            new ComponentTokenParser( 'MasterDetail', ['meta', 'values', 'target'], true),
        ];
    }
}