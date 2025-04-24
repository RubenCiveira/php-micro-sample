<?php declare(strict_types=1);

namespace Civi\View\Twig;

use Civi\View\AssetOptimizer;
use Civi\Micro\ProjectLocator;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

use Twig\Environment;
use Twig\Loader\LoaderInterface;
use Twig\TwigFunction;

class AssetOptimizingTwigEnvironment extends Environment
{
    private AssetOptimizer $optimizer;

    public function __construct(ServerRequestInterface $request, LoaderInterface $loader, array $options = [])
    {
        parent::__construct($loader, $options);

        $routeContext = RouteContext::fromRequest($request);
        $basePath = $routeContext->getBasePath();

        $root = ProjectLocator::getRootPath();
        $this->optimizer = new AssetOptimizer(  "{$root}/public/.assets", "{$basePath}/.assets");

        $this->addFunction(new TwigFunction('path', function (string $routeName, array $params = []) use ($basePath) {
            $url = "{$basePath}/{$routeName}";
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            return $url;
        }));
        $this->addFunction(new TwigFunction('asset', function (string $routeName) use ($basePath) {
            $url = "{$basePath}/{$routeName}";
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            return $url;
        }));
        $this->addExtension(new ComponentExtension());
    }

    public function render($name, array $context = []): string
    {
        $html = parent::render($name, $context);
        return $this->optimizer->optimize($html);
    }

    public function display($name, array $context = []): void
    {
        echo $this->render($name, $context);
    }
}
