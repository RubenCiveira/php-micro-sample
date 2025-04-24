<?php

use Civi\Micro\AppBuilder;
use Civi\SecurityStoreBackoffice\RolesView;
use Civi\SecurityStoreBackoffice\UsersView;
use Slim\App;

return function (App $app) {
    if( AppBuilder::registerView('backoffice', 'Users', "/users") ) {
        $app->get("/users", [UsersView::class, 'get']);
        $app->post("/users", [UsersView::class, 'post']);
    }

    if( AppBuilder::registerView('backoffice', 'Roles', "/roles") ) {
        $app->get("/roles", [RolesView::class, 'get']);
        $app->post("/roles", [RolesView::class, 'post']);
    }
};
