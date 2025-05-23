<?php declare(strict_types=1);

namespace Civi\Store\Endpoint;

use Civi\Store\Service\DataService;
use Civi\Store\GraphQLProcessor;
use Civi\Store\Schemas;
use Civi\Store\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class GraphQLController
{
    public function __construct(
        private readonly Schemas $schemas,
        private readonly DataService $datas,
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
