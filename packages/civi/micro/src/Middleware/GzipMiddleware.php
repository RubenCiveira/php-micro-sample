<?php declare(strict_types=1);

namespace Civi\Micro\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface;

class GzipMiddleware
{
    public function __invoke(Request $request, RequestHandlerInterface $handler): Response
    {
        $acceptEncoding = $request->getHeaderLine('Accept-Encoding');

        $response = $handler->handle($request);

        if (strpos($acceptEncoding, 'gzip') !== false) {
            $body = (string) $response->getBody();
            $gzipped = gzencode($body);
    
            $response = $response
                ->withHeader('Content-Encoding', 'gzip')
                ->withHeader('Vary', 'Accept-Encoding')
                ->withHeader('Content-Length', ''.strlen($gzipped));
    
            $response->getBody()->rewind();
            $response->getBody()->write($gzipped);
        }
    
        return $response;
    }
}