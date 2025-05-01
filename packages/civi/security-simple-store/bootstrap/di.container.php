<?php

use Civi\Micro\Config;
use Civi\SecurityStore\Bootstrap\AuthSecurityMiddleware;
use Civi\SecurityStore\Bootstrap\OAuth\GoogleSecurityMiddleware;
use Civi\SecurityStore\Bootstrap\SecurityConfig;
use DI\ContainerBuilder;
use Slim\App;

return function (ContainerBuilder $container) {
    $container->addDefinitions([
        AuthSecurityMiddleware::class => \DI\Factory(function (SecurityConfig $config, App $app, GoogleSecurityMiddleware $google) {
            return new AuthSecurityMiddleware($config, $app, [$google]);
        }),
        SecurityConfig::class => \DI\factory(function () {
            return Config::load('app.security', SecurityConfig::class, 'security');
        })]);
};
