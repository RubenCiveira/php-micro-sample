<?php

namespace Civi\Micro\Rate\Resolver;

use Civi\Micro\Rate\ConnectionResolverInterface;
use Psr\Http\Message\ServerRequestInterface;

class IpGranularityResolver implements ConnectionResolverInterface
{
    public function resolve(ServerRequestInterface $request): string
    {
        return $request->getServerParams()['REMOTE_ADDR'] ?? "*";
    }
}