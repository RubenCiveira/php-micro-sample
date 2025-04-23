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
        return [new ComponentTokenParser(null, 'Card', ['width', 'title', 'text'], true), 
            new ComponentTokenParser(null, 'CardAction', ['url'], true), 
            new ComponentTokenParser(null, 'Grid', [], true),
            new ComponentTokenParser(null, 'Footer', [], true),
            new ComponentTokenParser(null, 'Main', [], true),
            new ComponentTokenParser(null, 'Nav', ['url', 'title', 'smallTitle'], true),
            new ComponentTokenParser(null, 'NavLink', ['url'], true),
            new ComponentTokenParser(null, 'MasterDetail', ['meta', 'values', 'url'], true),
        ];
    }
}