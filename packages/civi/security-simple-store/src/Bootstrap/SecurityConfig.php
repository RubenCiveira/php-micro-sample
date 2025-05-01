<?php declare(strict_types=1);

namespace Civi\SecurityStore\Bootstrap;

class SecurityConfig
{
    public readonly string $loginUrl;

    public function __construct(
        public readonly string $oauthRedirectHost,
        public readonly string $oauthRedirectPath,
        public readonly string $oauthProvidersGoogleClientId,
        public readonly string $oauthProvidersGoogleClientSecret,
        // public readonly string $googleRedirectUri,
        public readonly array $authorizedUsers,
        //public readonly array $publicPaths
    ) {
        $this->loginUrl = '/login';
    }
}