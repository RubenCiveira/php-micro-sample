<?php

declare(strict_types=1);

namespace Civi\Security;

class SecurityContext
{
    public function __construct(
        public readonly Authentication $authentication,
        public readonly Connection $connection
    ) {
    }

    public function getTenant(): string
    {
        return $this->authentication->tenant;
    }
    public function getClaim(string $claim): ?string
    {
        return $this->authentication->getClaim($claim);
    }
    public function hasScope(string $scope): bool
    {
        return $this->authentication->hasScope($scope);
    }
    public function hasAnyRole(string ...$roles): bool
    {
        return $this->authentication->hasAnyRole(...$roles);
    }
    public function hasRole(string $role): bool
    {
        return $this->authentication->hasRole($role);
    }
    public function isAnonimous(): bool
    {
        return $this->authentication->anonimous;
    }
}
