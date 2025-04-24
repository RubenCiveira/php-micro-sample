<?php
namespace Civi\Repomanager\Shared\Infrastructure\View\Twig;

use Civi\Repomanager\Shared\Infrastructure\View\Twig\AssetOptimizingTwigEnvironment;
use Civi\Repomanager\Shared\Infrastructure\View\ViewMetadata;
use Civi\Repomanager\Shared\ProjectLocator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;

abstract class MasterDetailView
{

    public function __construct(private readonly string $name, private readonly string $templates) 
    {

    }
    public function post(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        $meta = $this->meta();
        try {
            $text = $meta->exec($data);
            if( $response ) {
                $_SESSION['indicator'] = ['kind' => 'primary', 'message' => $text];
            }
        } catch (\Exception $ex) {
            $_SESSION['indicator'] = ['kind' => 'danger', 'message' => $ex->getMessage()];
        }
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        return $this->redirect($route ? substr($route->getPattern(), 1) : '', $request, $response);
    }
    public function get(Request $request, Response $response, array $args): Response
    {
        $params = $request->getQueryParams();
        if( isset($params['fetch']) ) {
            $values = array_map(fn($row) => is_array($row) ? $row : get_object_vars($row), $this->list());
            $response->getBody()->write( json_encode($values) );
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $meta = $this->meta()->export();
            // $values = array_map(fn($row) => is_array($row) ? $row : get_object_vars($row), $this->list());
            $context = [
                'meta' => $meta,
                'values' => []
            ];
            return $this->render($this->name, $context, $request, $response);
        }
    }

    protected abstract function meta(): ViewMetadata;
    protected abstract function list(): array;

    private function redirect(string $target, Request $request, Response $response): Response
    {
        $routeContext = RouteContext::fromRequest($request);
        $basePath = $routeContext->getBasePath();
        return $response->withHeader('Location', "{$basePath}/{$target}")->withStatus(302);
    }
    private function render(string $name, array $context, Request $request, Response $response): Response
    {
        if (isset($_SESSION['indicator'])) {
          $context['indicator'] = $_SESSION['indicator'];
          unset($_SESSION['indicator']);
        }
        $loader = new \Twig\Loader\FilesystemLoader($this->templates );// __DIR__ . '/templates');
        $twig = new AssetOptimizingTwigEnvironment($request, $loader, [
            'cache' => ProjectLocator::getRootPath() . '/.cache',
            'debug' => true,
        ]);
        $html = $twig->render("{$name}.html.twig", $context);
        $response->getBody()->write($html);
        return $response;
    }
}