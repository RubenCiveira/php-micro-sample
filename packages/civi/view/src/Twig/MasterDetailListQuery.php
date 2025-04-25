<?php

declare(strict_types=1);

namespace Civi\View\Twig;

class MasterDetailListQuery
{
    public function __construct(
        public readonly array|null $query = null,
        public readonly array|null $include = null,
        public readonly array|null $cursor = null,
    ) {}
}