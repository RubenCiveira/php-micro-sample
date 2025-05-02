<?php

declare(strict_types=1);

namespace Civi\Micro\Rate;

use Psr\Http\Message\ServerRequestInterface;

interface ConnectionResolverInterface
{
    public function resolve(ServerRequestInterface $request): string;
}