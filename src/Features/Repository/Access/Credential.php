<?php

namespace Civi\Repomanager\Features\Repository\Access;

class Credential
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $user,
        public readonly string $pass,
        public readonly \DateTimeImmutable $until
    ) {
    }

    public static function from($data): Credential
    {
        return new Credential($data['id'], $data['name'], $data['user'], $data['pass'], new \DateTimeImmutable($data['until']['date']));
    }
}