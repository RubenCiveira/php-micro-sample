<?php

declare(strict_types=1);

namespace Civi\Security;

class SecurityContextHolder
{
    private static ?SecurityContext $context = null;

    public static function set(SecurityContext $context): void
    {
        self::$context = $context;
    }

    public function get(): SecurityContext
    {
        return self::$context ?? $this->default();
    }

    private function default(): SecurityContext
    {
        return new SecurityContext(
            authentication: Authentication::anonimous(),
            connection: Connection::remoteHttp()
        );
    }
}