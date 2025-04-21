<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\Store\Endpoint;

use Slim\App;

class Register
{
    public static function register(App $app, string $directory = '')
    {
        $on = $directory ? "{$directory}/" : "";
        $app->get("/{$on}{namespace}/graphql", [GraphQLController::class, 'get']);
        $app->post("/{$on}{namespace}/graphql", [GraphQLController::class, 'post']);
    }
}