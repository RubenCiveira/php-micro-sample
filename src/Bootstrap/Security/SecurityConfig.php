<?php declare(strict_types=1);

namespace Civi\Repomanager\Bootstrap\Security;

class SecurityConfig
{
    public function __construct(
        public readonly string $googleClientId,
        public readonly string $googleClientSecret,
        public readonly string $googleRedirectUri,
        public readonly array $authorizedUsers,
        public readonly array $publicPaths
    ) {

    }
}