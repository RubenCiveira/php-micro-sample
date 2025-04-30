<?php

declare(strict_types=1);

namespace Civi\Security;

class Authentication
{
    public const AUTH_SCOPE_NONE = 'none';
    public const AUTH_SCOPE_READ = 'read';
    public const AUTH_SCOPE_BOTH = 'read-write';

    public static function anonimous(): Authentication
    {
        return new Authentication(anonimous: true);
    }

    public function __construct(
        public readonly bool $anonimous,
        public readonly string $authScope = self::AUTH_SCOPE_NONE,
        public readonly ?string $name = null,
        public readonly ?string $token = null,
        public readonly ?string $issuer = null,
        public readonly ?string $tenant = null,
        public readonly ?array $roles = null,
        public readonly ?array $groups = null,
        public readonly ?array $claims = null,
        public readonly ?string $scope = null
    ) {
    }

    public function getClaim(string $claim): ?string
    {
        return $this->claims && isset($this->claims[$claim]) ? $this->claims[$claim] : null;
    }
    public function hasScope(string $scope): bool
    {
        // return $this->scope ? false !== array_search($scope, $this->scope) : false;
        return false;
    }
    public function hasAnyRole(string ...$roles): bool
    {
        if (!$this->roles) {
            return false;
        }
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        return false;
    }
    public function hasRole(string $role): bool
    {
        return $this->roles ? false !== array_search($role, $this->roles) : false;
    }
}
