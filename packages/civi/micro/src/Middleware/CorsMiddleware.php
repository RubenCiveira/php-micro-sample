<?php

declare(strict_types=1);

namespace Civi\Micro\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @api
 */
class CorsMiddleware
{
    public function __invoke(Request $request, RequestHandlerInterface $handler): Response
    {
        return $handler->handle($request)
            ->withHeader('Access-Control-Allow-Origin', $request->hasHeader('Origin') ? $request->getHeader('Origin') : 'localhost')
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', '*')
            ->withHeader('Access-Control-Max-Age', '86400')
        ;
    }
}
