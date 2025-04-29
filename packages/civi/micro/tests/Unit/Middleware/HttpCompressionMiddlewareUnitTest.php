<?php

declare(strict_types=1);

namespace Civi\Micro\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class HttpCompressionMiddlewareUnitTest extends TestCase
{
    public function testInvokeWithoutGzipAndWithoutIfNoneMatch()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);

        $bodyContent = 'Response content';
        $etag = '"' . sha1($bodyContent) . '"';

        $request->method('getHeaderLine')->willReturn(''); // No Accept-Encoding, no If-None-Match
        $response->method('getBody')->willReturn($body);
        $body->method('__toString')->willReturn($bodyContent);

        $response->method('withHeader')->willReturnSelf();

        $handler->method('handle')->willReturn($response);

        $middleware = new HttpCompressionMiddleware();
        $result = $middleware($request, $handler);

        $this->assertSame($response, $result);
    }

    public function testInvokeWithMatchingIfNoneMatch()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);

        $bodyContent = 'Response content';
        $etag = '"' . sha1($bodyContent) . '"';

        $request->expects($this->exactly(2))
            ->method('getHeaderLine')
            ->willReturnOnConsecutiveCalls('', $etag);

        $response->method('getBody')->willReturn($body);
        $body->method('__toString')->willReturn($bodyContent);

        $response->method('withHeader')->willReturnSelf();
        $response->method('withStatus')->willReturnSelf();

        $handler->method('handle')->willReturn($response);

        $middleware = new HttpCompressionMiddleware();
        $result = $middleware($request, $handler);

        $this->assertSame($response, $result);
    }

    public function testInvokeWithGzipCompression()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);

        $bodyContent = 'Compress me!';
        $etag = '"' . sha1($bodyContent) . '"';

        $request->expects($this->any())
            ->method('getHeaderLine')
            ->willReturnOnConsecutiveCalls('gzip', ''); // Accept-Encoding: gzip

        $response->method('getBody')->willReturn($body);
        $body->method('__toString')->willReturn($bodyContent);

        $response->method('withHeader')->willReturnSelf();

        $body->expects($this->once())->method('rewind');
        $body->expects($this->once())->method('write')->with($this->callback(function ($gzippedContent) use ($bodyContent) {
            return gzdecode($gzippedContent) === $bodyContent;
        }));

        $handler->method('handle')->willReturn($response);

        $middleware = new HttpCompressionMiddleware();
        $result = $middleware($request, $handler);

        $this->assertSame($response, $result);
    }
}
