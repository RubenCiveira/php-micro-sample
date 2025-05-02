<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Civi\Micro\Rate\Resolver\BaseBucketResolver;
use Psr\Http\Message\ServerRequestInterface;

class BaseBucketResolverUnitTest extends TestCase
{
    public function testResolveAlwaysReturnsBase(): void
    {
        $resolver = new BaseBucketResolver();
        $request = $this->createMock(ServerRequestInterface::class);

        $result = $resolver->resolve($request);

        $this->assertSame('base', $result);
    }
}
