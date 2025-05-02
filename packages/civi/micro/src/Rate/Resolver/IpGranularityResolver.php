<?php

declare(strict_types=1);

namespace Civi\Micro\Rate\Resolver;

use Civi\Micro\Rate\ConnectionResolverInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A connection resolver that identifies clients by their IP address.
 *
 * This resolver extracts the `REMOTE_ADDR` server parameter from the request
 * to determine the source of the connection. It is commonly used to apply
 * rate limiting per IP.
 */
class IpGranularityResolver implements ConnectionResolverInterface
{
    /**
     * Resolves the connection identity for the given HTTP request.
     *
     * @param ServerRequestInterface $request The incoming HTTP request.
     * @return string The client's IP address if available, or '*' as a fallback when `REMOTE_ADDR` is not set.
     */
    public function resolve(ServerRequestInterface $request): string
    {
        return $request->getServerParams()['REMOTE_ADDR'] ?? "*";
    }
}
