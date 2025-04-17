<?php

use Civi\Repomanager\Bootstrap\Security\SecurityConfig;
use Slim\App;
use Civi\Repomanager\Bootstrap\Security\GoogleSecurityMiddleware;

return function (App $app) {
    $container = $app->getContainer();
    $config = $container->get(SecurityConfig::class);
    $app->add(GoogleSecurityMiddleware::class);
    $app->get("/" . basename($config->googleRedirectUri), [GoogleSecurityMiddleware::class, 'verifyAuthorization']);
};