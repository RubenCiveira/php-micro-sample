<?php declare(strict_types=1);

namespace Civi\View;

class ViewBuilder
{
    private static array $views = [];

    public static function registerView(ViewSection $section): bool
    {
        self::$views[$section->application][$section->label] = $section->path;
        return true;
    }

    public static function getViews(string $app) {
        return array_reverse( self::$views[$app], true);
    }
}