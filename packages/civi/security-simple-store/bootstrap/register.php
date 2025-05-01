<?php

use Civi\Micro\AppBuilder;
use Civi\Micro\Config;
use Civi\Security\Policy\PolicyEngine;
use Civi\Store\Schemas;

PolicyEngine::register( __DIR__.'/../config/guards.yaml');
Schemas::register('usermanagement', __DIR__.'/../config/schemas/usermanagement');

AppBuilder::dependencies(__DIR__.'/di.container.php');
AppBuilder::routes(__DIR__.'/slim.routes.php');
