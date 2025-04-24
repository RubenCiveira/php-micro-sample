<?php
namespace Civi\RepomanagerBackoffice;

use Civi\Repomanager\Shared\Infrastructure\View\Twig\ComponentExtension;
use Civi\Repomanager\Shared\Infrastructure\View\ViewMetadata;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;
use Slim\Routing\RouteContext;
use Twig\TwigFunction;
use voku\helper\HtmlMin;

abstract class MasterDetailView
{

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
            return $this->render($this->template(), $context, $request, $response);
        }
    }

    protected abstract function template(): string;
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
        $routeContext = RouteContext::fromRequest($request);
        $basePath = $routeContext->getBasePath();
        $route = $routeContext->getRoute();
        $context['route'] = $route ? substr($route->getPattern(), 1) : '';

        if (isset($_SESSION['indicator'])) {
          $context['indicator'] = $_SESSION['indicator'];
          unset($_SESSION['indicator']);
        }

        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
        $twig = new \Twig\Environment($loader, [
            'cache' => __DIR__ . '/../../.cache',
            'debug' => true,
        ]);
        $twig->addFunction(new TwigFunction('path', function (string $routeName, array $params = []) use ($basePath) {
            $url = "{$basePath}/{$routeName}";
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            return $url;
        }));
        $twig->addFunction(new TwigFunction('asset', function (string $routeName) use ($basePath) {
            $url = "{$basePath}/{$routeName}";
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            return $url;
        }));
        $twig->addExtension(new ComponentExtension());
        // Renderizar la plantilla con los datos

        $html = $twig->render("{$name}.html.twig", $context);
        // $htmlMin = new HtmlMin();
        // $html = $htmlMin->minify($html);
        $response->getBody()->write($html);
        return $response;
    }

}