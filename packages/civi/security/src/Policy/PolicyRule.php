<?php

declare(strict_types=1);

namespace Civi\Security\Policy;

use Civi\Security\Authentication;
use Civi\Security\Connection;

final class PolicyRule
{
    public function __construct(
        public readonly bool $allow = true,
        public readonly bool $override = false,
        public readonly ?array $ifRole = null,
        public readonly ?string $ifIpRange = null,
        public readonly ?array $ifScope = null,
        public readonly ?array $ifClaim = null,
        public readonly ?string $ifIssuer = null,
        public readonly ?string $ifTenant = null,
        public readonly ?bool $ifAnonymous = null,
    ) {}

    public function matches(Authentication $auth, Connection $conn): bool
    {
        $matches = true;
        if ($this->ifAnonymous !== null) {
            $matches &= $auth->anonimous === $this->ifAnonymous;
        }
        if ($this->ifRole !== null) {
            $matches &= $auth->hasAnyRole(...$this->ifRole);
        }
        if ($this->ifIpRange !== null) {
            $matches &= $conn->inRange($this->ifIpRange);
        }
        if ($this->ifScope !== null) {
            $matches &= $auth->scope && in_array($auth->scope, $this->ifScope, true);
        }
        if ($this->ifClaim !== null) {
            foreach ($this->ifClaim as $key => $val) {
                $matches &= $auth->getClaim($key) !== $val;
            }
        }
        if ($this->ifIssuer !== null ) {
            $matches &= $auth->issuer === $this->ifIssuer;
        }
        if ($this->ifTenant !== null) {
            $matches &= $auth->tenant === $this->ifTenant;
        }
        return (bool)$matches;
    }

    public function mergeWith(PolicyRule $other): PolicyRule
    {
        // En merge, la regla es más restrictiva si alguna no permite.
        return new self(
            allow: $this->allow && $other->allow,
            override: false,
            ifRole: array_merge($this->ifRole ?? [], $other->ifRole ?? []),
            ifIpRange: $this->ifIpRange ?? $other->ifIpRange,
            ifScope: array_merge($this->ifScope ?? [], $other->ifScope ?? []),
            ifClaim: array_merge($this->ifClaim ?? [], $other->ifClaim ?? []),
            ifIssuer: $this->ifIssuer ?? $other->ifIssuer,
            ifTenant: $this->ifTenant ?? $other->ifTenant,
            ifAnonymous: $this->ifAnonymous ?? $other->ifAnonymous
        );
    }

    public function toArray(): array
    {
        return [
            'allow' => $this->allow,
            'override' => $this->override,
            'ifRole' => $this->ifRole,
            'ifIpRange' => $this->ifIpRange,
            'ifScope' => $this->ifScope,
            'ifClaim' => $this->ifClaim,
            'ifIssuer' => $this->ifIssuer,
            'ifTenant' => $this->ifTenant,
            'ifAnonymous' => $this->ifAnonymous,
        ];
    }
    public static function __set_state(array $data): self
    {
        return self::fromArray($data);
    }
    public static function fromArray(array $data): self
    {
        return new self(
            allow: $data['allow'] ?? true,
            override: $data['override'] ?? false,
            ifRole: $data['ifRole'] ?? null,
            ifIpRange: $data['ifIpRange'] ?? null,
            ifScope: $data['ifScope'] ?? null,
            ifClaim: $data['ifClaim'] ?? null,
            ifIssuer: $data['ifIssuer'] ?? null,
            ifTenant: $data['ifTenant'] ?? null,
            ifAnonymous: $data['ifAnonymous'] ?? null,
        );
    }
}
