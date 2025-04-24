<?php

use Civi\Repomanager\Bootstrap\Security\SecurityConfig;
use Civi\Micro\Middleware\GzipMiddleware;
use Civi\Store\Endpoint\Register;
use Civi\RepomanagerBackoffice\ConfigurationView;
use Civi\RepomanagerBackoffice\CredentialsView;
use Civi\RepomanagerBackoffice\IndexView;
use Civi\RepomanagerBackoffice\PackagesView;
use Slim\App;
use Civi\Repomanager\Bootstrap\Security\GoogleSecurityMiddleware;

return function (App $app) {
    $container = $app->getContainer();
    $config = $container->get(SecurityConfig::class);
    $app->add(GoogleSecurityMiddleware::class);
    $app->add(GzipMiddleware::class);
    $app->get("/" . basename($config->googleRedirectUri), [GoogleSecurityMiddleware::class, 'verifyAuthorization']);
    $app->get("/", [IndexView::class, 'get']);
    $app->get("/packages", [PackagesView::class, 'get']);
    $app->post("/packages", [PackagesView::class, 'post']);
    $app->get("/credentials", [CredentialsView::class, 'get']);
    $app->post("/credentials", [CredentialsView::class, 'post']);
    $app->get("/configuration", [ConfigurationView::class, 'get']);

    Register::register($app);
};