<?php

use Civi\SecurityStore\Bootstrap\AuthSecurityMiddleware;
use Civi\SecurityStore\Bootstrap\SecurityConfig;
use Civi\SecurityStoreBackoffice\LoginView;
use Civi\SecurityStoreBackoffice\RolesView;
use Civi\SecurityStoreBackoffice\UsersView;
use Civi\View\ViewBuilder;
use Civi\View\ViewSection;
use Slim\App;


return function (App $app) {
    $container = $app->getContainer();
    $config = $container->get(SecurityConfig::class);
    $app->add(AuthSecurityMiddleware::class);
    AuthSecurityMiddleware::register($config, $app);
    $app->get($config->loginUrl, [LoginView::class, 'get']);


    if( ViewBuilder::registerView(new ViewSection('backoffice', 'Users', "/users") )) {
        $app->get("/users", [UsersView::class, 'get']);
        $app->post("/users", [UsersView::class, 'post']);
    }

    if( ViewBuilder::registerView(new ViewSection('backoffice', 'Roles', "/roles") ) ) {
        $app->get("/roles", [RolesView::class, 'get']);
        $app->post("/roles", [RolesView::class, 'post']);
    }
};
