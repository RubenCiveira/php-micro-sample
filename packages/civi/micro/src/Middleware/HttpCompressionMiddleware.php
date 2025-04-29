<?php

declare(strict_types=1);

namespace Civi\Micro\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware that applies gzip compression if the client supports it,
 * and generates an ETag header based on the content for cache validation.
 * 
 * @api
 */
class HttpCompressionMiddleware
{
    public function __invoke(Request $request, RequestHandlerInterface $handler): Response
    {
        $acceptEncoding = $request->getHeaderLine('Accept-Encoding');

        $response = $handler->handle($request);

        // Extract body content
        $body = (string) $response->getBody();
        $etag = '"' . sha1($body) . '"'; // ETag between quotes, as per HTTP spec

        // Add ETag header
        $response = $response->withHeader('ETag', $etag)
            ->withHeader('Cache-Control', 'public, max-age=0, must-revalidate');

        // Check If-None-Match to skip sending body if unchanged
        $ifNoneMatch = $request->getHeaderLine('If-None-Match');
        if ($ifNoneMatch === $etag) {
            // Content not modified
            return $response
                ->withStatus(304)
                ->withHeader('Content-Length', '0');
        }

        // Gzip compression if supported
        if (strpos($acceptEncoding, 'gzip') !== false) {
            $gzipped = gzencode($body);

            $response = $response
                ->withHeader('Content-Encoding', 'gzip')
                ->withHeader('Vary', 'Accept-Encoding')
                ->withHeader('Content-Length', (string) strlen($gzipped));

            $response->getBody()->rewind();
            $response->getBody()->write($gzipped);
        } else {
            // Ensure correct Content-Length if no gzip
            $response = $response->withHeader('Content-Length', (string) strlen($body));
        }

        return $response;
    }
}
