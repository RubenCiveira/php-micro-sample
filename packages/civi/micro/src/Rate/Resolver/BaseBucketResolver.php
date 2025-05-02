<?php

declare(strict_types=1);

namespace Civi\Micro\Rate\Resolver;

use Civi\Micro\Rate\BucketResolverInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A basic bucket resolver that always returns the same fixed bucket identifier.
 *
 * This implementation is useful for applying global rate limits regardless of request content.
 * It can be used as a default resolver in configurations where request-based differentiation is not needed.
 */
class BaseBucketResolver implements BucketResolverInterface
{
    /**
     * Resolves the bucket identifier for the given HTTP request.
     *
     * @param ServerRequestInterface $request The incoming HTTP request.
     * @return string Always returns the string 'base', representing a static global bucket.
     */
    public function resolve(ServerRequestInterface $request): string
    {
        return 'base';
    }
}