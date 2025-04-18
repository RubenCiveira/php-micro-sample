<?php
namespace Civi\RepomanagerBackoffice;

use Civi\Repomanager\Shared\Infrastructure\Simple\FormMetadata;
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
        if (isset($data['delete'])) {
            $this->delete($data['delete']);
        } else {
            if (!$data['id']) {
                $data['id'] = Uuid::uuid4()->toString();
            }
            $this->save( $data );
        }
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        return $this->redirect($route ? substr($route->getPattern(), 1) : '', $request, $response);
    }
    public function get(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);
        // $basePath = $routeContext->getBasePath();
        $route = $routeContext->getRoute();
        $context = [
            'meta' => $this->meta()->export(),
            'values' => array_map(fn($row) => get_object_vars($row), $this->list())
        ];
        return $this->render($this->template(), $context, $request, $response);
    }

    protected abstract function template(): string;
    protected abstract function meta(): FormMetadata;

    protected abstract function list(): array;

    protected abstract function delete(string $id);

    protected abstract function save(array $data);

    private function redirect(string $target,  Request $request, Response $response): Response
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
        // Renderizar la plantilla con los datos
        $html = $twig->render($name . ".html.twig", $context);
        $htmlMin = new HtmlMin();
        $html = $htmlMin->minify($html);
        // $html = $this->minifyHtml( $html );
        $response->getBody()->write($html);
        return $response;
    }

    private function minifyHtml(string $html): string {
        return preg_replace(['/>\s+</', '/\s+/'], ['><', ' '], $html);
    }
    
}