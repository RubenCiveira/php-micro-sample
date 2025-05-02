<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Civi\Micro\Rate\Resolver\IpGranularityResolver;
use Psr\Http\Message\ServerRequestInterface;

class IpGranularityResolverUnitTest extends TestCase
{
    public function testResolveReturnsIpAddress(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn([
            'REMOTE_ADDR' => '192.168.1.100'
        ]);

        $resolver = new IpGranularityResolver();
        $result = $resolver->resolve($request);

        $this->assertSame('192.168.1.100', $result);
    }

    public function testResolveReturnsWildcardWhenIpMissing(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn([]);

        $resolver = new IpGranularityResolver();
        $result = $resolver->resolve($request);

        $this->assertSame('*', $result);
    }
}
