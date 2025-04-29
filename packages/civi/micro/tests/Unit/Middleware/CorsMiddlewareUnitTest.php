<?php

declare(strict_types=1);

namespace Civi\Micro\Middleware\Test;

use Civi\Micro\Middleware\CorsMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @covers \Civi\Micro\Middleware\CorsMiddleware
 */
class CorsMiddlewareUnitTest extends TestCase
{
    public function testInvokeWithOriginHeader(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        // Simulate that the request has an Origin header
        $request->method('hasHeader')
            ->with('Origin')
            ->willReturn(true);

        $request->method('getHeader')
            ->with('Origin')
            ->willReturn(['https://example.com']);

        $handler->method('handle')
            ->with($request)
            ->willReturn($response);

        $responseWithHeaders = $this->createMock(ResponseInterface::class);
        $responseWithHeaders2 = $this->createMock(ResponseInterface::class);
        $responseWithHeaders3 = $this->createMock(ResponseInterface::class);
        $responseWithHeaders4 = $this->createMock(ResponseInterface::class);
        $responseWithHeaders5 = $this->createMock(ResponseInterface::class);
        $responseWithHeaders6 = $this->createMock(ResponseInterface::class);

        $response->method('withHeader')->willReturnMap([
            ['Access-Control-Allow-Origin', ['https://example.com'], $responseWithHeaders],
            ['Access-Control-Allow-Credentials', 'true', $responseWithHeaders2],
            ['Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS', $responseWithHeaders3],
            ['Access-Control-Allow-Headers', '*', $responseWithHeaders4],
            ['Access-Control-Max-Age', '86400', $responseWithHeaders5],
        ]);

        $responseWithHeaders->method('withHeader')->willReturn($responseWithHeaders2);
        $responseWithHeaders2->method('withHeader')->willReturn($responseWithHeaders3);
        $responseWithHeaders3->method('withHeader')->willReturn($responseWithHeaders4);
        $responseWithHeaders4->method('withHeader')->willReturn($responseWithHeaders5);
        $responseWithHeaders5->method('withHeader')->willReturn($responseWithHeaders6);

        $middleware = new CorsMiddleware();
        $result = $middleware($request, $handler);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testInvokeWithoutOriginHeader(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('withHeader')->willReturnSelf();

        // Simulate that the request does not have an Origin header
        $request->method('hasHeader')
            ->with('Origin')
            ->willReturn(false);

        $request->expects($this->never())
            ->method('getHeader');

        $handler->method('handle')
            ->with($request)
            ->willReturn($response);

        $responseWithHeaders = $this->createMock(ResponseInterface::class);
        $responseWithHeaders2 = $this->createMock(ResponseInterface::class);
        $responseWithHeaders3 = $this->createMock(ResponseInterface::class);
        $responseWithHeaders4 = $this->createMock(ResponseInterface::class);
        $responseWithHeaders5 = $this->createMock(ResponseInterface::class);
        $responseWithHeaders6 = $this->createMock(ResponseInterface::class);

        $response->method('withHeader')->willReturnMap([
            ['Access-Control-Allow-Origin', ['localhost'], $responseWithHeaders],
            ['Access-Control-Allow-Credentials', 'true', $responseWithHeaders2],
            ['Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS', $responseWithHeaders3],
            ['Access-Control-Allow-Headers', '*', $responseWithHeaders4],
            ['Access-Control-Max-Age', '86400', $responseWithHeaders5],
        ]);

        $responseWithHeaders->method('withHeader')->willReturn($responseWithHeaders2);
        $responseWithHeaders2->method('withHeader')->willReturn($responseWithHeaders3);
        $responseWithHeaders3->method('withHeader')->willReturn($responseWithHeaders4);
        $responseWithHeaders4->method('withHeader')->willReturn($responseWithHeaders5);
        $responseWithHeaders5->method('withHeader')->willReturn($responseWithHeaders6);

        $middleware = new CorsMiddleware();
        $result = $middleware($request, $handler);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
