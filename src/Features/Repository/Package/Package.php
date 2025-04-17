<?php

namespace Civi\Repomanager\Features\Repository\Package;

class Package
{
       public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $url,
        public readonly string $type,
        public readonly string $status,
        public readonly string $description
    ) {
    }
    public static function from($data): Package
    {
        return new Package($data['id'], $data['name'], $data['url'], $data['type'], $data['status'], $data['description']);
    }
}