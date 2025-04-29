<?php

declare(strict_types=1);

namespace Civi\View\Twig;

use Civi\Micro\Schema\TypeSchemaBuilder;
use Civi\View\ViewConfig;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;

abstract class MasterDetailView extends BaseView
{

    public function __construct(private readonly ViewConfig $config, private readonly string $name, private readonly string $templates) 
    {
        parent::__construct($config, $name, $templates);

    }
    public function post(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        $meta = $this->meta();
        try {
            $text = $meta->exec($data);
            if ($response) {
                $this->addIndication($text);
            }
        } catch (\Exception $ex) {
            $this->addErrorIndication($ex->getMessage());
        }
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        return $this->redirect($route ? substr($route->getPattern(), 1) : '', $request, $response);
    }
    public function get(Request $request, Response $response, array $args): Response
    {
        $params = $request->getQueryParams();
        $meta = $this->meta()->export();
        if (isset($params['fetch'])) {
            $values = [];
            if( isset($params['field']) ) {
                $field = $meta->getField( $params['field'] );
                if( $field?->reference->load ) {
                    $callback = $field->reference->load;
                    $values = $callback();
                } else {
                    throw new InvalidArgumentException("To load refence a callback is needed");
                }
            } else {
                $query = [];
                $includes = [];
                foreach($meta->fields as $fieldName => $fieldInfo) {
                    if( $fieldInfo->reference ?? false ) {
                        $includes[] = "{$fieldName}.{$fieldInfo->reference->label}";
                    }
                }
                $query = new MasterDetailListQuery(query: $query, include: $includes);
                $values = array_map(fn($row) => is_array($row) ? $row : get_object_vars($row), $this->list($query, $includes) );
            }
            $response->getBody()->write(json_encode($values));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $context = [
                'meta' => $meta,
                'values' => []
            ];
            return $this->render($context, $request, $response);
        }
    }

    protected abstract function meta(): TypeSchemaBuilder;

    protected abstract function list(MasterDetailListQuery $query): array;
}
