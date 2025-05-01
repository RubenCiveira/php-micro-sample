<?php

use Civi\Micro\AppBuilder;
use Civi\Security\Policy\PolicyEngine;
use Civi\Store\Schemas;

Schemas::register('usermanagement', __DIR__.'/../config/schemas/usermanagement');
PolicyEngine::register( __DIR__.'/../conf/guards.yaml');
AppBuilder::dependencies(__DIR__.'/di.container.php');
AppBuilder::routes(__DIR__.'/slim.routes.php');
