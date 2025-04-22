<?php

namespace Civi\RepomanagerBackoffice;

use Civi\Repomanager\Shared\Infrastructure\View\Twig\ComponentExtension;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use Twig\TwigFunction;

class BaseView
{
    public static function redirect(string $target,  Request $request, Response $response): Response
    {
        $routeContext = RouteContext::fromRequest($request);
        $basePath = $routeContext->getBasePath();
        return $response->withHeader('Location', "{$basePath}/{$target}")->withStatus(302);
    }
    public static function template(string $name, array $context, Request $request, Response $response): Response
    {
        $routeContext = RouteContext::fromRequest($request);
        $basePath = $routeContext->getBasePath();
        $route = $routeContext->getRoute();
        $context['route'] = $route ? substr($route->getPattern(), 1) : '';
        
        // if (isset($_SESSION['flash'])) {
        //     $context['flash'] = $_SESSION['flash'];
        //     unset($_SESSION['flash']);
        // }

        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
        $twig = new \Twig\Environment($loader, [
            'cache' => __DIR__ . '/../../.cache',
            'debug' => true,
        ]);
        $twig->addFunction(new TwigFunction('path', function (string $routeName, array $params = []) use($basePath) {
            $url = "{$basePath}/{$routeName}";
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            return $url;
        }));
        $twig->addFunction(new TwigFunction('asset', function (string $routeName) use($basePath) {
            $url = "{$basePath}/{$routeName}";
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            return $url;
        }));
        $twig->addExtension(new ComponentExtension());
        // Renderizar la plantilla con los datos
        $response->getBody()->write($twig->render($name . ".html.twig", $context));
        return $response;
    }

}