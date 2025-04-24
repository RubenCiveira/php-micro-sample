<?php

declare(strict_types=1);

namespace Civi\View\Twig;

use Civi\Micro\ProjectLocator;
use Civi\View\ViewConfig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;

class BaseView
{
    public function __construct(private readonly ViewConfig $config, private readonly string $name, private readonly string $templates) {}

    protected function redirect(string $target, Request $request, Response $response): Response
    {
        $routeContext = RouteContext::fromRequest($request);
        $basePath = $routeContext->getBasePath();
        return $response->withHeader('Location', "{$basePath}/{$target}")->withStatus(302);
    }
    protected function addIndication(string $text)
    {
        $_SESSION['indicator'] = ['kind' => "primary", 'message' => $text];
    }
    protected function addErrorIndication(string $text)
    {
        $_SESSION['indicator'] = ['kind' => "danger", 'message' => $text];
    }
    protected function render(array $context, Request $request, Response $response): Response
    {
        if (isset($_SESSION['indicator'])) {
            $context['indicator'] = $_SESSION['indicator'];
            unset($_SESSION['indicator']);
        }
        $loader = new \Twig\Loader\FilesystemLoader($this->templates);
        $base = realpath($this->templates);
        $root = realpath(ProjectLocator::getRootPath() . "/{$this->config->rootTemplateDir}");
        if ($base != $root) {
            $loader->addPath($root, 'Root');
        }
        $twig = new AssetOptimizingTwigEnvironment($request, $loader, [
            'cache' => ProjectLocator::getRootPath() . '/.cache',
            'debug' => true,
        ]);
        $html = $twig->render("{$this->name}.html.twig", $context);
        $response->getBody()->write($html);
        return $response;
    }
}
