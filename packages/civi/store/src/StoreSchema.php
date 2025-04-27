<?php

declare(strict_types=1);

namespace Civi\Store;

class StoreSchema
{
    public function __construct(
        public readonly string $idName,
        public readonly array $indexFields
    )
    {
        
    }
}