<?php

declare(strict_types=1);

namespace Civi\View;

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;

class ViewServices
{
    public function __construct(
        public readonly ViewConfig $config, 
        public readonly ViewGuard $guard
    ){}

    public function currentView(Request $request)
    {
        $routeContext = RouteContext::fromRequest($request);
        $basePath = $routeContext->getBasePath();
        
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if( str_starts_with($currentPath, $basePath) ) {
            $currentPath = substr($currentPath, strlen($basePath));
        }
        return rtrim($currentPath, '/');
    }
}
