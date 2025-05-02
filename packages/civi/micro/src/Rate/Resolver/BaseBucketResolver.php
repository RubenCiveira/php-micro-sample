<?php

namespace Civi\Micro\Rate\Resolver;

use Civi\Micro\Rate\BucketResolverInterface;
use Psr\Http\Message\ServerRequestInterface;

class BaseBucketResolver implements BucketResolverInterface
{
    public function resolve(ServerRequestInterface $request): string
    {
        return 'base';
    }
}