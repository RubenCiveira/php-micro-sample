<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\Store\Endpoint;

use Civi\Repomanager\Shared\Infrastructure\Store\Gateway\DataGateway;
use Civi\Repomanager\Shared\Infrastructure\Store\GraphQLProcessor;
use Civi\Repomanager\Shared\Infrastructure\Store\Schemas;
use Civi\Repomanager\Shared\Infrastructure\Store\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class GraphQLController
{
    public function __construct(
        private readonly Schemas $schemas,
        private readonly DataGateway $datas,
        private readonly Validator $validator
    ) {
    }
    public function get(Request $request, Response $response, array $args)
    {
        $data = $this->schemas->sdl($args['namespace']);
        $response->getBody()->write(string: $data);
        return $response->withHeader('Content-Type', 'application/txt');
    }

    public function post(Request $request, Response $response, array $args)
    {
        $namepace = $args['namespace'];
        $input = json_decode($request->getBody()->getContents(), true);
        $query = $input['query'] ?? '';
        $variables = $input['variables'] ?? null;
        $result = new GraphQLProcessor($this->schemas, $this->datas, $this->validator, $namepace, $query, $variables)->result();
        $output = $result->toArray();
        $response->getBody()->write(json_encode($output));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
