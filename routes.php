<?php

use Civi\Repomanager\Bootstrap\Security\SecurityConfig;
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
    $app->get("/" . basename($config->googleRedirectUri), [GoogleSecurityMiddleware::class, 'verifyAuthorization']);
    $app->get("/", [IndexView::class, 'get']);
    $app->get("/packages", [PackagesView::class, 'get']);
    $app->get("/credentials", [CredentialsView::class, 'get']);
    $app->post("/credentials", [CredentialsView::class, 'post']);
    $app->get("/configuration", [ConfigurationView::class, 'get']);
};