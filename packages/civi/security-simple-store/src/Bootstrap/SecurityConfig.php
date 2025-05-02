<?php declare(strict_types=1);

namespace Civi\SecurityStore\Bootstrap;

class SecurityConfig
{
    public readonly string $loginUrl;

    public function __construct(
        public readonly string $root,
        public readonly string $oauthRedirectHost,
        public readonly string $oauthRedirectPath,
        public readonly string $oauthProvidersGoogleClientId,
        public readonly string $oauthProvidersGoogleClientSecret,
        public readonly array $routesWhiteList = []
    ) {
        $this->loginUrl = '/login';
    }
}