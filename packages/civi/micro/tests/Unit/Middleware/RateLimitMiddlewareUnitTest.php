<?php

declare(strict_types=1);

use Civi\Micro\Middleware\RateLimitMiddleware;
use Civi\Micro\Rate\RateConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\Policy\NoLimiter;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Slim\Psr7\Response;

class RateLimitMiddlewareUnitTest extends TestCase
{
    public function testRequestWithoutRateLimitReturnsHandlerResponse(): void
    {
        $config = new RateConfig('default', 'fixed_window', 10, '1 minute', 'bucket', 'conn');

        $container = $this->createMock(ContainerInterface::class);
        $storage = new InMemoryStorage();

        // Resolver no devuelve ningún RateLimiterFactory
        $bucketResolver = new class {
            public function resolve() { return null; } // fuerza que la factory no funcione
        };
        $connResolver = new class {
            public function resolve() { return 'conn-id'; }
        };

        $container->method('get')
            ->willReturnMap([
                ['bucket', $bucketResolver],
                ['conn', $connResolver],
            ]);

        $middleware = new RateLimitMiddleware($container, $config, $storage);

        $request = $this->createMock(ServerRequestInterface::class);

        $response = new Response();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($response);

        $result = $middleware($request, $handler);

        $this->assertSame($response, $result);
    }

    public function testRequestAcceptedReturnsResponseWithHeader(): void
    {
        $config = new RateConfig('test_id', 'fixed_window', 5, '1 minute', 'bucket', 'conn');
        $storage = new InMemoryStorage();

        $bucketResolver = new class {
            public function resolve() { return 'bucket-a'; }
        };

        $connResolver = new class {
            public function resolve() { return 'user-a'; }
        };

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                ['bucket', $bucketResolver],
                ['conn', $connResolver],
            ]);

        $middleware = new RateLimitMiddleware($container, $config, $storage);

        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = new Response();
        $handler->expects($this->once())->method('handle')->willReturn($response);

        $result = $middleware($request, $handler);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue($result->hasHeader('X-RateLimit-Remaining'));
    }

    public function testRequestRejectedReturns429(): void
    {
        $config = new RateConfig('strict', 'fixed_window', 1, '1 hour', 'bucket', 'conn');
        $storage = new InMemoryStorage();

        $bucketResolver = new class {
            public function resolve() { return 'b-user'; }
        };

        $connResolver = new class {
            public function resolve() { return 'c-user'; }
        };

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                ['bucket', $bucketResolver],
                ['conn', $connResolver],
            ]);

        $middleware = new RateLimitMiddleware($container, $config, $storage);

        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response());

        // Primer request lo consume
        $middleware($request, $handler);

        // Segundo debe rechazar
        $result = $middleware($request, $handler);

        $this->assertEquals(429, $result->getStatusCode());
        $this->assertTrue($result->hasHeader('Retry-After'));
        $this->assertStringContainsString('Rate limit exceeded', (string) $result->getBody());
    }
}
