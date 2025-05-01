<?php

use Civi\Micro\Config;
use Civi\SecurityStore\Bootstrap\AuthSecurityMiddleware;
use Civi\SecurityStore\Bootstrap\OAuth\GoogleSecurityMiddleware;
use Civi\SecurityStore\Bootstrap\SecurityConfig;
use Civi\SecurityStore\Features\Access\User\Gateway\UserGateway;
use DI\Container;
use DI\ContainerBuilder;
use Slim\App;

return function (ContainerBuilder $container) {
    $container->addDefinitions([
        AuthSecurityMiddleware::class => \DI\Factory(function (
            SecurityConfig $config,
            UserGateway $users,
            App $app,
            Container $container
        ) {
            $providers = [];
            if( $container->has(GoogleSecurityMiddleware::class) ) {
                $providers[] = $container->get(GoogleSecurityMiddleware::class);
            }
            return new AuthSecurityMiddleware($config, $users, $app, $providers);
        }),
        SecurityConfig::class => \DI\factory(function () {
            return Config::load('app.security', SecurityConfig::class, 'security');
        })]);
};
