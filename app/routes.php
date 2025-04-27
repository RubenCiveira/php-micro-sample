<?php

use Civi\Micro\AppBuilder;
use Civi\Repomanager\Bootstrap\Security\SecurityConfig;
use Civi\Micro\Middleware\GzipMiddleware;
use Civi\Store\Endpoint\Register;
use Civi\RepomanagerBackoffice\ConfigurationView;
use Civi\RepomanagerBackoffice\CredentialsView;
use Civi\RepomanagerBackoffice\IndexView;
use Civi\RepomanagerBackoffice\PackagesView;
use Slim\App;
use Civi\Repomanager\Bootstrap\Security\GoogleSecurityMiddleware;
use Civi\View\ViewBuilder;
use Civi\View\ViewSection;

return function (App $app) {
    $container = $app->getContainer();
    $config = $container->get(SecurityConfig::class);
    $app->add(GoogleSecurityMiddleware::class);

    if( ViewBuilder::registerView(new ViewSection('backoffice', 'home', "/")) ) {
        $app->get("/" . basename($config->googleRedirectUri), [GoogleSecurityMiddleware::class, 'verifyAuthorization']);
        $app->get("/", [IndexView::class, 'get']);
    }

    if( ViewBuilder::registerView(new ViewSection('backoffice', 'Configuration', "/configuration")) ) {
        $app->get("/configuration", [ConfigurationView::class, 'get']);
    }

    if( ViewBuilder::registerView(new ViewSection('backoffice', 'Credentials', "/credentials")) ) {
        $app->get("/credentials", [CredentialsView::class, 'get']);
        $app->post("/credentials", [CredentialsView::class, 'post']);
    }
    
    if( ViewBuilder::registerView(new ViewSection('backoffice', 'Packages', "/packages")) ) {
        $app->get("/packages", [PackagesView::class, 'get']);
        $app->post("/packages", [PackagesView::class, 'post']);
    }

    Register::register($app);
};