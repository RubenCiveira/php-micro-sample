<?php

declare(strict_types=1);

namespace Civi\View\Twig;

use Civi\Micro\ProjectLocator;
use Civi\View\ViewServices;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use Twig\Environment;

class BaseView
{
    public function __construct(private readonly ViewServices $services, private readonly string $name, private readonly string $templates)
    {
    }

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
    protected function isAccesible(Request $request)
    {
        $on = $this->services->currentView($request);
        return $this->services->guard->canView($on);
    }
    protected function render(array $context, Request $request, Response $response): Response
    {
        if (isset($_SESSION['indicator'])) {
            $context['indicator'] = $_SESSION['indicator'];
            unset($_SESSION['indicator']);
        }
        $twig = $this->twigEngine($request);
        $html = $twig->render("{$this->name}.html.twig", $context);
        $response->getBody()->write($html);
        return $response;
    }

    protected function foward(string $label, array $context, Request $request, Response $response): Response
    {
        $twig = $this->twigEngine($request);
        $html = $twig->render("{$label}.html.twig", $context);
        $response->getBody()->write($html);
        return $response;
    }

    private function twigEngine(Request $request): Environment
    {
        $loader = new \Twig\Loader\FilesystemLoader($this->templates);
        $base = realpath($this->templates);
        $loader->addPath($base);
        $root = realpath(ProjectLocator::getRootPath() . "/{$this->services->config->rootTemplateDir}");
        if ($base != $root) {
            $loader->addPath($root);
        }
        $loader->addPath(realpath(__DIR__.'/../../templates'));
        return new AssetOptimizingTwigEnvironment($this->services, $request, $loader, [
            'cache' => ProjectLocator::getRootPath() . '/.cache',
            'debug' => true,
        ]);
    }
}
